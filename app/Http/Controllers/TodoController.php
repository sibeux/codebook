<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\TodoItem;
use App\Models\TodoProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TodoController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $projects = TodoProject::where('user_id', $user->user_id)
            ->withCount([
                'todos as pending_count' => function ($q) {
                    $q->where('status', '!=', 'done');
                },
            ])
            ->latest()
            ->get();

        $query = Todo::where('user_id', $user->user_id)
            ->with(['project', 'items' => function ($q) {
                $q->latest();
            }]);

        $currentProjectId = $request->integer('project_id');
        $currentProject = $projects->firstWhere('id', $currentProjectId);

        if ($currentProject) {
            $query->where('todo_project_id', $currentProject->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $todos = $query->orderByRaw("CASE WHEN status = 'done' THEN 2 ELSE 1 END")
            ->latest()
            ->get();

        $activeTodoId = $request->filled('active')
            ? $request->integer('active')
            : $todos->first()?->id;
        $activeTodo = $todos->firstWhere('id', $activeTodoId) ?? $todos->first();
        return view('todos.index', [
            'projects' => $projects,
            'todos' => $todos,
            'activeTodo' => $activeTodo,
            'currentProjectId' => $currentProject?->id,
            'currentProject' => $currentProject,
            'currentStatus' => $request->status,
        ]);
    }

    public function storeProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $project = TodoProject::create([
            'user_id' => $request->user()->user_id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
        ]);

        return response()->json([
            'success' => true,
            'project' => $project,
        ]);
    }

    public function deleteProject(Request $request, TodoProject $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->user_id, 403);
        $project->delete();

        return response()->json(['success' => true]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'todo_project_id' => [
                'nullable',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'description' => ['nullable', 'string'],
        ]);

        $todo = Todo::create([
            'user_id' => $request->user()->user_id,
            'todo_project_id' => $validated['todo_project_id'] ?? null,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'description' => $validated['description'] ?? null,
            'status' => 'todo',
        ]);

        $todo->load(['project', 'items']);

        return response()->json([
            'success' => true,
            'todo' => $todo,
        ]);
    }

    public function show(Request $request, Todo $todo): JsonResponse
    {
        abort_unless($todo->user_id === $request->user()->user_id, 403);

        $todo->load(['project', 'items' => function ($q) {
            $q->latest();
        }]);

        return response()->json([
            'success' => true,
            'todo' => $todo,
        ]);
    }

    public function update(Request $request, Todo $todo): JsonResponse
    {
        abort_unless($todo->user_id === $request->user()->user_id, 403);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'in:todo,in_progress,blocked,done'],
            'priority' => ['sometimes', 'required', 'in:low,medium,high,urgent'],
            'todo_project_id' => [
                'sometimes',
                'nullable',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $todo->update($validated);
        $todo->load(['project', 'items']);

        return response()->json([
            'success' => true,
            'todo' => $todo,
        ]);
    }

    public function destroy(Request $request, Todo $todo): JsonResponse
    {
        abort_unless($todo->user_id === $request->user()->user_id, 403);
        $todo->delete();

        return response()->json(['success' => true]);
    }

    public function storeItem(Request $request, Todo $todo): JsonResponse
    {
        abort_unless($todo->user_id === $request->user()->user_id, 403);

        $validated = $request->validate([
            'type' => ['required', 'in:command,note,log'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $item = $todo->items()->create([
            'user_id' => $request->user()->user_id,
            'type' => $validated['type'],
            'content' => $validated['content'],
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'item' => $item,
        ]);
    }

    public function deleteItem(Request $request, TodoItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->user_id, 403);
        $item->delete();

        return response()->json(['success' => true]);
    }
}
