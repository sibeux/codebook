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
        const isCurrentLight = document.documentElement.getAttribute('data-theme') === 'light';
        themeBtn.textContent = isCurrentLight ? 'Dark mode' : 'Light mode';

        themeBtn.addEventListener('click', function () {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            if (isLight) {
                document.documentElement.removeAttribute('data-theme');
                themeBtn.textContent = 'Light mode';
                localStorage.setItem('codebook_theme', 'dark');
                if (hljsDark && hljsLight) {
                    hljsDark.disabled = false;
                    hljsLight.disabled = true;
                }
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                themeBtn.textContent = 'Dark mode';
                localStorage.setItem('codebook_theme', 'light');
                if (hljsDark && hljsLight) {
                    hljsDark.disabled = true;
                    hljsLight.disabled = false;
                }
            }
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
                }

                // Update URL cleanly without reloading
                window.history.pushState({ noteId: data.note.id }, '', `/?note=${data.note.id}`);
                document.title = `Codebook - ${data.note.title}`;

                renderAll();
                thread.scrollTop = thread.scrollHeight;
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
        if (!window.CURRENT_NOTE) return;
        window.CURRENT_NOTE.title = newTitle;

        // Update title in sidebar immediately
        const activeNav = document.querySelector(`.tree-note[data-note-id="${window.CURRENT_NOTE.id}"] .note-title-text`);
        if (activeNav) activeNav.textContent = newTitle;

        clearTimeout(titleDebounceTimeout);
        titleDebounceTimeout = setTimeout(async () => {
            try {
                await fetch(`/notes/${window.CURRENT_NOTE.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ title: newTitle })
                });
            } catch (e) {
                console.error('Failed to auto-save note title', e);
            }
        }, 500);
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
    document.getElementById('folder-parent-id').value = parentId || '';
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
    const parentId = document.getElementById('folder-parent-id').value;

    try {
        const res = await fetch('/folders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ name, parent_id: parentId || null })
        });
        const data = await res.json();
        if (data.success && data.folder) {
            closeModal('newFolderModal');
            // Dynamically inject folder into tree
            const tree = document.getElementById('sidebar-tree');
            const noNotice = document.getElementById('no-items-notice');
            if (noNotice) noNotice.remove();

            const folderHtml = `
                <div class="tree-folder-group" data-folder-id="${data.folder.id}">
                    <div class="tree-folder" onclick="toggleFolder(this, event)">
                        <div class="tree-label">
                            <span class="tree-icon folder-icon">&#128193;</span>
                            <span class="folder-name">${data.folder.name}</span>
                        </div>
                        <div class="item-actions" onclick="event.stopPropagation()">
                            <button class="mini-btn" title="Tambah Catatan di Folder ini" onclick="openNewNoteModal(${data.folder.id})">&#43; note</button>
                            <button class="mini-btn danger" title="Hapus Folder" onclick="deleteFolder(${data.folder.id})">&times;</button>
                        </div>
                    </div>
                    <div class="tree-children"></div>
                </div>
            `;

            if (parentId) {
                const parentGroup = document.querySelector(`.tree-folder-group[data-folder-id="${parentId}"] .tree-children`);
                if (parentGroup) parentGroup.insertAdjacentHTML('beforeend', folderHtml);
            } else {
                tree.insertAdjacentHTML('afterbegin', folderHtml);
            }

            // Also add option to note modal select
            const select = document.getElementById('note-folder-select');
            if (select) {
                const opt = document.createElement('option');
                opt.value = data.folder.id;
                opt.textContent = data.folder.name;
                select.appendChild(opt);
            }
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
                const tree = document.getElementById('sidebar-tree');
                tree.insertAdjacentHTML('beforeend', noteHtml);
            }

            // Select this newly created note in-place without page reload
            window.selectNote(data.note.id);
        }
    } catch (err) {
        alert('Gagal membuat catatan.');
    }
}
