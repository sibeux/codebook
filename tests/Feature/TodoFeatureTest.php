<?php

namespace Tests\Feature;

use App\Models\Todo;
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
}
