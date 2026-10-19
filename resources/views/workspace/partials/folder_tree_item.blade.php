<div class="tree-folder-group" data-folder-id="{{ $folder->id }}">
    <div class="tree-folder" onclick="toggleFolder(this, event)">
        <div class="tree-label">
            <span class="tree-icon folder-icon">&#128193;</span>
            <span class="folder-name">{{ $folder->name }}</span>
        </div>
        <div class="item-actions" onclick="event.stopPropagation()">
            <button class="mini-btn" title="Tambah Catatan di Folder ini" onclick="openNewNoteModal({{ $folder->id }})">&#43; note</button>
            <button class="mini-btn danger" title="Hapus Folder" onclick="deleteFolder({{ $folder->id }})">&times;</button>
        </div>
    </div>

    <div class="tree-children">
        {{-- Subfolders --}}
        @foreach ($folder->children as $child)
            @include('workspace.partials.folder_tree_item', ['folder' => $child, 'activeNote' => $activeNote])
        @endforeach

        {{-- Notes in this folder --}}
        @foreach ($folder->notes as $note)
            <a href="{{ route('workspace', ['note' => $note->id]) }}" 
               class="tree-note {{ $activeNote && $activeNote->id === $note->id ? 'active' : '' }}" 
               data-note-id="{{ $note->id }}"
               data-folder-id="{{ $folder->id }}"
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

