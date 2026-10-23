<div class="tree-folder-group" data-folder-id="{{ $folder->id }}" data-depth="{{ $depth ?? 1 }}">
    <div class="tree-folder" draggable="true" data-folder-id="{{ $folder->id }}" onclick="toggleFolder(this, event)">
        <div class="tree-label">
            <button type="button" class="folder-toggle-btn" title="Tutup folder" aria-expanded="true" onclick="event.stopPropagation(); toggleFolder(this.closest('.tree-folder'), event)">
                <svg class="folder-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            <span class="tree-icon folder-icon">&#128194;</span>
            <span class="folder-name">{{ $folder->name }}</span>
        </div>
        <div class="item-actions" onclick="event.stopPropagation()">
            @if (($depth ?? 1) < 10)
                <button class="mini-btn" title="Tambah Subfolder" onclick="openNewFolderModal({{ $folder->id }})">&#43; folder</button>
            @endif
            <button class="mini-btn" title="Tambah Catatan di Folder ini" onclick="openNewNoteModal({{ $folder->id }})">&#43; note</button>
            <button class="mini-btn danger" title="Hapus Folder" onclick="deleteFolder({{ $folder->id }})">&times;</button>
        </div>
    </div>

    <div class="tree-children">
        {{-- Subfolders (Up to max depth 10, always placed on top) --}}
        @foreach ($folder->children as $child)
            @include('workspace.partials.folder_tree_item', ['folder' => $child, 'activeNote' => $activeNote, 'depth' => ($depth ?? 1) + 1])
        @endforeach

        {{-- Notes in this folder (placed below subfolders) --}}
        @foreach ($folder->notes as $note)
            <a href="{{ route('workspace', ['note' => $note->id]) }}" 
               class="tree-note {{ $activeNote && $activeNote->id === $note->id ? 'active' : '' }}" 
               draggable="true"
               data-note-id="{{ $note->id }}"
               data-folder-id="{{ $folder->id }}"
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

        @if ($folder->children->isEmpty() && $folder->notes->isEmpty())
            <div class="tree-empty-notice">(kosong)</div>
        @endif
    </div>
</div>

