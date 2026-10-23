<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NoteMessageController extends Controller
{
    public function store(Request $request, Note $note): JsonResponse
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'sender' => ['required', 'in:me,other'],
            'content' => ['required', 'string'],
            'sent_time' => ['nullable', 'string', 'max:10'],
            'after_message_id' => ['nullable', 'integer', 'exists:note_messages,id'],
        ]);

        $afterMessage = null;
        if (! empty($validated['after_message_id'])) {
            $afterMessage = $note->messages()->where('id', $validated['after_message_id'])->first();
            if (! $afterMessage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan referensi tidak ditemukan pada catatan ini.',
                ], 422);
            }
        }

        $message = DB::transaction(function () use ($note, $validated, $afterMessage) {
            if ($afterMessage) {
                $existingMessages = $note->messages()->orderBy('order_index', 'asc')->orderBy('id', 'asc')->get();
                $targetIndex = $existingMessages->search(fn ($m) => $m->id === $afterMessage->id);

                if ($targetIndex !== false) {
                    $currentPos = 1;
                    $orderIndex = null;
                    foreach ($existingMessages as $idx => $msg) {
                        if ($msg->order_index !== $currentPos) {
                            $msg->updateQuietly(['order_index' => $currentPos]);
                        }
                        $currentPos++;
                        if ($idx === $targetIndex) {
                            $orderIndex = $currentPos;
                            $currentPos++;
                        }
                    }
                } else {
                    $orderIndex = ($note->messages()->max('order_index') ?? 0) + 1;
                }
            } else {
                $orderIndex = ($note->messages()->max('order_index') ?? 0) + 1;
            }

            $created = $note->messages()->create([
                'sender' => $validated['sender'],
                'content' => $validated['content'],
                'sent_time' => $validated['sent_time'] ?? now()->format('H:i'),
                'order_index' => $orderIndex,
            ]);

            $note->touch();

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public function update(Request $request, Note $note, NoteMessage $message): JsonResponse
    {
        if ($note->user_id !== Auth::id() || $message->note_id !== $note->id) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
            'sender' => ['sometimes', 'in:me,other'],
        ]);

        $message->update($validated);
        $note->touch();

        return response()->json([
            'success' => true,
            'message' => $message->fresh(),
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
