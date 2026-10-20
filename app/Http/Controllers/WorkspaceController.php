<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Retrieve all folders for user with their notes in eager loaded queries
        $allFolders = Folder::where('user_id', $userId)
            ->with('notes')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // Build the nested tree in memory
        $foldersById = [];
        foreach ($allFolders as $folder) {
            $folder->setRelation('children', collect());
            $foldersById[$folder->id] = $folder;
        }

        $folders = collect();
        foreach ($allFolders as $folder) {
            if ($folder->parent_id && isset($foldersById[$folder->parent_id])) {
                $foldersById[$folder->parent_id]->children->push($folder);
            } else {
                $folders->push($folder);
            }
        }

        // Build a flat ordered list with calculated depth for select dropdowns
        $flatFolderTree = collect();
        $buildFlatTree = function ($items, $depth = 1) use (&$buildFlatTree, &$flatFolderTree) {
            foreach ($items as $item) {
                $item->depth = $depth;
                $flatFolderTree->push($item);
                if ($item->children && $item->children->isNotEmpty()) {
                    $buildFlatTree($item->children, $depth + 1);
                }
            }
        };
        $buildFlatTree($folders);

        // Load notes that are directly at root level (not in any folder)
        $rootNotes = Note::where('user_id', $userId)
            ->whereNull('folder_id')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Active note selection
        $activeNoteId = $request->query('note');
        $activeNote = null;

        if ($activeNoteId) {
            $activeNote = Note::where('user_id', $userId)
                ->with('messages')
                ->find($activeNoteId);
        }

        if (! $activeNote) {
            // Pick most recent note if available
            $activeNote = Note::where('user_id', $userId)
                ->with('messages')
                ->orderBy('updated_at', 'desc')
                ->first();
        }

        return view('workspace.index', compact('folders', 'flatFolderTree', 'rootNotes', 'activeNote'));
    }
}
