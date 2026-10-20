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
        if (!confirm('Hapus pesan ini?')) return;

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
        if (!confirm('Hapus semua pesan di catatan ini?')) return;

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

    // Render all messages
    function renderAll() {
        if (!thread) return;
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
            const displayTime = m.sent_time || (m.created_at ? new Date(m.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '');
            meta.innerHTML = '<span class="who">' + (m.sender === 'me' ? 'Me' : 'Other') + '</span><span>' + displayTime + '</span>';

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
                '<div style="display:flex;align-items:center;gap:12px;">' +
                '<button class="toggle-btn" id="toggle-' + m.id + '">&#8964; tampilkan semua</button>' +
                '<button class="del-btn" title="Hapus pesan" data-id="' + m.id + '">&times;</button>' +
                '</div>';

            bubble.appendChild(tools);
            wrap.appendChild(meta);
            wrap.appendChild(bubble);
            row.appendChild(wrap);
            thread.appendChild(row);

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
        if (!confirm(`Apakah Anda yakin ingin menghapus catatan "${window.CURRENT_NOTE.title}"?`)) return;

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
    if (!confirm('Apakah Anda yakin ingin menghapus catatan ini?')) return;
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
            const navItem = document.querySelector(`.tree-note[data-note-id="${noteId}"]`);
            if (navItem) navItem.remove();

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

// Delete Folder
async function deleteFolder(folderId) {
    if (!confirm('Hapus folder ini dan semua catatan di dalamnya?')) return;
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
            const folderGroup = document.querySelector(`.tree-folder-group[data-folder-id="${folderId}"]`);
            if (folderGroup) folderGroup.remove();

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

// Toggle folder expansion
function toggleFolder(el, event) {
    el.classList.toggle('collapsed');
}

// Filter Sidebar Tree
function filterTree(query) {
    const q = query.toLowerCase().trim();
    const notes = document.querySelectorAll('.tree-note');
    if (!q) {
        notes.forEach(el => el.style.display = '');
        return;
    }

    notes.forEach(note => {
        const title = note.querySelector('.note-title-text')?.textContent.toLowerCase() || '';
        const match = title.includes(q);
        note.style.display = match ? 'flex' : 'none';
    });
}

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
    document.getElementById(modalId).classList.remove('active');
}

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
                            <span class="tree-icon folder-icon">&#128193;</span>
                            <span class="folder-name">${data.folder.name}</span>
                        </div>
                        <div class="item-actions" onclick="event.stopPropagation()">
                            ${depth < 10 ? `<button class="mini-btn" title="Tambah Subfolder" onclick="openNewFolderModal(${data.folder.id})">&#43; folder</button>` : ''}
                            <button class="mini-btn" title="Tambah Catatan di Folder ini" onclick="openNewNoteModal(${data.folder.id})">&#43; note</button>
                            <button class="mini-btn danger" title="Hapus Folder" onclick="deleteFolder(${data.folder.id})">&times;</button>
                        </div>
                    </div>
                    <div class="tree-children"></div>
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
                    childrenContainer.insertAdjacentHTML('beforeend', folderHtml);
                    const parentHeader = parentGroup.querySelector('.tree-folder');
                    if (parentHeader) parentHeader.classList.remove('collapsed');
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

    try {
        const res = await fetch('/notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ title, folder_id: folderId || null, mode: 'markdown' })
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
                    </div>
                    <div class="item-actions" onclick="event.preventDefault(); event.stopPropagation()">
                        <button class="mini-btn danger" title="Hapus Catatan" onclick="deleteNote(${data.note.id})">&times;</button>
                    </div>
                </a>
            `;

            if (folderId) {
                const folderChildren = document.querySelector(`.tree-folder-group[data-folder-id="${folderId}"] .tree-children`);
                if (folderChildren) {
                    folderChildren.insertAdjacentHTML('beforeend', noteHtml);
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
                    children.appendChild(noteEl);

                    const folderHeader = targetGroup.querySelector('.tree-folder');
                    if (folderHeader) folderHeader.classList.remove('collapsed');
                }
            } else {
                noteEl.removeAttribute('data-folder-id');
                const rootSection = document.getElementById('root-notes-section');
                const rootContainer = document.getElementById('root-notes-container');
                if (rootSection) rootSection.style.display = 'block';
                if (rootContainer) rootContainer.appendChild(noteEl);
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
            if (targetParentId) {
                const targetGroup = document.querySelector(`.tree-folder-group[data-folder-id="${targetParentId}"]`);
                if (targetGroup) {
                    let children = targetGroup.querySelector('.tree-children');
                    if (!children) {
                        children = document.createElement('div');
                        children.className = 'tree-children';
                        targetGroup.appendChild(children);
                    }
                    children.appendChild(folderGroup);

                    const folderHeader = targetGroup.querySelector('.tree-folder');
                    if (folderHeader) folderHeader.classList.remove('collapsed');

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
});
