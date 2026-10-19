@extends('layouts.app')

@section('title', $activeNote ? $activeNote->title : 'Workspace')

@section('content')
<header>
    <div class="brand">
        <h1>Codebook</h1>
        <span>Markdown Mode</span>
    </div>

    <div class="header-actions">
        <div class="user-badge">
            <span>&#128100; {{ Auth::user()->name }}</span>
            <span style="font-size:10px;background:var(--panel-2);border:1px solid var(--border);padding:2px 6px;border-radius:12px;text-transform:uppercase;">{{ Auth::user()->role }}</span>
        </div>
        <button class="icon-btn" id="theme-btn">Light mode</button>
        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="icon-btn" title="Keluar dari akun">&#x27A1; Logout</button>
        </form>
    </div>
</header>

<main>
    <!-- Sidebar: Directory Explorer -->
    <aside id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title-row">
                <span class="sidebar-title">Direktori</span>
                <div class="sidebar-quick-actions">
                    <button class="icon-btn" onclick="openNewFolderModal()" title="Buat Folder Baru" style="padding:4px 8px;font-size:11px;">&#43; Folder</button>
                    <button class="icon-btn btn-primary" onclick="openNewNoteModal()" title="Buat Catatan Baru" style="padding:4px 8px;font-size:11px;">&#43; Note</button>
                </div>
            </div>
            <div class="sidebar-search">
                <input type="text" id="tree-search" placeholder="Cari folder atau catatan..." onkeyup="filterTree(this.value)">
            </div>
        </div>

        <div class="sidebar-tree" id="sidebar-tree">
            {{-- Folders --}}
            @foreach ($folders as $folder)
                @include('workspace.partials.folder_tree_item', ['folder' => $folder, 'activeNote' => $activeNote])
            @endforeach

            {{-- Root Notes (Notes without a folder) --}}
            @if ($rootNotes->count() > 0)
                <div style="font-size:11px;color:var(--muted);font-weight:600;padding:8px 6px 4px;text-transform:uppercase;letter-spacing:0.5px;">
                    Catatan Utama
                </div>
                @foreach ($rootNotes as $note)
                    <a href="{{ route('workspace', ['note' => $note->id]) }}"
                       class="tree-note {{ $activeNote && $activeNote->id === $note->id ? 'active' : '' }}"
                       data-note-id="{{ $note->id }}">
                        <div class="tree-label">
                            <span class="tree-icon">&#128221;</span>
                            <span class="note-title-text">{{ $note->title }}</span>
                        </div>
                        <div class="item-actions" onclick="event.preventDefault(); event.stopPropagation()">
                            <button class="mini-btn danger" title="Hapus Catatan" onclick="deleteNote({{ $note->id }})">&times;</button>
                        </div>
                    </a>
                @endforeach
            @endif

            @if ($folders->count() === 0 && $rootNotes->count() === 0)
                <div style="padding:20px 10px;text-align:center;color:var(--muted);font-size:12px;">
                    Belum ada folder atau catatan. Klik tombol <strong>+ Note</strong> untuk mulai.
                </div>
            @endif
        </div>
    </aside>

    <!-- Center: Chat-like Markdown Thread -->
    <div id="thread">
        @if ($activeNote)
            <div class="empty-state {{ $activeNote->messages->count() > 0 ? 'd-none' : '' }}" id="empty-state" style="{{ $activeNote->messages->count() > 0 ? 'display:none;' : '' }}">
                <div class="glyph">&gt;_</div>
                <p>Belum ada pesan di catatan <strong>"{{ $activeNote->title }}"</strong>. Tulis markdown di panel kanan, pilih pengirim, lalu kirim untuk melihat hasil render chat bubble.</p>
            </div>
        @else
            <div class="empty-state">
                <div class="glyph">&gt;_</div>
                <p>Tidak ada catatan yang dipilih. Pilih catatan dari panel kiri atau buat catatan baru.</p>
                <button class="icon-btn btn-primary" onclick="openNewNoteModal()" style="margin:16px auto 0;">&#43; Buat Catatan Pertama</button>
            </div>
        @endif
    </div>

    <!-- Right Panel: Composer & Controls -->
    @if ($activeNote)
        <div id="composer">
            <div class="panel-block">
                <div class="panel-label">Judul Catatan</div>
                <input type="text" id="note-title-input" class="note-title-input" value="{{ $activeNote->title }}" placeholder="Nama Catatan..." onchange="updateNoteTitle({{ $activeNote->id }}, this.value)">
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
                <button class="icon-btn" id="clear-btn" style="flex:1;justify-content:center;" onclick="clearCurrentNoteMessages({{ $activeNote->id }})">Clear chat</button>
                <button class="icon-btn" style="color:var(--danger);justify-content:center;" onclick="deleteNote({{ $activeNote->id }})" title="Hapus Catatan">&#128465;</button>
            </div>
        </div>
    @endif
</main>

<!-- Modal: New Folder -->
<div class="modal-overlay" id="newFolderModal">
    <div class="modal">
        <h3 class="modal-title">Folder Baru</h3>
        <form id="newFolderForm" onsubmit="submitNewFolder(event)">
            <input type="hidden" id="folder-parent-id" name="parent_id" value="">
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
                <input type="text" id="note-title-modal-input" class="modal-input" required placeholder="Contoh: Diskusi Fitur Chat, Snippet Auth...">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Folder (Opsional)</label>
                <select id="note-folder-select" class="modal-input" style="background:var(--panel-2);">
                    <option value="">-- Tanpa Folder (Catatan Utama) --</option>
                    @foreach ($folders as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @foreach ($f->children as $sub)
                            <option value="{{ $sub->id }}">&nbsp;&nbsp;&bull; {{ $sub->name }}</option>
                        @endforeach
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

<script src="{{ asset('js/codebook.js') }}"></script>
@endsection

