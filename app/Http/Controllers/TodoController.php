<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\TodoItem;
use App\Models\TodoList;
use App\Models\TodoProject;
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
        TodoList::ensureSystemLists($user);

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

        $todos = $query->orderBy('position')
            ->orderByRaw("CASE WHEN status = 'done' THEN 2 ELSE 1 END")
            ->latest()
            ->get();
        $activeTodo = $currentProject
            ? $todos->firstWhere('id', $request->integer('active'))
            : null;

        return view('todos.index', [
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
        DB::transaction(function () use ($request, $todoList): void {
            Todo::where('user_id', $request->user()->user_id)
                ->where('todo_list_id', $todoList->id)
                ->update(['todo_list_id' => null]);
            $todoList->delete();
        });

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
                Rule::exists('todo_lists', 'id')
                    ->where('user_id', $request->user()->user_id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        $status = $validated['status'] ?? 'todo';
        $todoList = array_key_exists('todo_list_id', $validated)
            ? (isset($validated['todo_list_id']) ? TodoList::findOrFail($validated['todo_list_id']) : null)
            : TodoList::where('user_id', $request->user()->user_id)->where('system_status', $status)->first();
        $positionQuery = Todo::where('user_id', $request->user()->user_id);

        if ($todoList) {
            $positionQuery->where('todo_list_id', $todoList->id);
        } else {
            $positionQuery->whereNull('todo_list_id');
        }

        $todo = Todo::create([
            'user_id' => $request->user()->user_id,
            'todo_project_id' => $validated['todo_project_id'] ?? null,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'medium',
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'todo_list_id' => $todoList?->id,
            'position' => (int) $positionQuery->max('position') + 1,
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
            $request->only(['project_id', 'status']),
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
                'nullable',
                Rule::exists('todo_projects', 'id')->where('user_id', $request->user()->user_id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'todo_list_id' => [
                'sometimes',
                'nullable',
                Rule::exists('todo_lists', 'id')
                    ->where('user_id', $request->user()->user_id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        $nextTodoListId = $validated['todo_list_id'] ?? null;
        $todoListChanged = array_key_exists('todo_list_id', $validated)
            && ($nextTodoListId === null
                ? $todo->todo_list_id !== null
                : (int) $nextTodoListId !== $todo->todo_list_id);

        if ($todoListChanged) {
            $positionQuery = Todo::where('user_id', $request->user()->user_id);

            if ($nextTodoListId === null) {
                $positionQuery->whereNull('todo_list_id');
            } else {
                $positionQuery->where('todo_list_id', $nextTodoListId);
            }

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
                'nullable',
                Rule::exists('todo_lists', 'id')
                    ->where('user_id', $request->user()->user_id)
                    ->whereNull('deleted_at'),
            ],
            'project_id' => [
                'nullable',
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
