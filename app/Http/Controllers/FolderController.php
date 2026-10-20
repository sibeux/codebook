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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null) {
            // Cannot set parent to itself
            if ((int) $validated['parent_id'] === (int) $folder->id) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Folder tidak bisa menjadi parent dari dirinya sendiri.'], 422);
                }

                return back()->withErrors(['parent_id' => 'Folder tidak bisa menjadi parent dari dirinya sendiri.']);
            }

            // Ensure target parent belongs to current user
            $targetParent = Folder::where('id', $validated['parent_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (! $targetParent) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Folder tujuan tidak ditemukan.'], 422);
                }

                return back()->withErrors(['parent_id' => 'Folder tujuan tidak ditemukan.']);
            }

            // Prevent circular reference (cannot move to descendant)
            $curr = $targetParent;
            while ($curr) {
                if ((int) $curr->id === (int) $folder->id) {
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Tidak bisa memindahkan folder ke dalam subfolder miliknya.'], 422);
                    }

                    return back()->withErrors(['parent_id' => 'Tidak bisa memindahkan folder ke dalam subfolder miliknya.']);
                }
                $curr = $curr->parent;
            }
        }

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
