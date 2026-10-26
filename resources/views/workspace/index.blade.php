@extends('layouts.app')

@section('title', $activeNote ? $activeNote->title : 'Workspace')

@section('content')
<header>
    <div class="brand">
        <button type="button" class="icon-btn sidebar-toggle-btn" id="sidebar-toggle-btn" title="Buka / Tutup Sidebar (Ctrl+B)" aria-label="Toggle Sidebar">
            <svg class="icon-sidebar" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="9" y1="3" x2="9" y2="21"></line>
            </svg>
        </button>
        <h1>Codebook</h1>
        <span>Markdown Mode</span>
    </div>

    <div class="header-actions">
        <div class="user-badge">
            <span>&#128100; {{ Auth::user()->name }}</span>
            <span style="font-size:10px;background:var(--panel-2);border:1px solid var(--border);padding:2px 6px;border-radius:12px;text-transform:uppercase;">{{ Auth::user()->role }}</span>
        </div>
        <button class="icon-btn" id="theme-btn" title="Ganti Mode Tema"><span>Light mode</span></button>
        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="icon-btn" title="Keluar dari akun">&#x27A1; Logout</button>
        </form>
    </div>
</header>

<main>
    <!-- Floating toggle button to reopen sidebar when collapsed -->
    <button type="button" class="sidebar-floating-toggle" id="sidebar-floating-toggle" title="Buka Sidebar (Ctrl+B)" aria-label="Buka Sidebar">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </button>

    <!-- Sidebar: Directory Explorer -->
    <aside id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title-row">
                <span class="sidebar-title">Direktori</span>
                <div class="sidebar-header-tools">
                    <button type="button" class="sidebar-tool-btn" onclick="expandAllFolders()" title="Buka Semua Folder" aria-label="Buka Semua Folder">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="7 13 12 18 17 13"></polyline>
                            <polyline points="7 6 12 11 17 6"></polyline>
                        </svg>
                    </button>
                    <button type="button" class="sidebar-tool-btn" onclick="collapseAllFolders()" title="Tutup Semua Folder" aria-label="Tutup Semua Folder">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="17 11 12 6 7 11"></polyline>
                            <polyline points="17 18 12 13 7 18"></polyline>
                        </svg>
                    </button>
                    <button type="button" class="sidebar-tool-btn sidebar-close-btn" id="sidebar-close-btn" title="Tutup / Minimize Sidebar (Ctrl+B)" aria-label="Tutup Sidebar">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="sidebar-create-actions">
                <button type="button" class="sidebar-btn-create primary" onclick="openNewNoteModal()" title="Buat Catatan Baru">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Catatan</span>
                </button>
                <button type="button" class="sidebar-btn-create secondary" onclick="openNewFolderModal()" title="Buat Folder Baru">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        <line x1="12" y1="11" x2="12" y2="17"></line>
                        <line x1="9" y1="14" x2="15" y2="14"></line>
                    </svg>
                    <span>Folder</span>
                </button>
            </div>

            <div class="sidebar-search">
                <svg class="search-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="tree-search" placeholder="Cari folder atau catatan..." onkeyup="filterTree(this.value)" autocomplete="off" spellcheck="false">
                <button type="button" class="search-clear-btn" id="search-clear-btn" title="Hapus pencarian" onclick="clearSearch()" style="display:none;" aria-label="Hapus pencarian">&times;</button>
            </div>
        </div>

        <div class="sidebar-tree" id="sidebar-tree">
            {{-- Folders --}}
            <div class="folders-container" id="folders-container">
                @foreach ($folders as $folder)
                    @include('workspace.partials.folder_tree_item', ['folder' => $folder, 'activeNote' => $activeNote, 'depth' => 1])
                @endforeach
            </div>

            {{-- Root Notes (Notes without a folder) --}}
            <div class="root-notes-section" id="root-notes-section" style="{{ $rootNotes->count() === 0 ? 'display:none;' : '' }}">
                <div class="root-notes-header" style="font-size:11px;color:var(--muted);font-weight:600;padding:8px 6px 4px;text-transform:uppercase;letter-spacing:0.5px;">
                    Catatan Utama
                </div>
                <div class="root-notes-container" id="root-notes-container">
                    @foreach ($rootNotes as $note)
                        <a href="{{ route('workspace', ['note' => $note->id]) }}" 
                           class="tree-note {{ $activeNote && $activeNote->id === $note->id ? 'active' : '' }}" 
                           draggable="true"
                           data-note-id="{{ $note->id }}"
                           onclick="selectNote({{ $note->id }}, event)">
                            <div class="tree-label">
                                <span class="tree-icon">&#128221;</span>
                                <span class="note-title-text">{{ $note->title }}</span>
                                <span class="note-public-icon" id="tree-note-public-{{ $note->id }}" title="Catatan Publik" style="{{ $note->is_public ? 'font-size:10px;margin-left:4px;opacity:0.8;' : 'display:none;font-size:10px;margin-left:4px;opacity:0.8;' }}">&#127760;</span>
                            </div>
                            <div class="item-actions" onclick="event.preventDefault(); event.stopPropagation()">
                                <button class="mini-btn danger" title="Hapus Catatan" onclick="deleteNote({{ $note->id }})">&times;</button>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Root Drop Zone (appears when dragging to drop item back to root level) --}}
            <div class="root-dropzone" id="root-dropzone" title="Tarik ke sini untuk memindahkan ke tingkat terluar (tanpa folder)">
                <span>&darr; Lepas di sini untuk pindah ke Root</span>
            </div>

            @if ($folders->count() === 0 && $rootNotes->count() === 0)
                <div style="padding:20px 10px;text-align:center;color:var(--muted);font-size:12px;" id="no-items-notice">
                    Belum ada folder atau catatan. Klik tombol <strong>+ Note</strong> untuk mulai.
                </div>
            @endif
        </div>
    </aside>

    <!-- Center: Chat-like Markdown Thread -->
    <div class="thread-container" id="thread-container">
        <div class="thread-loading-bar" id="thread-loading-bar"></div>

        <div id="thread">
            <div class="empty-state" id="no-note-state" style="{{ $activeNote ? 'display:none;' : '' }}">
                <div class="glyph">&gt;_</div>
                <p>Tidak ada catatan yang dipilih. Pilih catatan dari panel kiri atau buat catatan baru.</p>
                <button class="icon-btn btn-primary" onclick="openNewNoteModal()" style="margin:16px auto 0;">&#43; Buat Catatan Pertama</button>
            </div>

            <div class="empty-state" id="empty-state" style="{{ ($activeNote && $activeNote->messages->count() === 0) ? 'display:block;' : 'display:none;' }}">
                <div class="glyph">&gt;_</div>
                <p id="empty-state-text">Belum ada pesan di catatan <strong>"{{ $activeNote ? $activeNote->title : '' }}"</strong>. Tulis markdown di panel kanan, pilih pengirim, lalu kirim untuk melihat hasil render chat bubble.</p>
            </div>
        </div>

        <div class="thread-loader-overlay" id="thread-loader-overlay" aria-live="polite">
            <div class="thread-loader-content">
                <div class="thread-loader-spinner"></div>
                <span class="thread-loader-text">Membuka catatan...</span>
            </div>
        </div>
    </div>

    <!-- Right Panel: Composer & Controls (SPA persistent) -->
    <div id="composer" style="{{ $activeNote ? 'display:flex;' : 'display:none;' }}">
        <div class="panel-block">
            <div class="panel-label">Judul Catatan</div>
            <textarea id="note-title-input" class="note-title-input" rows="1" maxlength="255" placeholder="Nama Catatan..." title="Enter untuk simpan &middot; Esc untuk batal" oninput="onNoteTitleInput(this.value)">{{ $activeNote ? $activeNote->title : '' }}</textarea>
            <div class="title-edit-hint" style="font-size:11px;color:var(--muted);display:flex;justify-content:space-between;align-items:center;padding:0 2px;">
                <span>Enter simpan &middot; Esc batal</span>
                <span id="title-char-count" style="font-variant-numeric: tabular-nums;">{{ $activeNote ? mb_strlen($activeNote->title) : 0 }}/255</span>
            </div>
        </div>

        <div class="panel-block">
            <div class="panel-label">Pengirim</div>
            <div class="sender-toggle">
                <button type="button" class="active me" data-sender="me">Me</button>
                <button type="button" class="other" data-sender="other">Other</button>
            </div>
        </div>

        <div class="panel-block" style="flex:1;min-height:0;">
            <div class="panel-label">Pesan Markdown</div>
            <textarea id="msg-input" placeholder="Tulis markdown di sini... (# heading, **bold**, - list, ```kode```, dsb)"></textarea>
            <button id="send-btn">Kirim</button>
            <div class="hint">Ctrl / Cmd + Enter untuk kirim &middot; Auto-saved ke database &middot; Format Markdown penuh</div>
        </div>

        <div class="panel-block">
            <div class="panel-label">Ukuran Font Chat</div>
            <div class="font-slider-row">
                <input type="range" id="font-size" min="12" max="20" step="0.5" value="14.5">
                <span id="font-size-out">14.5px</span>
            </div>
        </div>

        <div class="panel-block" style="flex-direction:row;gap:8px;">
            <button class="icon-btn" id="share-note-btn" style="flex:1;justify-content:center;gap:6px;" onclick="openShareModal()" title="Bagikan Catatan">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                </svg>
                <span>Bagikan</span>
                <span id="active-note-visibility-badge" style="font-size:10px;padding:1px 6px;border-radius:10px;background:{{ ($activeNote && $activeNote->is_public) ? 'rgba(34,197,94,0.18)' : 'var(--panel-2)' }};color:{{ ($activeNote && $activeNote->is_public) ? '#22c55e' : 'var(--muted)' }};border:1px solid {{ ($activeNote && $activeNote->is_public) ? 'rgba(34,197,94,0.3)' : 'var(--border)' }};">
                    {{ ($activeNote && $activeNote->is_public) ? 'Publik' : 'Pribadi' }}
                </span>
            </button>
            <button class="icon-btn" id="clear-btn" style="justify-content:center;gap:6px;" onclick="clearCurrentNoteMessages()" title="Kosongkan semua pesan di catatan ini">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"></path>
                    <path d="M22 21H7"></path>
                    <path d="m5 11 9 9"></path>
                </svg>
                <span>Kosongkan Chat</span>
            </button>
        </div>
    </div>
</main>

<!-- Modal: New Folder -->
<div class="modal-overlay" id="newFolderModal">
    <div class="modal">
        <h3 class="modal-title" id="folder-modal-title">Folder Baru</h3>
        <form id="newFolderForm" onsubmit="submitNewFolder(event)">
            <input type="hidden" id="folder-parent-id" name="parent_id" value="">
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Lokasi / Folder Induk</label>
                <select id="folder-parent-select" class="modal-input" style="background:var(--panel-2);" onchange="document.getElementById('folder-parent-id').value = this.value">
                    <option value="">-- Tingkat Utama (Root) --</option>
                    @foreach ($flatFolderTree as $f)
                        <option value="{{ $f->id }}" {{ $f->depth >= 10 ? 'disabled' : '' }} data-depth="{{ $f->depth }}">
                            {!! str_repeat('&nbsp;&nbsp;&nbsp;', $f->depth - 1) !!}{{ $f->depth > 1 ? '└─ ' : '' }}{{ $f->name }}{{ $f->depth >= 10 ? ' (Maksimal level 10)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Nama Folder</label>
                <input type="text" id="folder-name-input" class="modal-input" required placeholder="Contoh: Projek Laravel, Dokumentasi API...">
            </div>
            <div class="modal-actions">
                <button type="button" class="icon-btn" onclick="closeModal('newFolderModal')">Batal</button>
                <button type="submit" class="icon-btn btn-primary">Buat Folder</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: New Note -->
<div class="modal-overlay" id="newNoteModal">
    <div class="modal">
        <h3 class="modal-title">Catatan Baru</h3>
        <form id="newNoteForm" onsubmit="submitNewNote(event)">
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Judul Catatan</label>
                <input type="text" id="note-title-modal-input" class="modal-input" required maxlength="255" placeholder="Contoh: Diskusi Fitur Chat, Snippet Auth...">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Visibilitas</label>
                <select id="note-is-public-select" class="modal-input" style="background:var(--panel-2);">
                    <option value="0">&#128274; Pribadi (Hanya Anda yang dapat melihat)</option>
                    <option value="1">&#127760; Publik (Dapat dilihat siapa saja dengan tautan)</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Folder (Opsional)</label>
                <select id="note-folder-select" class="modal-input" style="background:var(--panel-2);">
                    <option value="">-- Tanpa Folder (Catatan Utama) --</option>
                    @foreach ($flatFolderTree as $f)
                        <option value="{{ $f->id }}">
                            {!! str_repeat('&nbsp;&nbsp;&nbsp;', $f->depth - 1) !!}{{ $f->depth > 1 ? '└─ ' : '' }}{{ $f->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="icon-btn" onclick="closeModal('newNoteModal')">Batal</button>
                <button type="submit" class="icon-btn btn-primary">Buat Catatan</button>
            </div>
        </form>
    </div>
</div>

@include('workspace.partials.share_modal')
@include('workspace.partials.confirm_modal')
@include('workspace.partials.folder_choice_modal')

@if ($activeNote)
    <script>
        window.CURRENT_NOTE = @json($activeNote);
        window.INITIAL_MESSAGES = @json($activeNote->messages);
    </script>
@else
    <script>
        window.CURRENT_NOTE = null;
        window.INITIAL_MESSAGES = [];
    </script>
@endif

<script src="{{ asset('js/codebook.js') }}?v={{ file_exists(public_path('js/codebook.js')) ? filemtime(public_path('js/codebook.js')) : time() }}"></script>
@endsection

