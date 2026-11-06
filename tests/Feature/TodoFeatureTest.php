<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\TodoList;
use App\Models\TodoProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'email' => 'dev@sibeux.my.id',
            'name' => 'Dev User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
    }

    public function test_user_can_access_todos_page(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('todos.index'));

        $response->assertStatus(200);
        $response->assertSee('Tasks & Projects');
    }

    public function test_todos_page_renders_the_task_selected_by_active_query_parameter(): void
    {
        $user = $this->makeUser();
        $firstTodo = Todo::create([
            'user_id' => $user->user_id,
            'title' => 'First task',
            'description' => 'First task description',
        ]);
        $selectedTodo = Todo::create([
            'user_id' => $user->user_id,
            'title' => 'Selected task',
            'description' => 'Selected task description',
        ]);

        $response = $this->actingAs($user)->get(route('todos.index', ['active' => $selectedTodo->id]));

        $response->assertOk();
        $response->assertSee('Selected task description');
        $response->assertDontSee('First task description');
        $response->assertSee('task-card-'.$selectedTodo->id.'"', false);
        $this->assertNotSame($firstTodo->id, $selectedTodo->id);
    }

    public function test_user_can_create_project_and_task(): void
    {
        $user = $this->makeUser();

        // Create project
        $resProject = $this->actingAs($user)->postJson(route('todos.projects.store'), [
            'name' => 'Backend API',
            'color' => '#3b82f6',
        ]);
        $resProject->assertStatus(200);
        $this->assertDatabaseHas('todo_projects', ['name' => 'Backend API']);

        $project = TodoProject::first();

        // Create task
        $resTodo = $this->actingAs($user)->postJson(route('todos.store'), [
            'title' => 'Setup Redis Queue',
            'todo_project_id' => $project->id,
            'priority' => 'high',
        ]);
        $resTodo->assertStatus(200);
        $this->assertDatabaseHas('todos', ['title' => 'Setup Redis Queue']);

        $todo = Todo::first();

        // Add quick command
        $resCmd = $this->actingAs($user)->postJson(route('todos.items.store', $todo), [
            'type' => 'command',
            'content' => 'docker compose up -d redis',
        ]);
        $resCmd->assertStatus(200);
        $this->assertDatabaseHas('todo_items', ['content' => 'docker compose up -d redis', 'type' => 'command']);
    }

    public function test_new_task_form_shows_the_current_project_context(): void
    {
        $user = $this->makeUser();
        $project = TodoProject::create([
            'user_id' => $user->user_id,
            'name' => 'Current project',
            'color' => '#3b82f6',
        ]);

        $response = $this->actingAs($user)->get(route('todos.index', ['project_id' => $project->id]));

        $response->assertOk();
        $response->assertSee('Adding to project:');
        $response->assertSee('Current project');
        $response->assertSee('<option value="'.$project->id.'" selected>', false);
        $response->assertSee('Tasks');
        $response->assertSee('/ Current project');
    }

    public function test_updating_status_preserves_existing_description(): void
    {
        $user = $this->makeUser();
        $todo = Todo::create([
            'user_id' => $user->user_id,
            'title' => 'Keep context',
            'description' => 'This description must remain.',
        ]);

        $response = $this->actingAs($user)->putJson(route('todos.update', $todo), [
            'status' => 'in_progress',
        ]);

        $response->assertOk()->assertJsonPath('todo.status', 'in_progress');
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'description' => 'This description must remain.',
            'status' => 'in_progress',
        ]);
    }

    public function test_user_can_update_a_task_title(): void
    {
        $user = $this->makeUser();
        $todo = Todo::create([
            'user_id' => $user->user_id,
            'title' => 'Original title',
        ]);

        $response = $this->actingAs($user)->putJson(route('todos.update', $todo), [
            'title' => 'Updated title',
        ]);

        $response->assertOk()->assertJsonPath('todo.title', 'Updated title');
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_task_creation_rejects_a_project_owned_by_another_user(): void
    {
        $user = $this->makeUser();
        $otherUser = User::create([
            'email' => 'other@sibeux.my.id',
            'name' => 'Other User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
        $otherProject = TodoProject::create([
            'user_id' => $otherUser->user_id,
            'name' => 'Private project',
        ]);

        $response = $this->actingAs($user)->postJson(route('todos.store'), [
            'title' => 'Unauthorized project assignment',
            'todo_project_id' => $otherProject->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('todo_project_id');
        $this->assertDatabaseMissing('todos', [
            'title' => 'Unauthorized project assignment',
        ]);
    }

    public function test_task_update_rejects_a_project_owned_by_another_user(): void
    {
        $user = $this->makeUser();
        $todo = Todo::create([
            'user_id' => $user->user_id,
            'title' => 'Protected assignment',
        ]);
        $otherUser = User::create([
            'email' => 'project-owner@sibeux.my.id',
            'name' => 'Project Owner',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
        $otherProject = TodoProject::create([
            'user_id' => $otherUser->user_id,
            'name' => 'Restricted project',
        ]);

        $response = $this->actingAs($user)->putJson(route('todos.update', $todo), [
            'todo_project_id' => $otherProject->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('todo_project_id');
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'todo_project_id' => null,
        ]);
    }

    public function test_user_can_delete_all_default_lists_and_keep_tasks_unassigned(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('todos.index'))->assertOk();

        $todoList = TodoList::where('user_id', $user->user_id)
            ->where('system_status', 'todo')
            ->firstOrFail();
        $todo = Todo::create([
            'user_id' => $user->user_id,
            'todo_list_id' => $todoList->id,
            'title' => 'Keep this task',
        ]);
        $todoLists = TodoList::where('user_id', $user->user_id)->get();

        foreach ($todoLists as $todoList) {
            $this->deleteJson(route('todos.lists.destroy', $todoList))
                ->assertOk()
                ->assertJsonPath('success', true);

            $this->assertDatabaseMissing('todo_lists', [
                'id' => $todoList->id,
                'deleted_at' => null,
            ]);
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'todo_list_id' => null,
        ]);

        $this->putJson(route('todos.update', $todo), [
            'todo_list_id' => $todoLists->first()->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('todo_list_id');

        $this->postJson(route('todos.store'), [
            'title' => 'New unassigned task',
            'todo_list_id' => null,
        ])->assertOk()->assertJsonPath('todo.todo_list_id', null);

        $response = $this->get(route('todos.index'));

        $response->assertOk()
            ->assertSee('Unassigned')
            ->assertSee('Keep this task');
        $this->assertSame(0, TodoList::where('user_id', $user->user_id)->count());
        $this->assertDatabaseHas('todos', [
            'title' => 'New unassigned task',
            'todo_list_id' => null,
        ]);
    }

    public function test_user_cannot_delete_another_users_list(): void
    {
        $owner = $this->makeUser();
        $this->actingAs($owner)->get(route('todos.index'))->assertOk();
        $otherUser = User::create([
            'email' => 'list-owner@sibeux.my.id',
            'name' => 'List Owner',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
        $otherList = TodoList::create([
            'user_id' => $otherUser->user_id,
            'name' => 'Private list',
        ]);

        $response = $this->actingAs($owner)->deleteJson(route('todos.lists.destroy', $otherList));

        $response->assertForbidden();
        $this->assertDatabaseHas('todo_lists', [
            'id' => $otherList->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_reorder_cards_within_a_list(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('todos.index'))->assertOk();
        $todoList = TodoList::where('user_id', $user->user_id)
            ->where('system_status', 'todo')
            ->firstOrFail();
        $firstTodo = Todo::create([
            'user_id' => $user->user_id,
            'todo_list_id' => $todoList->id,
            'title' => 'First card',
            'position' => 0,
        ]);
        $secondTodo = Todo::create([
            'user_id' => $user->user_id,
            'todo_list_id' => $todoList->id,
            'title' => 'Second card',
            'position' => 1,
        ]);

        $response = $this->actingAs($user)->putJson(route('todos.cards.reorder'), [
            'todo_ids' => [$secondTodo->id, $firstTodo->id],
            'todo_list_id' => $todoList->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(
            [$secondTodo->id, $firstTodo->id],
            Todo::where('todo_list_id', $todoList->id)->orderBy('position')->pluck('id')->all(),
        );
        $this->assertDatabaseHas('todos', ['id' => $secondTodo->id, 'position' => 0]);
        $this->assertDatabaseHas('todos', ['id' => $firstTodo->id, 'position' => 1]);
    }

    public function test_reordering_a_list_rejects_cards_owned_by_another_user(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('todos.index'))->assertOk();
        $todoList = TodoList::where('user_id', $user->user_id)
            ->where('system_status', 'todo')
            ->firstOrFail();
        $todo = Todo::create([
            'user_id' => $user->user_id,
            'todo_list_id' => $todoList->id,
            'title' => 'Private card',
            'position' => 0,
        ]);
        $otherUser = User::create([
            'email' => 'reorder-owner@sibeux.my.id',
            'name' => 'Other User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
        $otherTodo = Todo::create([
            'user_id' => $otherUser->user_id,
            'title' => 'Other users card',
            'position' => 0,
        ]);

        $response = $this->putJson(route('todos.cards.reorder'), [
            'todo_ids' => [$todo->id, $otherTodo->id],
            'todo_list_id' => $todoList->id,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'position' => 0,
            'todo_list_id' => $todoList->id,
        ]);
    }

    public function test_reordering_project_filtered_cards_preserves_hidden_card_positions(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('todos.index'))->assertOk();
        $todoList = TodoList::where('user_id', $user->user_id)
            ->where('system_status', 'todo')
            ->firstOrFail();
        $project = TodoProject::create([
            'user_id' => $user->user_id,
            'name' => 'Visible project',
        ]);
        $otherProject = TodoProject::create([
            'user_id' => $user->user_id,
            'name' => 'Hidden project',
        ]);
        $firstVisibleTodo = Todo::create([
            'user_id' => $user->user_id,
            'todo_project_id' => $project->id,
            'todo_list_id' => $todoList->id,
            'title' => 'First visible card',
            'position' => 0,
        ]);
        $hiddenTodo = Todo::create([
            'user_id' => $user->user_id,
            'todo_project_id' => $otherProject->id,
            'todo_list_id' => $todoList->id,
            'title' => 'Hidden card',
            'position' => 1,
        ]);
        $secondVisibleTodo = Todo::create([
            'user_id' => $user->user_id,
            'todo_project_id' => $project->id,
            'todo_list_id' => $todoList->id,
            'title' => 'Second visible card',
            'position' => 2,
        ]);

        $response = $this->putJson(route('todos.cards.reorder'), [
            'todo_ids' => [$secondVisibleTodo->id, $firstVisibleTodo->id],
            'todo_list_id' => $todoList->id,
            'project_id' => $project->id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(
            [$secondVisibleTodo->id, $hiddenTodo->id, $firstVisibleTodo->id],
            Todo::where('todo_list_id', $todoList->id)->orderBy('position')->pluck('id')->all(),
        );
    }

    public function test_user_can_reorder_unassigned_cards(): void
    {
        $user = $this->makeUser();
        $firstTodo = Todo::create([
            'user_id' => $user->user_id,
            'title' => 'First unassigned card',
            'position' => 0,
        ]);
        $secondTodo = Todo::create([
            'user_id' => $user->user_id,
            'title' => 'Second unassigned card',
            'position' => 1,
        ]);

        $response = $this->actingAs($user)->putJson(route('todos.cards.reorder'), [
            'todo_ids' => [$secondTodo->id, $firstTodo->id],
            'todo_list_id' => null,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(
            [$secondTodo->id, $firstTodo->id],
            Todo::whereNull('todo_list_id')->orderBy('position')->pluck('id')->all(),
        );

        $this->actingAs($user)->get(route('todos.index'))
            ->assertOk()
            ->assertSee('ondrop="dropTodoCard(event, null)"', false)
            ->assertSee('draggable="true" ondragstart="startTodoDrag(event, '.$firstTodo->id.')"', false);
    }
}
