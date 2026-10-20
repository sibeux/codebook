<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShareController extends Controller
{
    /**
     * Display a shared note.
     * Accessible by guests if the note is public, or by the owner if private.
     */
    public function show(string $token): View
    {
        $note = Note::where('share_token', $token)
            ->with(['user', 'messages'])
            ->first();

        if (! $note) {
            abort(404, 'Catatan tidak ditemukan atau tautan share tidak valid.');
        }

        if (! $note->is_public) {
            if (! Auth::check()) {
                abort(403, 'Catatan ini bersifat pribadi. Silakan login sebagai pemilik catatan untuk melihatnya.');
            }

            if (Auth::id() !== $note->user_id) {
                abort(403, 'Catatan ini bersifat pribadi. Anda tidak memiliki izin untuk melihatnya.');
            }
        }

        return view('workspace.share', compact('note'));
    }

    /**
     * Regenerate share token for a note.
     */
    public function regenerate(Request $request, Note $note): JsonResponse
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $note->update([
            'share_token' => Str::random(32),
        ]);

        return response()->json([
            'success' => true,
            'share_token' => $note->share_token,
            'share_url' => $note->share_url,
            'message' => 'Tautan share berhasil diperbarui.',
        ]);
    }
}
