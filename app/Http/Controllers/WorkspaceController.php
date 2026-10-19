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

        // Load root folders with their full recursive children and notes
        $folders = Folder::where('user_id', $userId)
            ->whereNull('parent_id')
            ->with(['children.children.notes', 'children.notes', 'notes'])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

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

        if (!$activeNote) {
            // Pick most recent note if available
            $activeNote = Note::where('user_id', $userId)
                ->with('messages')
                ->orderBy('updated_at', 'desc')
                ->first();
        }

        return view('workspace.index', compact('folders', 'rootNotes', 'activeNote'));
    }
}

