document.addEventListener('DOMContentLoaded', function () {
    marked.setOptions({ breaks: true, gfm: true });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const thread = document.getElementById('thread');
    const emptyState = document.getElementById('empty-state');
    const input = document.getElementById('msg-input');
    const sendBtn = document.getElementById('send-btn');
    const senderButtons = document.querySelectorAll('.sender-toggle button');
    const themeBtn = document.getElementById('theme-btn');
    const fontSlider = document.getElementById('font-size');
    const fontSizeOut = document.getElementById('font-size-out');
    const hljsDark = document.getElementById('hljs-dark');
    const hljsLight = document.getElementById('hljs-light');

    let currentSender = 'me';
    let messages = window.INITIAL_MESSAGES || [];
    const currentNote = window.CURRENT_NOTE;

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
        if (!currentNote || !input) return;
        const text = input.value;
        if (!text.trim()) return;

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        sendBtn.disabled = true;

        try {
            const res = await fetch(`/notes/${currentNote.id}/messages`, {
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
        if (!currentNote) return;
        if (!confirm('Hapus pesan ini?')) return;

        try {
            const res = await fetch(`/notes/${currentNote.id}/messages/${id}`, {
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
    window.clearCurrentNoteMessages = async function (noteId) {
        if (messages.length === 0) return;
        if (!confirm('Hapus semua pesan di catatan ini?')) return;

        try {
            const res = await fetch(`/notes/${noteId}/clear`, {
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

        if (messages.length === 0) {
            if (emptyState) {
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

    // Initial render
    if (currentNote && messages.length > 0) {
        renderAll();
        setTimeout(function () {
            thread.scrollTop = thread.scrollHeight;
        }, 100);
    }
});

// Update Note Title
async function updateNoteTitle(noteId, newTitle) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    try {
        const res = await fetch(`/notes/${noteId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ title: newTitle })
        });
        const data = await res.json();
        if (data.success) {
            const activeNav = document.querySelector(`.tree-note[data-note-id="${noteId}"] .note-title-text`);
            if (activeNav) activeNav.textContent = newTitle;
        }
    } catch (e) {
        console.error('Failed to update note title', e);
    }
}

// Delete Note
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
            window.location.href = '/';
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
            window.location.href = '/';
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
    const items = document.querySelectorAll('.tree-note, .tree-folder-group');
    if (!q) {
        items.forEach(el => el.style.display = '');
        return;
    }

    document.querySelectorAll('.tree-note').forEach(note => {
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
        if (data.success) {
            window.location.reload();
        }
    } catch (err) {
        alert('Gagal membuat folder.');
    }
}

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
            window.location.href = `/?note=${data.note.id}`;
        }
    } catch (err) {
        alert('Gagal membuat catatan.');
    }
}

