<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\TodoItem;
use App\Models\TodoList;
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
        TodoList::ensureSystemLists($user);

        foreach (TodoList::SYSTEM_LISTS as $status => $attributes) {
            $systemList = TodoList::where('user_id', $user->user_id)
                ->where('system_status', $status)
                ->first();

            Todo::where('user_id', $user->user_id)
                ->where('status', $status)
                ->whereNull('todo_list_id')
                ->update(['todo_list_id' => $systemList->id]);
        }

        $todoLists = TodoList::where('user_id', $user->user_id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $projects = TodoProject::where('user_id', $user->user_id)
            ->withCount([
                'todos as pending_count' => function ($q) {
                    $q->where('status', '!=', 'done');
                },
            ])
            ->latest()
            ->get();

        $query = Todo::where('user_id', $user->user_id)
            ->with(['project', 'todoList', 'items' => function ($q) {
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

        return view('todos.index', [
            'projects' => $projects,
            'todoLists' => $todoLists,
            'todos' => $todos,
            'activeTodo' => null,
            'currentProjectId' => $currentProject?->id,
            'currentProject' => $currentProject,
            'detailOnly' => false,
        ]);
    }

    public function storeList(Request $request): JsonResponse
    {
        TodoList::ensureSystemLists($request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $userId = $request->user()->user_id;
        $position = (int) TodoList::where('user_id', $userId)->max('position') + 1;

        $todoList = TodoList::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
            'position' => $position,
        ]);

        return response()->json(['success' => true, 'list' => $todoList]);
    }

    public function updateList(Request $request, TodoList $todoList): JsonResponse
    {
        abort_unless($todoList->user_id === $request->user()->user_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $todoList->update($validated);

        return response()->json(['success' => true, 'list' => $todoList]);
    }

    public function deleteList(Request $request, TodoList $todoList): JsonResponse
    {
        abort_unless($todoList->user_id === $request->user()->user_id, 403);
        abort_if($todoList->system_status !== null, 422, 'Default lists cannot be deleted.');
        TodoList::ensureSystemLists($request->user());

        $defaultList = TodoList::where('user_id', $request->user()->user_id)
            ->where('system_status', 'todo')
            ->firstOrFail();

        Todo::where('todo_list_id', $todoList->id)->update([
            'todo_list_id' => $defaultList->id,
        ]);
        $todoList->delete();

        return response()->json(['success' => true]);
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
        TodoList::ensureSystemLists($request->user());

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'todo_project_id' => [
                'nullable',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:todo,in_progress,blocked,done'],
            'todo_list_id' => [
                'nullable',
                Rule::exists('todo_lists', 'id')->where('user_id', $request->user()->user_id),
            ],
        ]);

        $status = $validated['status'] ?? 'todo';
        $todoList = isset($validated['todo_list_id'])
            ? TodoList::findOrFail($validated['todo_list_id'])
            : TodoList::where('user_id', $request->user()->user_id)->where('system_status', $status)->firstOrFail();

        $todo = Todo::create([
            'user_id' => $request->user()->user_id,
            'todo_project_id' => $validated['todo_project_id'] ?? null,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'todo_list_id' => $todoList->id,
        ]);

        $todo->load(['project', 'todoList', 'items']);

        return response()->json([
            'success' => true,
            'todo' => $todo,
        ]);
    }

    public function show(Request $request, Todo $todo): View
    {
        abort_unless($todo->user_id === $request->user()->user_id, 403);

        TodoList::ensureSystemLists($request->user());

        if ($todo->todo_list_id === null) {
            $defaultList = TodoList::where('user_id', $request->user()->user_id)
                ->where('system_status', $todo->status)
                ->firstOrFail();
            $todo->update(['todo_list_id' => $defaultList->id]);
        }

        $todo->load(['project', 'todoList', 'items' => function ($q) {
            $q->latest();
        }]);

        $projects = TodoProject::where('user_id', $request->user()->user_id)
            ->withCount([
                'todos as pending_count' => function ($q) {
                    $q->where('status', '!=', 'done');
                },
            ])
            ->latest()
            ->get();
        $todoLists = TodoList::where('user_id', $request->user()->user_id)->orderBy('position')->orderBy('id')->get();
        $currentProjectId = $request->integer('project_id');

        return view('todos.index', [
            'projects' => $projects,
            'todoLists' => $todoLists,
            'todos' => collect(),
            'activeTodo' => $todo,
            'currentProjectId' => $currentProjectId ?: null,
            'currentProject' => $projects->firstWhere('id', $currentProjectId),
            'detailOnly' => true,
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
            'todo_list_id' => [
                'sometimes',
                'nullable',
                Rule::exists('todo_lists', 'id')->where('user_id', $request->user()->user_id),
            ],
        ]);

        $todo->update($validated);
        $todo->load(['project', 'todoList', 'items']);

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
