<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'mode' => ['nullable', 'in:markdown,plaintext'],
        ]);

        $note = Note::create([
            'user_id' => Auth::id(),
            'folder_id' => $validated['folder_id'] ?? null,
            'title' => $validated['title'] ?? 'Untitled Note',
            'mode' => $validated['mode'] ?? 'markdown',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'note' => $note->load('messages'),
            ]);
        }

        return redirect()->route('workspace', ['note' => $note->id]);
    }

    public function show(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'note' => $note->load('messages'),
        ]);
    }

    public function update(Request $request, Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'mode' => ['sometimes', 'in:markdown,plaintext'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);

        $note->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'note' => $note,
            ]);
        }

        return back();
    }

    public function destroy(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $note->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('workspace')->with('status', 'Catatan berhasil dihapus.');
    }
}

