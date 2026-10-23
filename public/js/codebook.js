// Reusable Custom Confirmation Modal (Promise-based)
let activeConfirmCallback = null;

function showConfirmModal(options = {}) {
    const {
        title = 'Konfirmasi Hapus',
        message = 'Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.',
        confirmText = 'Hapus',
        cancelText = 'Batal',
        danger = true
    } = options;

    return new Promise(function (resolve) {
        if (activeConfirmCallback) {
            activeConfirmCallback(false);
            activeConfirmCallback = null;
        }

        const modal = document.getElementById('confirmModal');
        if (!modal) {
            resolve(window.confirm(message));
            return;
        }

        const titleEl = document.getElementById('confirm-modal-title');
        const msgEl = document.getElementById('confirm-modal-message');
        const confirmBtn = document.getElementById('confirm-modal-confirm-btn');
        const cancelBtn = document.getElementById('confirm-modal-cancel-btn');

        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;
        if (confirmBtn) {
            confirmBtn.textContent = confirmText;
            confirmBtn.className = danger ? 'icon-btn btn-danger' : 'icon-btn btn-primary';
        }
        if (cancelBtn) cancelBtn.textContent = cancelText;

        function finish(result) {
            modal.classList.remove('active');
            confirmBtn.removeEventListener('click', onConfirm);
            cancelBtn.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onOverlayClick);
            document.removeEventListener('keydown', onKeyDown);
            activeConfirmCallback = null;
            resolve(result);
        }

        function onConfirm() {
            finish(true);
        }

        function onCancel() {
            finish(false);
        }

        function onOverlayClick(e) {
            if (e.target === modal) {
                finish(false);
            }
        }

        function onKeyDown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                finish(false);
            } else if (e.key === 'Enter' && document.activeElement !== cancelBtn) {
                e.preventDefault();
                finish(true);
            }
        }

        activeConfirmCallback = finish;

        confirmBtn.addEventListener('click', onConfirm);
        cancelBtn.addEventListener('click', onCancel);
        modal.addEventListener('click', onOverlayClick);
        document.addEventListener('keydown', onKeyDown);

        modal.classList.add('active');
        setTimeout(() => {
            if (cancelBtn) cancelBtn.focus();
        }, 50);
    });
}
window.showConfirmModal = showConfirmModal;

document.addEventListener('DOMContentLoaded', function () {
    marked.setOptions({ breaks: true, gfm: true });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const thread = document.getElementById('thread');
    const emptyState = document.getElementById('empty-state');
    const emptyStateText = document.getElementById('empty-state-text');
    const noNoteState = document.getElementById('no-note-state');
    const composer = document.getElementById('composer');
    const input = document.getElementById('msg-input');
    const sendBtn = document.getElementById('send-btn');
    const noteTitleInput = document.getElementById('note-title-input');
    const senderButtons = document.querySelectorAll('.sender-toggle button');
    const themeBtn = document.getElementById('theme-btn');
    const fontSlider = document.getElementById('font-size');
    const fontSizeOut = document.getElementById('font-size-out');
    const hljsDark = document.getElementById('hljs-dark');
    const hljsLight = document.getElementById('hljs-light');

    let currentSender = 'me';
    let messages = window.INITIAL_MESSAGES || [];
    let titleDebounceTimeout = null;
    let originalNoteTitle = noteTitleInput ? noteTitleInput.value : '';

    function autoResizeNoteTitle() {
        if (!noteTitleInput) return;
        noteTitleInput.style.height = 'auto';
        noteTitleInput.style.height = noteTitleInput.scrollHeight + 'px';
    }

    function updateTitleCharCount(len) {
        const countEl = document.getElementById('title-char-count');
        if (countEl) {
            countEl.textContent = `${len}/255`;
        }
    }

    async function persistNoteTitle(noteId, titleToSave) {
        try {
            await fetch(`/notes/${noteId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ title: titleToSave })
            });
        } catch (e) {
            console.error('Failed to auto-save note title', e);
        }
    }

    if (noteTitleInput) {
        autoResizeNoteTitle();
        updateTitleCharCount(noteTitleInput.value.length);

        // Track original title on focus to allow revert on Escape
        noteTitleInput.addEventListener('focus', function () {
            originalNoteTitle = noteTitleInput.value;
        });

        // Keydown handling: Enter commits, Escape cancels and reverts
        noteTitleInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                clearTimeout(titleDebounceTimeout);

                // Revert to original title
                noteTitleInput.value = originalNoteTitle;
                autoResizeNoteTitle();
                updateTitleCharCount(originalNoteTitle.length);

                if (window.CURRENT_NOTE) {
                    window.CURRENT_NOTE.title = originalNoteTitle;
                    const activeNav = document.querySelector(`.tree-note[data-note-id="${window.CURRENT_NOTE.id}"] .note-title-text`);
                    if (activeNav) activeNav.textContent = originalNoteTitle;
                    document.title = `Codebook - ${originalNoteTitle}`;
                    // Ensure backend has the reverted title
                    persistNoteTitle(window.CURRENT_NOTE.id, originalNoteTitle);
                }
                noteTitleInput.blur();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                noteTitleInput.blur();
            }
        });

        // Blur event: validate empty text and commit changes safely
        noteTitleInput.addEventListener('blur', function () {
            clearTimeout(titleDebounceTimeout);
            if (!window.CURRENT_NOTE) return;

            let trimmed = noteTitleInput.value.trim();
            // Safety guard: prevent accidental empty title
            if (!trimmed) {
                trimmed = originalNoteTitle.trim() || 'Untitled Note';
                noteTitleInput.value = trimmed;
                autoResizeNoteTitle();
            }

            updateTitleCharCount(trimmed.length);
            window.CURRENT_NOTE.title = trimmed;
            document.title = `Codebook - ${trimmed}`;
            const activeNav = document.querySelector(`.tree-note[data-note-id="${window.CURRENT_NOTE.id}"] .note-title-text`);
            if (activeNav) activeNav.textContent = trimmed;

            // Save if changed from original
            if (trimmed !== originalNoteTitle) {
                originalNoteTitle = trimmed;
                persistNoteTitle(window.CURRENT_NOTE.id, trimmed);
            }
        });
    }

    // Sender toggle
    senderButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            senderButtons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentSender = btn.getAttribute('data-sender');
        });
    });

    // Theme toggle
    if (themeBtn) {
        const updateThemeBtn = () => {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            if (isLight) {
                themeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg> <span>Light Mode</span>';
                themeBtn.setAttribute('title', 'Tema saat ini: Light Mode. Klik untuk beralih ke Dark Mode');
            } else {
                themeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> <span>Dark Mode</span>';
                themeBtn.setAttribute('title', 'Tema saat ini: Dark Mode. Klik untuk beralih ke Light Mode');
            }
        };

        updateThemeBtn();

        themeBtn.addEventListener('click', function () {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            if (isLight) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('codebook_theme', 'dark');
                if (hljsDark && hljsLight) {
                    hljsDark.disabled = false;
                    hljsLight.disabled = true;
                }
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('codebook_theme', 'light');
                if (hljsDark && hljsLight) {
                    hljsDark.disabled = true;
                    hljsLight.disabled = false;
                }
            }
            updateThemeBtn();
        });
    }

    // Font size slider
    if (fontSlider) {
        const savedFontSize = localStorage.getItem('codebook_font_size') || '14.5';
        fontSlider.value = savedFontSize;
        if (fontSizeOut) fontSizeOut.textContent = savedFontSize + 'px';
        document.documentElement.style.setProperty('--chat-font-size', savedFontSize + 'px');

        fontSlider.addEventListener('input', function () {
            const val = fontSlider.value;
            document.documentElement.style.setProperty('--chat-font-size', val + 'px');
            if (fontSizeOut) fontSizeOut.textContent = val + 'px';
            localStorage.setItem('codebook_font_size', val);
        });
    }

    // Sidebar Collapse / Minimize Handling
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
    const sidebarFloatingToggle = document.getElementById('sidebar-floating-toggle');

    function updateSidebarToggleAria(isCollapsed) {
        if (sidebarToggleBtn) {
            sidebarToggleBtn.setAttribute('title', isCollapsed ? 'Buka Sidebar (Ctrl+B)' : 'Tutup Sidebar (Ctrl+B)');
            sidebarToggleBtn.setAttribute('aria-expanded', !isCollapsed);
        }
    }

    function toggleSidebar(forceState) {
        const isCurrentlyCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
        const shouldCollapse = (typeof forceState === 'boolean') ? forceState : !isCurrentlyCollapsed;

        if (shouldCollapse) {
            document.documentElement.classList.add('sidebar-collapsed');
            localStorage.setItem('codebook_sidebar_collapsed', 'true');
        } else {
            document.documentElement.classList.remove('sidebar-collapsed');
            localStorage.setItem('codebook_sidebar_collapsed', 'false');
        }

        updateSidebarToggleAria(shouldCollapse);
    }

    // Set initial aria / title
    updateSidebarToggleAria(document.documentElement.classList.contains('sidebar-collapsed'));

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function () {
            toggleSidebar();
        });
    }

    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', function () {
            toggleSidebar(true);
        });
    }

    if (sidebarFloatingToggle) {
        sidebarFloatingToggle.addEventListener('click', function () {
            toggleSidebar(false);
        });
    }

    // Keyboard shortcuts: Ctrl+B / Cmd+B (when not typing in inputs/textareas) and Ctrl+\ / Cmd+\
    document.addEventListener('keydown', function (e) {
        const isCtrlOrCmd = e.ctrlKey || e.metaKey;
        if (!isCtrlOrCmd) return;

        if (e.key === '\\') {
            e.preventDefault();
            toggleSidebar();
            return;
        }

        if (e.key.toLowerCase() === 'b') {
            const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (activeTag !== 'textarea' && activeTag !== 'input') {
                e.preventDefault();
                toggleSidebar();
            }
        }
    });

    // Keyboard shortcut for Kirim
    if (input) {
        input.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    // Send Message
    async function sendMessage() {
        if (!window.CURRENT_NOTE || !input) return;
        const text = input.value;
        if (!text.trim()) return;

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        sendBtn.disabled = true;

        try {
            const res = await fetch(`/notes/${window.CURRENT_NOTE.id}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    sender: currentSender,
                    content: text,
                    sent_time: timeStr
                })
            });

            const data = await res.json();
            if (data.success && data.message) {
                messages.push(data.message);
                input.value = '';
                renderAll();
                thread.scrollTop = thread.scrollHeight;
            }
        } catch (err) {
            console.error('Error sending message:', err);
            alert('Gagal mengirim pesan ke database.');
        } finally {
            sendBtn.disabled = false;
        }
    }

    // Delete message
    window.deleteMessage = async function (id) {
        if (!window.CURRENT_NOTE) return;

        const confirmed = await window.showConfirmModal({
            title: 'Hapus Pesan',
            message: 'Apakah Anda yakin ingin menghapus pesan ini?',
            confirmText: 'Hapus Pesan',
            cancelText: 'Batal',
            danger: true
        });
        if (!confirmed) return;

        try {
            const res = await fetch(`/notes/${window.CURRENT_NOTE.id}/messages/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await res.json();
            if (data.success) {
                messages = messages.filter(function (m) { return m.id !== id; });
                renderAll();
            }
        } catch (err) {
            console.error('Error deleting message:', err);
        }
    };

    // Clear all messages
    window.clearCurrentNoteMessages = async function () {
        if (!window.CURRENT_NOTE || messages.length === 0) return;

        const noteTitle = window.CURRENT_NOTE.title || '';
        const messageText = noteTitle
            ? `Apakah Anda yakin ingin menghapus semua pesan di catatan "${noteTitle}"? Riwayat pesan tidak dapat dikembalikan.`
            : 'Apakah Anda yakin ingin menghapus semua pesan di catatan ini? Riwayat pesan tidak dapat dikembalikan.';

        const confirmed = await window.showConfirmModal({
            title: 'Hapus Semua Pesan',
            message: messageText,
            confirmText: 'Hapus Semua',
            cancelText: 'Batal',
            danger: true
        });
        if (!confirmed) return;

        try {
            const res = await fetch(`/notes/${window.CURRENT_NOTE.id}/clear`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await res.json();
            if (data.success) {
                messages = [];
                renderAll();
            }
        } catch (err) {
            console.error('Error clearing messages:', err);
        }
    };

    // Toggle collapse
    function toggleCollapse(id) {
        const el = document.getElementById('md-' + id);
        const fade = document.getElementById('fade-' + id);
        const btn = document.getElementById('toggle-' + id);
        if (!el) return;
        const collapsed = el.classList.toggle('collapsed');
        if (fade) fade.style.display = collapsed ? 'block' : 'none';
        if (btn) btn.innerHTML = collapsed ? '&#8964; tampilkan semua' : '&#8963; sembunyikan';
    }

    // Setup Code Block Copy Buttons & Language Badges
    function setupCodeBlocks(container) {
        if (!container) return;
        container.querySelectorAll('pre').forEach(function (pre) {
            if (pre.parentElement && pre.parentElement.classList.contains('code-block-wrap')) {
                return;
            }

            const code = pre.querySelector('code');
            if (!code) return;

            // Highlight syntax if not yet processed
            if (!code.classList.contains('hljs') && window.hljs) {
                hljs.highlightElement(code);
            }

            // Extract language (e.g. language-powershell, language-command, language-code, etc.)
            let langName = '';
            const langMatch = code.className.match(/\blanguage-([a-zA-Z0-9_-]+)\b/i);
            if (langMatch && langMatch[1]) {
                const rawLang = langMatch[1].toLowerCase();
                if (rawLang !== 'undefined' && rawLang !== 'hljs') {
                    langName = rawLang;
                }
            }

            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrap' + (langName ? ' has-lang' : '');

            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            // Tools container in the corner
            const tools = document.createElement('div');
            tools.className = 'code-block-tools';

            if (langName) {
                const langBadge = document.createElement('span');
                langBadge.className = 'code-lang-badge';
                langBadge.textContent = langName;
                tools.appendChild(langBadge);
            }

            const copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.className = 'code-copy-btn';
            copyBtn.setAttribute('title', 'Salin kode');
            copyBtn.setAttribute('aria-label', 'Salin kode');
            copyBtn.innerHTML = `
                <svg class="copy-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <svg class="check-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span class="copy-btn-text">Salin</span>
            `;

            copyBtn.addEventListener('click', async function (e) {
                e.stopPropagation();
                const textToCopy = (code.innerText || code.textContent || '')
                    .replace(/\r\n/g, '\n')
                    .replace(/\n$/, '');

                const copyText = async (text) => {
                    if (navigator.clipboard && window.isSecureContext) {
                        try {
                            await navigator.clipboard.writeText(text);
                            return true;
                        } catch (err) {
                            console.warn('Clipboard API failed, using fallback', err);
                        }
                    }
                    try {
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.position = 'fixed';
                        ta.style.left = '-9999px';
                        ta.style.top = '-9999px';
                        ta.setAttribute('readonly', '');
                        document.body.appendChild(ta);
                        ta.focus();
                        ta.select();
                        const success = document.execCommand('copy');
                        document.body.removeChild(ta);
                        return success;
                    } catch (fallbackErr) {
                        console.error('Fallback copy failed', fallbackErr);
                        return false;
                    }
                };

                const isSuccess = await copyText(textToCopy);
                const copyIcon = copyBtn.querySelector('.copy-icon');
                const checkIcon = copyBtn.querySelector('.check-icon');
                const btnText = copyBtn.querySelector('.copy-btn-text');

                if (isSuccess !== false) {
                    copyBtn.classList.add('copied');
                    if (copyIcon) copyIcon.style.display = 'none';
                    if (checkIcon) checkIcon.style.display = 'inline-block';
                    if (btnText) btnText.textContent = 'Tersalin!';

                    setTimeout(function () {
                        copyBtn.classList.remove('copied');
                        if (copyIcon) copyIcon.style.display = 'inline-block';
                        if (checkIcon) checkIcon.style.display = 'none';
                        if (btnText) btnText.textContent = 'Salin';
                    }, 2000);
                } else {
                    if (btnText) btnText.textContent = 'Gagal!';
                    setTimeout(function () {
                        if (btnText) btnText.textContent = 'Salin';
                    }, 2000);
                }
            });

            tools.appendChild(copyBtn);
            wrapper.appendChild(tools);
        });
    }

    // Start inline editing for a chat message
    let activeEditCancel = null;

    function startEditMessage(m, bubble, mdWrap, tools) {
        if (!window.CURRENT_NOTE) return;

        // If another message is already being edited, cancel it first
        if (activeEditCancel) {
            activeEditCancel();
            activeEditCancel = null;
        }

        mdWrap.style.display = 'none';
        tools.style.display = 'none';

        const editBox = document.createElement('div');
        editBox.className = 'message-edit-box';

        const textarea = document.createElement('textarea');
        textarea.className = 'message-edit-textarea';
        textarea.value = m.content || '';
        textarea.placeholder = 'Tulis pesan markdown di sini...';
        textarea.setAttribute('autofocus', '');
        textarea.setAttribute('tabindex', '0');

        const footer = document.createElement('div');
        footer.className = 'message-edit-footer';

        const hint = document.createElement('span');
        hint.className = 'message-edit-hint';
        hint.innerHTML = 'Ctrl+Enter simpan &middot; Esc batal';

        const actions = document.createElement('div');
        actions.className = 'message-edit-actions';

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'icon-btn';
        cancelBtn.textContent = 'Batal';

        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'icon-btn btn-primary';
        saveBtn.textContent = 'Simpan';

        actions.appendChild(cancelBtn);
        actions.appendChild(saveBtn);

        footer.appendChild(hint);
        footer.appendChild(actions);

        editBox.appendChild(textarea);
        editBox.appendChild(footer);

        // Clear any window text selection from click/dblclick
        if (window.getSelection) {
            window.getSelection().removeAllRanges();
        }

        bubble.appendChild(editBox);

        function ensureCursorVisible() {
            if (!thread || !textarea) return;
            const taRect = textarea.getBoundingClientRect();
            const threadRect = thread.getBoundingClientRect();

            // Calculate vertical cursor position
            const text = textarea.value;
            const cursorPos = textarea.selectionEnd || 0;
            const textBeforeCursor = text.substring(0, cursorPos);
            const totalLines = (text.split('\n').length) || 1;
            const currentLine = (textBeforeCursor.split('\n').length) || 1;
            const lineRatio = currentLine / totalLines;

            const estimatedCursorY = taRect.top + (taRect.height * lineRatio);

            if (estimatedCursorY > threadRect.bottom - 60) {
                const diff = estimatedCursorY - (threadRect.bottom - 60);
                thread.scrollTop += diff;
            } else if (estimatedCursorY < threadRect.top + 40) {
                const diff = (threadRect.top + 40) - estimatedCursorY;
                thread.scrollTop -= diff;
            }
        }

        function adjustEditHeight() {
            const prevTaScrollTop = textarea.scrollTop;
            const prevThreadScrollTop = thread ? thread.scrollTop : 0;

            let mirror = document.getElementById('codebook-resize-mirror');
            if (!mirror) {
                mirror = document.createElement('textarea');
                mirror.id = 'codebook-resize-mirror';
                mirror.setAttribute('aria-hidden', 'true');
                mirror.setAttribute('tabindex', '-1');
                mirror.style.position = 'fixed';
                mirror.style.top = '-9999px';
                mirror.style.left = '-9999px';
                mirror.style.visibility = 'hidden';
                mirror.style.pointerEvents = 'none';
                mirror.style.zIndex = '-9999';
                mirror.style.opacity = '0';
                mirror.style.overflow = 'hidden';
                mirror.style.resize = 'none';
                document.body.appendChild(mirror);
            }

            const computed = window.getComputedStyle(textarea);
            mirror.style.boxSizing = computed.boxSizing;
            mirror.style.width = textarea.clientWidth + 'px';
            mirror.style.fontFamily = computed.fontFamily;
            mirror.style.fontSize = computed.fontSize;
            mirror.style.lineHeight = computed.lineHeight;
            mirror.style.padding = computed.padding;
            mirror.style.border = computed.border;
            mirror.style.letterSpacing = computed.letterSpacing;
            mirror.style.whiteSpace = 'pre-wrap';
            mirror.style.wordBreak = 'break-word';
            mirror.value = textarea.value;

            mirror.style.height = '0px';
            const targetHeight = Math.max(84, mirror.scrollHeight + 4);

            textarea.style.height = targetHeight + 'px';

            if (thread && thread.scrollTop !== prevThreadScrollTop) {
                thread.scrollTop = prevThreadScrollTop;
            }
            if (prevTaScrollTop > 0) {
                textarea.scrollTop = prevTaScrollTop;
            }
        }

        adjustEditHeight();
        textarea.addEventListener('input', adjustEditHeight);
        textarea.addEventListener('keyup', function (e) {
            if (e.key === 'Enter' || e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                ensureCursorVisible();
            }
        });

        function cancelEdit() {
            if (editBox.parentNode) {
                editBox.parentNode.removeChild(editBox);
            }
            mdWrap.style.display = '';
            tools.style.display = '';
            activeEditCancel = null;
        }

        activeEditCancel = cancelEdit;

        cancelBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            cancelEdit();
        });

        async function saveEdit() {
            const newContent = textarea.value.trim();
            if (!newContent) {
                alert('Pesan tidak boleh kosong.');
                textarea.focus();
                return;
            }

            if (newContent === (m.content || '').trim()) {
                cancelEdit();
                return;
            }

            saveBtn.disabled = true;
            cancelBtn.disabled = true;
            saveBtn.textContent = 'Menyimpan...';

            try {
                const res = await fetch(`/notes/${window.CURRENT_NOTE.id}/messages/${m.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        content: newContent
                    })
                });

                const data = await res.json();
                if (data.success && data.message) {
                    m.content = data.message.content;
                    m.updated_at = data.message.updated_at;
                    if (data.message.sent_time) {
                        m.sent_time = data.message.sent_time;
                    }
                    activeEditCancel = null;
                    renderAll();
                } else {
                    alert('Gagal menyimpan perubahan pesan.');
                    saveBtn.disabled = false;
                    cancelBtn.disabled = false;
                    saveBtn.textContent = 'Simpan';
                }
            } catch (err) {
                console.error('Error updating message:', err);
                alert('Gagal menghubungi server untuk menyimpan perubahan.');
                saveBtn.disabled = false;
                cancelBtn.disabled = false;
                saveBtn.textContent = 'Simpan';
            }
        }

        saveBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            saveEdit();
        });

        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                cancelEdit();
            } else if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                saveEdit();
            }
        });

        function focusAndPlaceCursor() {
            if (!textarea || !document.body.contains(textarea)) return;

            textarea.focus({ preventScroll: true });

            // Place cursor at the start (line 1, column 1) so it is always visible
            try {
                textarea.setSelectionRange(0, 0);
            } catch (err) {}
            textarea.scrollTop = 0;

            // Ensure the top of the edit box (where line 1 and cursor are) is visible in thread
            if (thread && typeof editBox.scrollIntoView === 'function') {
                const threadRect = thread.getBoundingClientRect();
                const editRect = editBox.getBoundingClientRect();
                if (editRect.top < threadRect.top || editRect.top > threadRect.bottom - 40) {
                    editBox.scrollIntoView({ behavior: 'auto', block: 'start' });
                }
            }
        }

        focusAndPlaceCursor();
        requestAnimationFrame(focusAndPlaceCursor);
    }

    // Format UTC ISO timestamp into viewer's local browser time
    function formatUtcToLocalTime(dateStr, fallbackStr) {
        if (!dateStr) return fallbackStr || '';
        let parsedStr = dateStr;
        if (typeof dateStr === 'string' && !dateStr.endsWith('Z') && !dateStr.includes('+')) {
            parsedStr = dateStr.replace(' ', 'T') + 'Z';
        }
        const d = new Date(parsedStr);
        if (isNaN(d.getTime())) return fallbackStr || '';
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Render all messages
    function renderAll() {
        if (!thread) return;
        activeEditCancel = null;
        thread.innerHTML = '';

        if (!window.CURRENT_NOTE) {
            if (noNoteState) {
                noNoteState.style.display = 'block';
                thread.appendChild(noNoteState);
            }
            if (composer) composer.style.display = 'none';
            return;
        }

        if (composer) composer.style.display = 'flex';
        if (noNoteState) noNoteState.style.display = 'none';

        if (messages.length === 0) {
            if (emptyState) {
                if (emptyStateText) {
                    emptyStateText.innerHTML = `Belum ada pesan di catatan <strong>"${window.CURRENT_NOTE.title}"</strong>. Tulis markdown di panel kanan, pilih pengirim, lalu kirim untuk melihat hasil render chat bubble.`;
                }
                emptyState.style.display = 'block';
                thread.appendChild(emptyState);
            }
            return;
        }

        messages.forEach(function (m) {
            const row = document.createElement('div');
            row.className = 'row ' + m.sender;

            const wrap = document.createElement('div');
            wrap.className = 'bubble-wrap';

            const meta = document.createElement('div');
            meta.className = 'meta';
            const displayTime = formatUtcToLocalTime(m.created_at, m.sent_time);
            const isEdited = m.updated_at && m.created_at && (new Date(m.updated_at).getTime() - new Date(m.created_at).getTime() > 1000);
            const editedBadge = isEdited ? '<span class="edited-tag" title="Pesan telah diedit">(diedit)</span>' : '';
            meta.innerHTML = '<span class="who">' + (m.sender === 'me' ? 'Me' : 'Other') + '</span><span>' + displayTime + '</span>' + editedBadge;

            const bubble = document.createElement('div');
            bubble.className = 'bubble';

            const mdWrap = document.createElement('div');
            mdWrap.className = 'md-wrap';

            const mdDiv = document.createElement('div');
            mdDiv.className = 'md';
            mdDiv.id = 'md-' + m.id;
            const rawHtml = marked.parse(m.content || '');
            mdDiv.innerHTML = DOMPurify.sanitize(rawHtml);
            mdDiv.querySelectorAll('pre code').forEach(function (block) {
                hljs.highlightElement(block);
            });
            setupCodeBlocks(mdDiv);

            const fade = document.createElement('div');
            fade.className = 'fade';
            fade.id = 'fade-' + m.id;

            mdWrap.appendChild(mdDiv);
            mdWrap.appendChild(fade);
            bubble.appendChild(mdWrap);

            const contentStr = m.content || '';
            const lineCount = contentStr.split('\n').length;
            const charCount = contentStr.length;

            const tools = document.createElement('div');
            tools.className = 'bubble-tools';
            tools.innerHTML =
                '<span class="linecount">' + lineCount + ' baris &middot; ' + charCount + ' karakter</span>' +
                '<div style="display:flex;align-items:center;gap:8px;">' +
                '<button class="toggle-btn" id="toggle-' + m.id + '">&#8964; tampilkan semua</button>' +
                '<button type="button" class="edit-btn" title="Edit teks pesan" data-id="' + m.id + '" aria-label="Edit pesan">' +
                '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>' +
                '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>' +
                '</svg>' +
                '</button>' +
                '<button type="button" class="del-btn" title="Hapus pesan" data-id="' + m.id + '" aria-label="Hapus pesan">&times;</button>' +
                '</div>';

            bubble.appendChild(tools);
            wrap.appendChild(meta);
            wrap.appendChild(bubble);
            row.appendChild(wrap);
            thread.appendChild(row);

            const editBtn = tools.querySelector('.edit-btn');
            if (editBtn) {
                editBtn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                });
                editBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    startEditMessage(m, bubble, mdWrap, tools);
                });
            }

            bubble.addEventListener('dblclick', function (e) {
                if (e.target.closest('pre, code, a, button, input, textarea, .code-block-tools, .bubble-tools, .message-edit-box')) {
                    return;
                }
                if (window.getSelection) {
                    window.getSelection().removeAllRanges();
                }
                startEditMessage(m, bubble, mdWrap, tools);
            });

            tools.querySelector('.del-btn').addEventListener('click', function () {
                window.deleteMessage(m.id);
            });

            setTimeout(function () {
                const toggleBtn = document.getElementById('toggle-' + m.id);
                if (mdDiv.scrollHeight > 340) {
                    mdDiv.classList.add('collapsed');
                    fade.style.display = 'block';
                    if (toggleBtn) {
                        toggleBtn.style.display = 'flex';
                        toggleBtn.addEventListener('click', function () { toggleCollapse(m.id); });
                    }
                }
            }, 0);
        });
    }

    // SPA Note Selection (No full page reload)
    window.selectNote = async function (noteId, e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight sidebar active item immediately
        document.querySelectorAll('.tree-note').forEach(el => el.classList.remove('active'));
        const activeLink = document.querySelector(`.tree-note[data-note-id="${noteId}"]`);
        if (activeLink) activeLink.classList.add('active');

        try {
            const res = await fetch(`/notes/${noteId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success && data.note) {
                window.CURRENT_NOTE = data.note;
                messages = data.note.messages || [];

                if (window.updateActiveNoteVisibilityUI) {
                    window.updateActiveNoteVisibilityUI(data.note);
                }

                if (noteTitleInput) {
                    noteTitleInput.value = data.note.title;
                    originalNoteTitle = data.note.title;
                    autoResizeNoteTitle();
                    updateTitleCharCount(data.note.title.length);
                }

                // Update URL cleanly without reloading
                window.history.pushState({ noteId: data.note.id }, '', `/?note=${data.note.id}`);
                document.title = `Codebook - ${data.note.title}`;

                renderAll();
                thread.scrollTop = thread.scrollHeight;

                if (window.innerWidth <= 768) {
                    toggleSidebar(true);
                }
            }
        } catch (err) {
            console.error('Failed to load note:', err);
        }
    };

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const noteId = urlParams.get('note');
        if (noteId) {
            window.selectNote(noteId);
        }
    });

    // Debounced Note Title Editing
    window.onNoteTitleInput = function (newTitle) {
        autoResizeNoteTitle();
        updateTitleCharCount(newTitle.length);
        if (!window.CURRENT_NOTE) return;

        // Update title in sidebar immediately
        const activeNav = document.querySelector(`.tree-note[data-note-id="${window.CURRENT_NOTE.id}"] .note-title-text`);
        if (activeNav) {
            activeNav.textContent = newTitle.trim() || originalNoteTitle || 'Untitled Note';
        }

        clearTimeout(titleDebounceTimeout);
        titleDebounceTimeout = setTimeout(async () => {
            const trimmed = noteTitleInput ? noteTitleInput.value.trim() : newTitle.trim();
            if (trimmed && trimmed !== originalNoteTitle) {
                originalNoteTitle = trimmed;
                window.CURRENT_NOTE.title = trimmed;
                await persistNoteTitle(window.CURRENT_NOTE.id, trimmed);
            }
        }, 1000);
    };

    // Delete Current Note
    window.deleteCurrentNote = async function () {
        if (!window.CURRENT_NOTE) return;

        const noteTitle = window.CURRENT_NOTE.title || 'ini';
        const confirmed = await window.showConfirmModal({
            title: 'Hapus Catatan',
            message: `Apakah Anda yakin ingin menghapus catatan "${noteTitle}"? Semua pesan di dalamnya akan dihapus secara permanen.`,
            confirmText: 'Hapus Catatan',
            cancelText: 'Batal',
            danger: true
        });
        if (!confirmed) return;

        const noteId = window.CURRENT_NOTE.id;
        try {
            const res = await fetch(`/notes/${noteId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const data = await res.json();
            if (data.success) {
                // Remove from sidebar DOM
                const navItem = document.querySelector(`.tree-note[data-note-id="${noteId}"]`);
                if (navItem) navItem.remove();

                // Select another note if available
                const nextNote = document.querySelector('.tree-note');
                if (nextNote) {
                    const nextId = nextNote.getAttribute('data-note-id');
                    window.selectNote(nextId);
                } else {
                    window.CURRENT_NOTE = null;
                    messages = [];
                    if (noteTitleInput) {
                        noteTitleInput.value = '';
                        originalNoteTitle = '';
                        autoResizeNoteTitle();
                        updateTitleCharCount(0);
                    }
                    window.history.pushState({}, '', '/');
                    renderAll();
                }
            }
        } catch (e) {
            console.error('Failed to delete note', e);
        }
    };

    // Initial render on page load
    if (window.CURRENT_NOTE) {
        if (window.updateActiveNoteVisibilityUI) {
            window.updateActiveNoteVisibilityUI(window.CURRENT_NOTE);
        }
        renderAll();
        setTimeout(function () {
            thread.scrollTop = thread.scrollHeight;
        }, 100);
    } else {
        renderAll();
    }
});

// Delete Note from sidebar item
async function deleteNote(noteId) {
    const navItem = document.querySelector(`.tree-note[data-note-id="${noteId}"]`);
    const noteTitle = navItem?.querySelector('.note-title-text')?.textContent.trim() 
        || (window.CURRENT_NOTE && window.CURRENT_NOTE.id == noteId ? window.CURRENT_NOTE.title : '');

    const messageText = noteTitle 
        ? `Apakah Anda yakin ingin menghapus catatan "${noteTitle}"? Semua pesan di dalamnya akan dihapus secara permanen.`
        : 'Apakah Anda yakin ingin menghapus catatan ini? Semua pesan di dalamnya akan dihapus secara permanen.';

    const confirmed = await window.showConfirmModal({
        title: 'Hapus Catatan',
        message: messageText,
        confirmText: 'Hapus Catatan',
        cancelText: 'Batal',
        danger: true
    });
    if (!confirmed) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/notes/${noteId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        const data = await res.json();
        if (data.success) {
            const parentGroup = navItem ? navItem.closest('.tree-folder-group') : null;
            if (navItem) navItem.remove();

            if (parentGroup) {
                checkAndUpdateFolderEmptyState(parentGroup);
            }

            if (window.CURRENT_NOTE && window.CURRENT_NOTE.id == noteId) {
                const nextNote = document.querySelector('.tree-note');
                if (nextNote) {
                    window.selectNote(nextNote.getAttribute('data-note-id'));
                } else {
                    window.CURRENT_NOTE = null;
                    window.INITIAL_MESSAGES = [];
                    window.history.pushState({}, '', '/');
                    location.reload();
                }
            }
        }
    } catch (e) {
        console.error('Failed to delete note', e);
    }
}
window.deleteNote = deleteNote;

// Delete Folder
async function deleteFolder(folderId) {
    const folderGroup = document.querySelector(`.tree-folder-group[data-folder-id="${folderId}"]`);
    const folderName = folderGroup?.querySelector('.folder-name')?.textContent.trim() || '';

    const messageText = folderName 
        ? `Apakah Anda yakin ingin menghapus folder "${folderName}" beserta semua subfolder dan catatan di dalamnya? Tindakan ini tidak dapat dibatalkan.`
        : 'Hapus folder ini dan semua catatan di dalamnya? Tindakan ini tidak dapat dibatalkan.';

    const confirmed = await window.showConfirmModal({
        title: 'Hapus Folder',
        message: messageText,
        confirmText: 'Hapus Folder',
        cancelText: 'Batal',
        danger: true
    });
    if (!confirmed) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/folders/${folderId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        const data = await res.json();
        if (data.success) {
            const parentGroup = folderGroup?.parentElement?.closest('.tree-folder-group');
            if (folderGroup) folderGroup.remove();

            if (parentGroup) {
                checkAndUpdateFolderEmptyState(parentGroup);
            }

            // Also remove from selects
            const opt1 = document.querySelector(`#folder-parent-select option[value="${folderId}"]`);
            if (opt1) opt1.remove();
            const opt2 = document.querySelector(`#note-folder-select option[value="${folderId}"]`);
            if (opt2) opt2.remove();

            // If active note was in this folder, reload or select next available
            const activeNoteInDom = document.querySelector(`.tree-note.active`);
            if (!activeNoteInDom) {
                const nextNote = document.querySelector('.tree-note');
                if (nextNote) {
                    window.selectNote(nextNote.getAttribute('data-note-id'));
                } else {
                    window.CURRENT_NOTE = null;
                    window.history.pushState({}, '', '/');
                    location.reload();
                }
            }
        }
    } catch (e) {
        console.error('Failed to delete folder', e);
    }
}

// Toggle folder expansion state
function toggleFolder(el, event) {
    if (!el) return;
    el.classList.toggle('collapsed');
    updateFolderToggleState(el);
}

function updateFolderToggleState(folderHeader) {
    if (!folderHeader) return;
    const isCollapsed = folderHeader.classList.contains('collapsed');
    const toggleBtn = folderHeader.querySelector('.folder-toggle-btn');
    const iconEl = folderHeader.querySelector('.folder-icon');

    if (toggleBtn) {
        toggleBtn.setAttribute('title', isCollapsed ? 'Buka folder' : 'Tutup folder');
        toggleBtn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    }

    if (iconEl) {
        iconEl.innerHTML = isCollapsed ? '&#128193;' : '&#128194;';
    }
}

function expandAllFolders() {
    document.querySelectorAll('.tree-folder').forEach(folder => {
        folder.classList.remove('collapsed');
        updateFolderToggleState(folder);
    });
}

function collapseAllFolders() {
    document.querySelectorAll('.tree-folder').forEach(folder => {
        folder.classList.add('collapsed');
        updateFolderToggleState(folder);
    });
}

function checkAndUpdateFolderEmptyState(groupEl) {
    if (!groupEl) return;
    const childrenContainer = groupEl.querySelector(':scope > .tree-children');
    if (!childrenContainer) return;

    const hasSubfolders = childrenContainer.querySelector(':scope > .tree-folder-group') !== null;
    const hasNotes = childrenContainer.querySelector(':scope > .tree-note') !== null;
    const existingNotice = childrenContainer.querySelector(':scope > .tree-empty-notice');

    if (!hasSubfolders && !hasNotes) {
        if (!existingNotice) {
            const notice = document.createElement('div');
            notice.className = 'tree-empty-notice';
            notice.textContent = '(kosong)';
            childrenContainer.appendChild(notice);
        }
    } else {
        if (existingNotice) {
            existingNotice.remove();
        }
    }
}

window.toggleFolder = toggleFolder;
window.updateFolderToggleState = updateFolderToggleState;
window.expandAllFolders = expandAllFolders;
window.collapseAllFolders = collapseAllFolders;
window.checkAndUpdateFolderEmptyState = checkAndUpdateFolderEmptyState;

// Filter Sidebar Tree
function filterTree(query) {
    const q = query.toLowerCase().trim();
    const notes = document.querySelectorAll('.tree-note');
    const emptyNotices = document.querySelectorAll('.tree-empty-notice');
    const clearBtn = document.getElementById('search-clear-btn');

    if (clearBtn) {
        clearBtn.style.display = query ? 'flex' : 'none';
    }

    if (!q) {
        notes.forEach(el => el.style.display = '');
        emptyNotices.forEach(el => el.style.display = '');
        return;
    }

    emptyNotices.forEach(el => el.style.display = 'none');
    notes.forEach(note => {
        const title = note.querySelector('.note-title-text')?.textContent.toLowerCase() || '';
        const match = title.includes(q);
        note.style.display = match ? 'flex' : 'none';
    });
}

function clearSearch() {
    const input = document.getElementById('tree-search');
    if (input) {
        input.value = '';
        filterTree('');
        input.focus();
    }
}

window.clearSearch = clearSearch;

// Modals
function openNewFolderModal(parentId = null) {
    const parentInput = document.getElementById('folder-parent-id');
    const parentSelect = document.getElementById('folder-parent-select');
    const modalTitle = document.getElementById('folder-modal-title');

    if (parentInput) parentInput.value = parentId ? String(parentId) : '';
    if (parentSelect) parentSelect.value = parentId ? String(parentId) : '';

    if (modalTitle) {
        modalTitle.textContent = parentId ? 'Subfolder Baru' : 'Folder Baru';
    }

    document.getElementById('folder-name-input').value = '';
    document.getElementById('newFolderModal').classList.add('active');
    setTimeout(() => document.getElementById('folder-name-input').focus(), 50);
}

function openNewNoteModal(folderId = null) {
    document.getElementById('note-title-modal-input').value = '';
    const select = document.getElementById('note-folder-select');
    if (select && folderId) {
        select.value = folderId;
    }
    document.getElementById('newNoteModal').classList.add('active');
    setTimeout(() => document.getElementById('note-title-modal-input').focus(), 50);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        if (modalId === 'confirmModal' && activeConfirmCallback) {
            activeConfirmCallback(false);
            activeConfirmCallback = null;
        }
    }
}

// Global modal backdrop click & Escape key listener for standard modals
document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('modal-overlay') && e.target.id !== 'confirmModal') {
        closeModal(e.target.id);
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal && activeModal.id !== 'confirmModal') {
            closeModal(activeModal.id);
        }
    }
});

// Submit New Folder via SPA (no full reload)
async function submitNewFolder(e) {
    e.preventDefault();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const name = document.getElementById('folder-name-input').value;
    const parentSelect = document.getElementById('folder-parent-select');
    const parentInput = document.getElementById('folder-parent-id');
    const parentId = (parentSelect ? parentSelect.value : (parentInput ? parentInput.value : '')) || null;

    try {
        const res = await fetch('/folders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ name, parent_id: parentId ? parseInt(parentId, 10) : null })
        });
        const data = await res.json();
        if (data.success && data.folder) {
            closeModal('newFolderModal');
            const depth = data.depth || 1;

            // Dynamically inject folder into tree
            const tree = document.getElementById('sidebar-tree');
            const noNotice = document.getElementById('no-items-notice');
            if (noNotice) noNotice.remove();

            const folderHtml = `
                <div class="tree-folder-group" data-folder-id="${data.folder.id}" data-depth="${depth}">
                    <div class="tree-folder" draggable="true" data-folder-id="${data.folder.id}" onclick="toggleFolder(this, event)">
                        <div class="tree-label">
                            <button type="button" class="folder-toggle-btn" title="Tutup folder" aria-expanded="true" onclick="event.stopPropagation(); toggleFolder(this.closest('.tree-folder'), event)">
                                <svg class="folder-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                            <span class="tree-icon folder-icon">&#128194;</span>
                            <span class="folder-name">${data.folder.name}</span>
                        </div>
                        <div class="item-actions" onclick="event.stopPropagation()">
                            ${depth < 10 ? `<button class="mini-btn" title="Tambah Subfolder" onclick="openNewFolderModal(${data.folder.id})">&#43; folder</button>` : ''}
                            <button class="mini-btn" title="Tambah Catatan di Folder ini" onclick="openNewNoteModal(${data.folder.id})">&#43; note</button>
                            <button class="mini-btn danger" title="Hapus Folder" onclick="deleteFolder(${data.folder.id})">&times;</button>
                        </div>
                    </div>
                    <div class="tree-children">
                        <div class="tree-empty-notice">(kosong)</div>
                    </div>
                </div>
            `;

            if (parentId) {
                const parentGroup = document.querySelector(`.tree-folder-group[data-folder-id="${parentId}"]`);
                if (parentGroup) {
                    let childrenContainer = parentGroup.querySelector('.tree-children');
                    if (!childrenContainer) {
                        childrenContainer = document.createElement('div');
                        childrenContainer.className = 'tree-children';
                        parentGroup.appendChild(childrenContainer);
                    }

                    // Remove empty notice from parent if present
                    const parentEmptyNotice = childrenContainer.querySelector(':scope > .tree-empty-notice');
                    if (parentEmptyNotice) {
                        parentEmptyNotice.remove();
                    }

                    // Ensure folder is placed at the top (before any notes in this folder)
                    const firstNote = childrenContainer.querySelector(':scope > .tree-note');
                    if (firstNote) {
                        firstNote.insertAdjacentHTML('beforebegin', folderHtml);
                    } else {
                        childrenContainer.insertAdjacentHTML('beforeend', folderHtml);
                    }

                    const parentHeader = parentGroup.querySelector('.tree-folder');
                    if (parentHeader) {
                        parentHeader.classList.remove('collapsed');
                        updateFolderToggleState(parentHeader);
                    }
                }
            } else {
                const container = document.getElementById('folders-container') || tree;
                container.insertAdjacentHTML('afterbegin', folderHtml);
            }

            // Also add option to folder-parent-select and note-folder-select
            const indent = '&nbsp;&nbsp;&nbsp;'.repeat(Math.max(0, depth - 1));
            const prefix = depth > 1 ? '└─ ' : '';

            const folderSelect = document.getElementById('folder-parent-select');
            if (folderSelect) {
                const opt = document.createElement('option');
                opt.value = data.folder.id;
                opt.setAttribute('data-depth', depth);
                opt.innerHTML = `${indent}${prefix}${data.folder.name}${depth >= 10 ? ' (Maksimal level 10)' : ''}`;
                if (depth >= 10) opt.disabled = true;
                folderSelect.appendChild(opt);
            }

            const noteSelect = document.getElementById('note-folder-select');
            if (noteSelect) {
                const opt = document.createElement('option');
                opt.value = data.folder.id;
                opt.innerHTML = `${indent}${prefix}${data.folder.name}`;
                noteSelect.appendChild(opt);
            }
        } else {
            alert(data.message || 'Gagal membuat folder.');
        }
    } catch (err) {
        alert('Gagal membuat folder.');
    }
}

// Submit New Note via SPA (no full reload)
async function submitNewNote(e) {
    e.preventDefault();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const title = document.getElementById('note-title-modal-input').value;
    const folderId = document.getElementById('note-folder-select').value;
    const isPublic = document.getElementById('note-is-public-select')?.value === '1';

    try {
        const res = await fetch('/notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ title, folder_id: folderId || null, mode: 'markdown', is_public: isPublic })
        });
        const data = await res.json();
        if (data.success && data.note) {
            closeModal('newNoteModal');

            const noNotice = document.getElementById('no-items-notice');
            if (noNotice) noNotice.remove();

            const noteHtml = `
                <a href="/?note=${data.note.id}" 
                   class="tree-note active" 
                   draggable="true"
                   data-note-id="${data.note.id}"
                   ${folderId ? `data-folder-id="${folderId}"` : ''}
                   onclick="selectNote(${data.note.id}, event)">
                    <div class="tree-label">
                        <span class="tree-icon">&#128221;</span>
                        <span class="note-title-text">${data.note.title}</span>
                        <span class="note-public-icon" id="tree-note-public-${data.note.id}" title="Catatan Publik" style="${data.note.is_public ? 'font-size:10px;margin-left:4px;opacity:0.8;' : 'display:none;font-size:10px;margin-left:4px;opacity:0.8;'}">&#127760;</span>
                    </div>
                    <div class="item-actions" onclick="event.preventDefault(); event.stopPropagation()">
                        <button class="mini-btn danger" title="Hapus Catatan" onclick="deleteNote(${data.note.id})">&times;</button>
                    </div>
                </a>
            `;

            if (folderId) {
                const parentGroup = document.querySelector(`.tree-folder-group[data-folder-id="${folderId}"]`);
                if (parentGroup) {
                    let folderChildren = parentGroup.querySelector('.tree-children');
                    if (!folderChildren) {
                        folderChildren = document.createElement('div');
                        folderChildren.className = 'tree-children';
                        parentGroup.appendChild(folderChildren);
                    }

                    // Remove empty notice if present
                    const emptyNotice = folderChildren.querySelector(':scope > .tree-empty-notice');
                    if (emptyNotice) {
                        emptyNotice.remove();
                    }

                    // Append note below subfolders
                    folderChildren.insertAdjacentHTML('beforeend', noteHtml);

                    const parentHeader = parentGroup.querySelector('.tree-folder');
                    if (parentHeader) {
                        parentHeader.classList.remove('collapsed');
                        updateFolderToggleState(parentHeader);
                    }
                }
            } else {
                const rootSection = document.getElementById('root-notes-section');
                if (rootSection) rootSection.style.display = 'block';
                const container = document.getElementById('root-notes-container') || document.getElementById('sidebar-tree');
                container.insertAdjacentHTML('beforeend', noteHtml);
            }

            // Select this newly created note in-place without page reload
            window.selectNote(data.note.id);
        }
    } catch (err) {
        alert('Gagal membuat catatan.');
    }
}

// -----------------------------------------------------------------------------
// Share Note Modal & Actions
// -----------------------------------------------------------------------------
function updateActiveNoteVisibilityUI(note) {
    if (!note) return;
    const badge = document.getElementById('active-note-visibility-badge');
    if (badge) {
        if (note.is_public) {
            badge.textContent = 'Publik';
            badge.style.background = 'rgba(34,197,94,0.18)';
            badge.style.color = '#22c55e';
            badge.style.borderColor = 'rgba(34,197,94,0.3)';
        } else {
            badge.textContent = 'Pribadi';
            badge.style.background = 'var(--panel-2)';
            badge.style.color = 'var(--muted)';
            badge.style.borderColor = 'var(--border)';
        }
    }

    // Update tree item indicator
    const treeIcon = document.getElementById(`tree-note-public-${note.id}`);
    if (treeIcon) {
        treeIcon.style.display = note.is_public ? 'inline' : 'none';
    }
}
window.updateActiveNoteVisibilityUI = updateActiveNoteVisibilityUI;

function updateShareModalVisibilityUI(isPublic) {
    const privBtn = document.getElementById('share-visibility-private');
    const pubBtn = document.getElementById('share-visibility-public');
    const desc = document.getElementById('share-visibility-desc');

    if (isPublic) {
        if (pubBtn) {
            pubBtn.style.background = 'rgba(34,197,94,0.15)';
            pubBtn.style.color = '#22c55e';
            pubBtn.style.borderColor = 'rgba(34,197,94,0.35)';
            pubBtn.style.fontWeight = '600';
        }
        if (privBtn) {
            privBtn.style.background = 'var(--panel-2)';
            privBtn.style.color = 'var(--muted)';
            privBtn.style.borderColor = 'var(--border)';
            privBtn.style.fontWeight = 'normal';
        }
        if (desc) {
            desc.innerHTML = '<strong style="color:#22c55e;">&#127760; Publik:</strong> Siapa saja yang memiliki tautan ini dapat melihat seluruh catatan dan pesan tanpa perlu login.';
        }
    } else {
        if (privBtn) {
            privBtn.style.background = 'rgba(234,179,8,0.15)';
            privBtn.style.color = '#eab308';
            privBtn.style.borderColor = 'rgba(234,179,8,0.35)';
            privBtn.style.fontWeight = '600';
        }
        if (pubBtn) {
            pubBtn.style.background = 'var(--panel-2)';
            pubBtn.style.color = 'var(--muted)';
            pubBtn.style.borderColor = 'var(--border)';
            pubBtn.style.fontWeight = 'normal';
        }
        if (desc) {
            desc.innerHTML = '<strong style="color:#eab308;">&#128274; Pribadi:</strong> Hanya Anda yang dapat melihat catatan ini. Pengguna lain tanpa login atau akun lain tidak dapat melihatnya.';
        }
    }
}

window.openShareModal = function () {
    if (!window.CURRENT_NOTE) return;
    const note = window.CURRENT_NOTE;

    const titleEl = document.getElementById('share-modal-note-title');
    if (titleEl) titleEl.textContent = `Catatan: "${note.title}"`;

    const shareUrl = note.share_url || (window.location.origin + '/share/' + (note.share_token || ''));
    const input = document.getElementById('share-url-input');
    if (input) input.value = shareUrl;

    const openLink = document.getElementById('open-share-link');
    if (openLink) openLink.href = shareUrl;

    updateShareModalVisibilityUI(note.is_public);

    const modal = document.getElementById('shareModal');
    if (modal) modal.classList.add('active');
};

window.setNoteVisibility = async function (isPublic) {
    if (!window.CURRENT_NOTE) return;
    const noteId = window.CURRENT_NOTE.id;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    updateShareModalVisibilityUI(isPublic);

    try {
        const res = await fetch(`/notes/${noteId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ is_public: isPublic })
        });
        const data = await res.json();
        if (data.success && data.note) {
            window.CURRENT_NOTE.is_public = Boolean(isPublic);
            updateActiveNoteVisibilityUI(window.CURRENT_NOTE);
        } else {
            updateShareModalVisibilityUI(window.CURRENT_NOTE.is_public);
            alert('Gagal mengubah visibilitas catatan.');
        }
    } catch (err) {
        updateShareModalVisibilityUI(window.CURRENT_NOTE.is_public);
        alert('Gagal mengubah visibilitas catatan.');
    }
};

window.copyShareUrl = function () {
    const input = document.getElementById('share-url-input');
    if (!input || !input.value) return;

    const copyBtnText = document.getElementById('copy-btn-text');

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(showDone).catch(fallback);
    } else {
        fallback();
    }

    function fallback() {
        input.select();
        try {
            document.execCommand('copy');
            showDone();
        } catch (e) {
            alert('Silakan salin tautan secara manual: ' + input.value);
        }
    }

    function showDone() {
        if (copyBtnText) copyBtnText.textContent = '✓ Tersalin!';
        setTimeout(() => {
            if (copyBtnText) copyBtnText.textContent = 'Salin Tautan';
        }, 2000);
    }
};

window.regenerateShareLink = async function () {
    if (!window.CURRENT_NOTE) return;
    const noteId = window.CURRENT_NOTE.id;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const confirmed = await window.showConfirmModal({
        title: 'Buat Ulang Tautan Share',
        message: 'Tautan lama akan langsung tidak berlaku dan diganti dengan tautan baru yang acak. Apakah Anda yakin?',
        confirmText: 'Buat Ulang',
        cancelText: 'Batal',
        danger: false
    });

    if (!confirmed) return;

    try {
        const res = await fetch(`/notes/${noteId}/share-token`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        const data = await res.json();
        if (data.success && data.share_url) {
            window.CURRENT_NOTE.share_token = data.share_token;
            window.CURRENT_NOTE.share_url = data.share_url;

            const input = document.getElementById('share-url-input');
            if (input) input.value = data.share_url;

            const openLink = document.getElementById('open-share-link');
            if (openLink) openLink.href = data.share_url;

            const copyBtnText = document.getElementById('copy-btn-text');
            if (copyBtnText) {
                copyBtnText.textContent = '✓ Tautan Baru Dibuat!';
                setTimeout(() => {
                    copyBtnText.textContent = 'Salin Tautan';
                }, 2000);
            }
        }
    } catch (err) {
        alert('Gagal membuat ulang tautan share.');
    }
};

// -----------------------------------------------------------------------------
// Drag & Drop Functionality for Sidebar Tree
// -----------------------------------------------------------------------------
let draggedItem = null;
let folderExpandTimeout = null;

function initSidebarDragAndDrop() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    // Drag Start
    sidebar.addEventListener('dragstart', function (e) {
        // Prevent drag when clicking action buttons
        if (e.target.closest('.item-actions') || e.target.closest('button')) {
            e.preventDefault();
            return;
        }

        const noteEl = e.target.closest('.tree-note');
        const folderHeader = e.target.closest('.tree-folder');

        if (noteEl) {
            draggedItem = {
                type: 'note',
                id: noteEl.getAttribute('data-note-id'),
                el: noteEl,
                folderId: noteEl.getAttribute('data-folder-id') || null
            };
            noteEl.classList.add('is-dragging');
            document.body.classList.add('is-drag-active');
            e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'note', id: draggedItem.id }));
            e.dataTransfer.effectAllowed = 'move';
            return;
        }

        if (folderHeader) {
            const folderGroup = folderHeader.closest('.tree-folder-group');
            if (!folderGroup) return;

            draggedItem = {
                type: 'folder',
                id: folderHeader.getAttribute('data-folder-id'),
                el: folderGroup,
                headerEl: folderHeader
            };
            folderGroup.classList.add('is-dragging');
            document.body.classList.add('is-drag-active');
            e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'folder', id: draggedItem.id }));
            e.dataTransfer.effectAllowed = 'move';
        }
    });

    // Drag End
    sidebar.addEventListener('dragend', function () {
        if (draggedItem && draggedItem.el) {
            draggedItem.el.classList.remove('is-dragging');
        }
        document.body.classList.remove('is-drag-active');
        document.querySelectorAll('.drag-target-hover').forEach(el => el.classList.remove('drag-target-hover'));
        if (folderExpandTimeout) {
            clearTimeout(folderExpandTimeout);
            folderExpandTimeout = null;
        }
        draggedItem = null;
    });

    // Drag Over
    sidebar.addEventListener('dragover', function (e) {
        if (!draggedItem) return;

        const targetFolder = e.target.closest('.tree-folder');
        const targetNote = e.target.closest('.tree-note');
        const rootDropzone = e.target.closest('#root-dropzone');
        const rootSection = e.target.closest('#root-notes-section');
        const foldersContainer = e.target.closest('#folders-container');

        if (targetFolder) {
            const targetId = targetFolder.getAttribute('data-folder-id');
            // Folder cannot be dropped onto itself or its own subfolder
            if (draggedItem.type === 'folder') {
                if (draggedItem.id === targetId || draggedItem.el.contains(targetFolder)) {
                    return;
                }
            }

            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            if (!targetFolder.classList.contains('drag-target-hover')) {
                document.querySelectorAll('.drag-target-hover').forEach(el => el.classList.remove('drag-target-hover'));
                targetFolder.classList.add('drag-target-hover');
            }

            // Auto-expand collapsed folder on prolonged hover
            if (targetFolder.classList.contains('collapsed') && !folderExpandTimeout) {
                folderExpandTimeout = setTimeout(() => {
                    targetFolder.classList.remove('collapsed');
                }, 550);
            }
            return;
        }

        if (targetNote && draggedItem.type === 'note' && targetNote !== draggedItem.el) {
            // Dragging note over another note
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            return;
        }

        if (rootDropzone || rootSection || (foldersContainer && draggedItem.type === 'folder')) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            const dropTarget = rootDropzone || document.getElementById('root-dropzone');
            if (dropTarget && !dropTarget.classList.contains('drag-target-hover')) {
                document.querySelectorAll('.drag-target-hover').forEach(el => el.classList.remove('drag-target-hover'));
                dropTarget.classList.add('drag-target-hover');
            }
        }
    });

    // Drag Leave
    sidebar.addEventListener('dragleave', function (e) {
        const targetFolder = e.target.closest('.tree-folder');
        const rootDropzone = e.target.closest('#root-dropzone');

        if (targetFolder && !targetFolder.contains(e.relatedTarget)) {
            targetFolder.classList.remove('drag-target-hover');
            if (folderExpandTimeout) {
                clearTimeout(folderExpandTimeout);
                folderExpandTimeout = null;
            }
        }

        if (rootDropzone && !rootDropzone.contains(e.relatedTarget)) {
            rootDropzone.classList.remove('drag-target-hover');
        }
    });

    // Drop
    sidebar.addEventListener('drop', function (e) {
        if (!draggedItem) return;

        e.preventDefault();
        e.stopPropagation();

        const targetFolder = e.target.closest('.tree-folder');
        const targetNote = e.target.closest('.tree-note');
        const rootDropzone = e.target.closest('#root-dropzone');
        const rootSection = e.target.closest('#root-notes-section');
        const foldersContainer = e.target.closest('#folders-container');

        document.querySelectorAll('.drag-target-hover').forEach(el => el.classList.remove('drag-target-hover'));
        if (folderExpandTimeout) {
            clearTimeout(folderExpandTimeout);
            folderExpandTimeout = null;
        }

        if (targetFolder) {
            const targetFolderId = targetFolder.getAttribute('data-folder-id');
            if (draggedItem.type === 'note') {
                if (draggedItem.folderId != targetFolderId) {
                    moveNoteToFolder(draggedItem.id, targetFolderId);
                }
            } else if (draggedItem.type === 'folder') {
                if (draggedItem.id !== targetFolderId && !draggedItem.el.contains(targetFolder)) {
                    moveFolderToParent(draggedItem.id, targetFolderId);
                }
            }
            return;
        }

        if (targetNote && draggedItem.type === 'note' && targetNote !== draggedItem.el) {
            const targetFolderId = targetNote.getAttribute('data-folder-id') || null;
            if (draggedItem.folderId != targetFolderId) {
                moveNoteToFolder(draggedItem.id, targetFolderId);
            }
            return;
        }

        if (rootDropzone || rootSection || (foldersContainer && draggedItem.type === 'folder')) {
            if (draggedItem.type === 'note') {
                if (draggedItem.folderId !== null && draggedItem.folderId !== '') {
                    moveNoteToFolder(draggedItem.id, null);
                }
            } else if (draggedItem.type === 'folder') {
                moveFolderToParent(draggedItem.id, null);
            }
        }
    });
}

// Move Note API & DOM Update
async function moveNoteToFolder(noteId, targetFolderId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const noteEl = document.querySelector(`.tree-note[data-note-id="${noteId}"]`);
    if (!noteEl) return;

    try {
        const res = await fetch(`/notes/${noteId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ folder_id: targetFolderId ? parseInt(targetFolderId, 10) : null })
        });

        const data = await res.json();
        if (data.success) {
            const oldFolderId = noteEl.getAttribute('data-folder-id');
            const oldParentGroup = oldFolderId ? document.querySelector(`.tree-folder-group[data-folder-id="${oldFolderId}"]`) : null;

            if (targetFolderId) {
                noteEl.setAttribute('data-folder-id', targetFolderId);
                const targetGroup = document.querySelector(`.tree-folder-group[data-folder-id="${targetFolderId}"]`);
                if (targetGroup) {
                    let children = targetGroup.querySelector('.tree-children');
                    if (!children) {
                        children = document.createElement('div');
                        children.className = 'tree-children';
                        targetGroup.appendChild(children);
                    }
                    const emptyNotice = children.querySelector(':scope > .tree-empty-notice');
                    if (emptyNotice) emptyNotice.remove();

                    // Notes are appended below any subfolders
                    children.appendChild(noteEl);

                    const folderHeader = targetGroup.querySelector('.tree-folder');
                    if (folderHeader) {
                        folderHeader.classList.remove('collapsed');
                        updateFolderToggleState(folderHeader);
                    }
                }
            } else {
                noteEl.removeAttribute('data-folder-id');
                const rootSection = document.getElementById('root-notes-section');
                const rootContainer = document.getElementById('root-notes-container');
                if (rootSection) rootSection.style.display = 'block';
                if (rootContainer) rootContainer.appendChild(noteEl);
            }

            if (oldParentGroup) {
                checkAndUpdateFolderEmptyState(oldParentGroup);
            }

            // Check if root notes section is now empty
            const rootContainer = document.getElementById('root-notes-container');
            const rootSection = document.getElementById('root-notes-section');
            if (rootContainer && rootSection && rootContainer.children.length === 0) {
                rootSection.style.display = 'none';
            }

            noteEl.classList.add('item-moved-highlight');
            setTimeout(() => noteEl.classList.remove('item-moved-highlight'), 800);
        } else {
            alert(data.message || 'Gagal memindahkan catatan.');
        }
    } catch (err) {
        console.error('Failed to move note:', err);
        alert('Gagal memindahkan catatan.');
    }
}

// Recursively update depth attribute and + folder button on tree groups
function updateGroupDepth(groupEl, newDepth) {
    if (!groupEl) return;
    groupEl.setAttribute('data-depth', newDepth);

    const folderHeader = groupEl.querySelector(':scope > .tree-folder');
    if (folderHeader) {
        const actions = folderHeader.querySelector('.item-actions');
        if (actions) {
            let addFolderBtn = actions.querySelector('button[title="Tambah Subfolder"]');
            if (newDepth >= 10) {
                if (addFolderBtn) addFolderBtn.remove();
            } else if (!addFolderBtn) {
                const folderId = groupEl.getAttribute('data-folder-id');
                const noteBtn = actions.querySelector('button[title^="Tambah Catatan"]');
                const newBtn = document.createElement('button');
                newBtn.className = 'mini-btn';
                newBtn.title = 'Tambah Subfolder';
                newBtn.setAttribute('onclick', `openNewFolderModal(${folderId})`);
                newBtn.innerHTML = '&#43; folder';
                if (noteBtn) {
                    actions.insertBefore(newBtn, noteBtn);
                } else {
                    actions.prepend(newBtn);
                }
            }
        }
    }

    const childrenContainer = groupEl.querySelector(':scope > .tree-children');
    if (childrenContainer) {
        const childGroups = childrenContainer.querySelectorAll(':scope > .tree-folder-group');
        childGroups.forEach(child => updateGroupDepth(child, newDepth + 1));
    }
}

// Move Folder API & DOM Update
async function moveFolderToParent(folderId, targetParentId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const folderGroup = document.querySelector(`.tree-folder-group[data-folder-id="${folderId}"]`);
    if (!folderGroup) return;

    try {
        const res = await fetch(`/folders/${folderId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ parent_id: targetParentId ? parseInt(targetParentId, 10) : null })
        });

        const data = await res.json();
        if (data.success) {
            const oldParentGroup = folderGroup.parentElement?.closest('.tree-folder-group');

            if (targetParentId) {
                const targetGroup = document.querySelector(`.tree-folder-group[data-folder-id="${targetParentId}"]`);
                if (targetGroup) {
                    let children = targetGroup.querySelector('.tree-children');
                    if (!children) {
                        children = document.createElement('div');
                        children.className = 'tree-children';
                        targetGroup.appendChild(children);
                    }
                    const emptyNotice = children.querySelector(':scope > .tree-empty-notice');
                    if (emptyNotice) emptyNotice.remove();

                    // Folders are always placed at the top (before any notes in this folder)
                    const firstNote = children.querySelector(':scope > .tree-note');
                    if (firstNote) {
                        children.insertBefore(folderGroup, firstNote);
                    } else {
                        children.appendChild(folderGroup);
                    }

                    const folderHeader = targetGroup.querySelector('.tree-folder');
                    if (folderHeader) {
                        folderHeader.classList.remove('collapsed');
                        updateFolderToggleState(folderHeader);
                    }

                    const targetDepth = parseInt(targetGroup.getAttribute('data-depth') || '1', 10);
                    updateGroupDepth(folderGroup, targetDepth + 1);
                }
            } else {
                const foldersContainer = document.getElementById('folders-container') || document.getElementById('sidebar-tree');
                if (foldersContainer) {
                    foldersContainer.appendChild(folderGroup);
                }
                updateGroupDepth(folderGroup, 1);
            }

            if (oldParentGroup) {
                checkAndUpdateFolderEmptyState(oldParentGroup);
            }

            folderGroup.classList.add('item-moved-highlight');
            setTimeout(() => folderGroup.classList.remove('item-moved-highlight'), 800);
        } else {
            alert(data.message || 'Gagal memindahkan folder.');
        }
    } catch (err) {
        console.error('Failed to move folder:', err);
        alert('Gagal memindahkan folder.');
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function () {
    initSidebarDragAndDrop();
    document.querySelectorAll('.tree-folder').forEach(folder => {
        updateFolderToggleState(folder);
    });

    const treeSearchInput = document.getElementById('tree-search');
    if (treeSearchInput) {
        treeSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && this.value) {
                e.preventDefault();
                e.stopPropagation();
                clearSearch();
            }
        });
    }
});
