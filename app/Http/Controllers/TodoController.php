<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\TodoItem;
use App\Models\TodoList;
use App\Models\TodoProject;
use App\Models\TodoWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TodoController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $defaultWorkspace = TodoWorkspace::firstOrCreate(
            ['user_id' => $user->user_id, 'is_default' => true],
            ['name' => 'Personal'],
        );
        $workspaces = TodoWorkspace::where('user_id', $user->user_id)
            ->with(['projects' => function ($query) {
                $query->withCount([
                    'todos as pending_count' => function ($todoQuery) {
                        $todoQuery->where('status', '!=', 'done');
                    },
                ])->withCount('todos')->orderBy('name');
            }])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $currentWorkspace = $workspaces->firstWhere('id', $request->integer('workspace_id')) ?? $defaultWorkspace;
        $currentProject = null;

        if ($request->filled('project_id')) {
            $currentProject = TodoProject::where('user_id', $user->user_id)
                ->withCount([
                    'todos as pending_count' => function ($query) {
                        $query->where('status', '!=', 'done');
                    },
                ])
                ->find($request->integer('project_id'));

            if ($currentProject) {
                $currentWorkspace = $workspaces->firstWhere('id', $currentProject->workspace_id) ?? $currentWorkspace;
            }
        }

        $projects = $currentWorkspace->projects;
        $currentProject = $projects->firstWhere('id', $currentProject?->id);
        $todoLists = collect();

        if ($currentProject) {
            TodoList::ensureSystemLists($user, $currentProject);
            $todoLists = TodoList::where('user_id', $user->user_id)
                ->where('todo_project_id', $currentProject->id)
                ->orderBy('position')
                ->orderBy('id')
                ->get();
        }

        $query = Todo::where('user_id', $user->user_id)
            ->with(['project', 'todoList', 'items' => function ($q) {
                $q->latest();
            }]);

        $currentProjectId = $request->integer('project_id');
        $currentProject = $projects->firstWhere('id', $currentProjectId);

        if ($currentProject) {
            $query->where('todo_project_id', $currentProject->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $todos = $query->orderBy('position')
            ->orderByRaw("CASE WHEN status = 'done' THEN 2 ELSE 1 END")
            ->latest()
            ->get();
        $activeTodo = $currentProject
            ? $todos->firstWhere('id', $request->integer('active'))
            : null;

        return view('todos.index', [
            'workspaces' => $workspaces,
            'currentWorkspace' => $currentWorkspace,
            'currentWorkspaceId' => $currentWorkspace->id,
            'projects' => $projects,
            'todoLists' => $todoLists,
            'todos' => $todos,
            'activeTodo' => $activeTodo,
            'currentProjectId' => $currentProject?->id,
            'currentProject' => $currentProject,
            'detailOnly' => false,
        ]);
    }

    public function storeList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'todo_project_id' => [
                'required',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
        ]);

        $userId = $request->user()->user_id;
        $project = TodoProject::where('user_id', $userId)->findOrFail($validated['todo_project_id']);
        TodoList::ensureSystemLists($request->user(), $project);
        $position = (int) TodoList::where('todo_project_id', $project->id)->max('position') + 1;

        $todoList = TodoList::create([
            'user_id' => $userId,
            'todo_project_id' => $project->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
            'position' => $position,
        ]);

        return response()->json(['success' => true, 'list' => $todoList]);
    }

    public function updateList(Request $request, TodoList $todoList): JsonResponse
    {
        abort_unless(
            $todoList->user_id === $request->user()->user_id
                && $todoList->project()->where('user_id', $request->user()->user_id)->exists(),
            403,
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $todoList->update($validated);

        return response()->json(['success' => true, 'list' => $todoList]);
    }

    public function deleteList(Request $request, TodoList $todoList): JsonResponse
    {
        abort_unless(
            $todoList->user_id === $request->user()->user_id
                && $todoList->project()->where('user_id', $request->user()->user_id)->exists(),
            403,
        );
        DB::transaction(function () use ($todoList): void {
            abort_if(
                Todo::where('todo_list_id', $todoList->id)->exists(),
                422,
                'Move or delete every card in this list before deleting it.',
            );

            $todoList->delete();
        });

        return response()->json(['success' => true]);
    }

    public function storeProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'workspace_id' => [
                'required',
                Rule::exists('todo_workspaces', 'id')->where('user_id', $request->user()->user_id),
            ],
        ]);

        $project = TodoProject::create([
            'user_id' => $request->user()->user_id,
            'workspace_id' => $validated['workspace_id'],
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
        ]);

        TodoList::ensureSystemLists($request->user(), $project);

        return response()->json([
            'success' => true,
            'project' => $project,
        ]);
    }

    public function updateProject(Request $request, TodoProject $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->user_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $validated['color'] ??= $project->color;
        $project->update($validated);

        return response()->json(['success' => true, 'project' => $project->fresh()]);
    }

    public function storeWorkspace(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $workspace = TodoWorkspace::create([
            'user_id' => $request->user()->user_id,
            'name' => $validated['name'],
        ]);

        return response()->json(['success' => true, 'workspace' => $workspace]);
    }

    public function updateWorkspace(Request $request, TodoWorkspace $workspace): JsonResponse
    {
        abort_unless($workspace->user_id === $request->user()->user_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $workspace->update($validated);

        return response()->json(['success' => true, 'workspace' => $workspace->fresh()]);
    }

    public function deleteProject(Request $request, TodoProject $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->user_id, 403);
        abort_if($project->todos()->exists(), 422, "Delete this project's cards before deleting the project.");
        $project->delete();

        return response()->json(['success' => true]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'todo_project_id' => [
                'required',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:todo,in_progress,blocked,done'],
            'todo_list_id' => [
                'required',
                Rule::exists('todo_lists', 'id')
                    ->where('todo_project_id', $request->input('todo_project_id'))
                    ->whereNull('deleted_at'),
            ],
        ]);

        $project = TodoProject::where('user_id', $request->user()->user_id)->findOrFail($validated['todo_project_id']);
        TodoList::ensureSystemLists($request->user(), $project);
        $todoList = TodoList::findOrFail($validated['todo_list_id']);
        $status = $validated['status'] ?? $todoList?->system_status ?? 'todo';

        $positionQuery = Todo::where('user_id', $request->user()->user_id)
            ->where('todo_project_id', $project->id);
        $positionQuery->where('todo_list_id', $todoList->id);

        $position = (int) $positionQuery->max('position') + 1;

        $todo = Todo::create([
            'user_id' => $request->user()->user_id,
            'todo_project_id' => $project->id,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'todo_list_id' => $todoList->id,
            'position' => $position,
        ]);

        $todo->load(['project', 'todoList', 'items']);

        return response()->json([
            'success' => true,
            'todo' => $todo,
        ]);
    }

    public function show(Request $request, Todo $todo): RedirectResponse
    {
        abort_unless($todo->user_id === $request->user()->user_id, 403);

        return redirect()->route('todos.index', array_merge(
            [
                'workspace_id' => $todo->project->workspace_id,
                'project_id' => $todo->todo_project_id,
            ],
            $request->only(['status']),
            ['active' => $todo->id],
        ));
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
                'required',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'todo_list_id' => [
                'sometimes',
                'required',
                Rule::exists('todo_lists', 'id')
                    ->where('todo_project_id', $request->input('todo_project_id', $todo->todo_project_id))
                    ->whereNull('deleted_at'),
            ],
        ]);

        $nextProjectId = $validated['todo_project_id'] ?? $todo->todo_project_id;
        $project = TodoProject::where('user_id', $request->user()->user_id)->findOrFail($nextProjectId);
        TodoList::ensureSystemLists($request->user(), $project);

        if (array_key_exists('todo_project_id', $validated) && (int) $nextProjectId !== $todo->todo_project_id) {
            $targetListId = TodoList::where('todo_project_id', $project->id)
                ->where('system_status', $validated['status'] ?? $todo->status)
                ->whereNull('deleted_at')
                ->value('id') ?? TodoList::where('todo_project_id', $project->id)
                ->whereNull('deleted_at')
                ->orderBy('position')
                ->value('id');
            abort_if($targetListId === null, 422, 'Create a list in the target project before moving this card.');
            $validated['todo_list_id'] = $targetListId;
        }

        $nextTodoListId = $validated['todo_list_id'] ?? $todo->todo_list_id;
        $todoListChanged = array_key_exists('todo_list_id', $validated)
            && (int) $nextTodoListId !== $todo->todo_list_id;

        if ($todoListChanged) {
            $positionQuery = Todo::where('user_id', $request->user()->user_id)
                ->where('todo_project_id', $project->id);

            $positionQuery->where('todo_list_id', $nextTodoListId);

            $validated['position'] = (int) $positionQuery->max('position') + 1;
        }

        $todo->update($validated);
        $todo->load(['project', 'todoList', 'items']);

        return response()->json([
            'success' => true,
            'todo' => $todo,
        ]);
    }

    public function reorderCards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'todo_ids' => ['required', 'array', 'min:1'],
            'todo_ids.*' => ['required', 'integer', 'distinct'],
            'todo_list_id' => [
                'present',
                'required',
                Rule::exists('todo_lists', 'id')
                    ->where('todo_project_id', $request->input('project_id'))
                    ->whereNull('deleted_at'),
            ],
            'project_id' => [
                'required',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
            'status' => ['nullable', 'in:todo,in_progress,blocked,done'],
        ]);

        $orderedTodoIds = array_map('intval', $validated['todo_ids']);
        $listTodosQuery = Todo::where('user_id', $request->user()->user_id);

        if ($validated['todo_list_id'] === null) {
            $listTodosQuery->whereNull('todo_list_id');
        } else {
            $listTodosQuery->where('todo_list_id', $validated['todo_list_id']);
        }

        $listTodosQuery->where('todo_project_id', $validated['project_id']);

        $listTodos = $listTodosQuery->orderBy('position')
            ->orderByRaw("CASE WHEN status = 'done' THEN 2 ELSE 1 END")
            ->latest()
            ->get(['id', 'todo_project_id', 'status']);
        $visibleTodoIds = $listTodos
            ->filter(function (Todo $listTodo) use ($validated): bool {
                if (isset($validated['project_id']) && (int) $listTodo->todo_project_id !== (int) $validated['project_id']) {
                    return false;
                }

                return ! isset($validated['status']) || $listTodo->status === $validated['status'];
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $expectedTodoIds = $visibleTodoIds;
        $submittedTodoIds = $orderedTodoIds;
        sort($expectedTodoIds);
        sort($submittedTodoIds);

        if ($expectedTodoIds !== $submittedTodoIds) {
            return response()->json([
                'message' => 'The order must include every visible card in this list.',
            ], 422);
        }

        $visibleTodoIdSet = array_fill_keys($visibleTodoIds, true);
        $orderedVisibleIndex = 0;
        $completeTodoOrder = $listTodos->map(function (Todo $listTodo) use ($orderedTodoIds, $visibleTodoIdSet, &$orderedVisibleIndex): int {
            if (isset($visibleTodoIdSet[$listTodo->id])) {
                return $orderedTodoIds[$orderedVisibleIndex++];
            }

            return (int) $listTodo->id;
        })->all();

        DB::transaction(function () use ($completeTodoOrder, $request, $validated): void {
            foreach ($completeTodoOrder as $position => $todoId) {
                $todoQuery = Todo::where('user_id', $request->user()->user_id)->whereKey($todoId);

                if ($validated['todo_list_id'] === null) {
                    $todoQuery->whereNull('todo_list_id');
                } else {
                    $todoQuery->where('todo_list_id', $validated['todo_list_id']);
                }

                $todoQuery->where('todo_project_id', $validated['project_id']);

                $todoQuery->update(['position' => $position]);
            }
        });

        return response()->json(['success' => true]);
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

    public function updateItem(Request $request, TodoItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->user_id, 403);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'item' => $item->fresh(),
        ]);
    }
}
