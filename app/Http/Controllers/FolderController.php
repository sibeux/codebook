<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $folder = Folder::create([
            'user_id' => Auth::id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'folder',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'folder' => $folder,
            ]);
        }

        return redirect()->route('workspace')->with('status', 'Folder berhasil dibuat.');
    }

    public function update(Request $request, Folder $folder)
    {
        if ($folder->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $folder->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'folder' => $folder,
            ]);
        }

        return back()->with('status', 'Folder berhasil diperbarui.');
    }

    public function destroy(Folder $folder)
    {
        if ($folder->user_id !== Auth::id()) {
            abort(403);
        }

        $folder->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('workspace')->with('status', 'Folder berhasil dihapus.');
    }
}

