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
                <div class="sidebar-quick-actions">
                    <button class="icon-btn" onclick="openNewFolderModal()" title="Buat Folder Baru" style="padding:4px 8px;font-size:11px;">&#43; Folder</button>
                    <button class="icon-btn btn-primary" onclick="openNewNoteModal()" title="Buat Catatan Baru" style="padding:4px 8px;font-size:11px;">&#43; Note</button>
                    <button type="button" class="icon-btn sidebar-close-btn" id="sidebar-close-btn" title="Tutup / Minimize Sidebar" style="padding:4px 7px;font-size:11px;" aria-label="Tutup Sidebar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="sidebar-search">
                <input type="text" id="tree-search" placeholder="Cari folder atau catatan..." onkeyup="filterTree(this.value)">
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
            <button class="icon-btn" id="clear-btn" style="flex:1;justify-content:center;" onclick="clearCurrentNoteMessages()">Clear chat</button>
            <button class="icon-btn" style="color:var(--danger);justify-content:center;" onclick="deleteCurrentNote()" title="Hapus Catatan">&#128465;</button>
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

@include('workspace.partials.confirm_modal')

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

