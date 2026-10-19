<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteMessageController extends Controller
{
    public function store(Request $request, Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'sender' => ['required', 'in:me,other'],
            'content' => ['required', 'string'],
            'sent_time' => ['nullable', 'string', 'max:10'],
        ]);

        $orderIndex = ($note->messages()->max('order_index') ?? 0) + 1;

        $message = $note->messages()->create([
            'sender' => $validated['sender'],
            'content' => $validated['content'],
            'sent_time' => $validated['sent_time'] ?? now()->format('H:i'),
            'order_index' => $orderIndex,
        ]);

        $note->touch(); // Refresh updated_at on the note

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function destroy(Note $note, NoteMessage $message)
    {
        if ($note->user_id !== Auth::id() || $message->note_id !== $note->id) {
            abort(403);
        }

        $message->delete();
        $note->touch();

        return response()->json(['success' => true]);
    }

    public function clear(Note $note)
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $note->messages()->delete();
        $note->touch();

        return response()->json(['success' => true]);
    }
}

