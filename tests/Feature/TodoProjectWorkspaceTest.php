<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\TodoList;
use App\Models\TodoProject;
use App\Models\TodoWorkspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoProjectWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'dev@sibeux.my.id'): User
    {
        return User::create([
            'email' => $email,
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
    }

    public function test_user_can_move_project_to_another_workspace(): void
    {
        $user = $this->makeUser();

        $workspaceA = TodoWorkspace::create([
            'user_id' => $user->user_id,
            'name' => 'Personal',
            'is_default' => true,
        ]);

        $workspaceB = TodoWorkspace::create([
            'user_id' => $user->user_id,
            'name' => 'Work',
            'is_default' => false,
        ]);

        $project = TodoProject::create([
            'user_id' => $user->user_id,
            'workspace_id' => $workspaceA->id,
            'name' => 'Website Redesign',
            'color' => '#6366f1',
        ]);

        $todoList = TodoList::create([
            'user_id' => $user->user_id,
            'todo_project_id' => $project->id,
            'name' => 'In Progress',
            'color' => '#3b82f6',
            'position' => 0,
            'is_system' => false,
        ]);

        $todo = Todo::create([
            'user_id' => $user->user_id,
            'todo_project_id' => $project->id,
            'todo_list_id' => $todoList->id,
            'title' => 'Design wireframe',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->putJson(route('todos.projects.update', $project), [
            'name' => 'Website Redesign V2',
            'color' => '#10b981',
            'workspace_id' => $workspaceB->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('project.workspace_id', $workspaceB->id)
            ->assertJsonPath('project.name', 'Website Redesign V2')
            ->assertJsonPath('project.color', '#10b981');

        $this->assertDatabaseHas('todo_projects', [
            'id' => $project->id,
            'workspace_id' => $workspaceB->id,
            'name' => 'Website Redesign V2',
            'color' => '#10b981',
        ]);

        // Verify task and list relationship remains intact
        $this->assertSame($project->id, $todo->fresh()->todo_project_id);
        $this->assertSame($project->id, $todoList->fresh()->todo_project_id);
    }

    public function test_user_cannot_move_project_to_workspace_owned_by_another_user(): void
    {
        $userA = $this->makeUser('userA@sibeux.my.id');
        $userB = $this->makeUser('userB@sibeux.my.id');

        $workspaceA = TodoWorkspace::create([
            'user_id' => $userA->user_id,
            'name' => 'Personal A',
            'is_default' => true,
        ]);

        $workspaceB = TodoWorkspace::create([
            'user_id' => $userB->user_id,
            'name' => 'Work B',
            'is_default' => true,
        ]);

        $project = TodoProject::create([
            'user_id' => $userA->user_id,
            'workspace_id' => $workspaceA->id,
            'name' => 'Secret Project A',
            'color' => '#6366f1',
        ]);

        $response = $this->actingAs($userA)->putJson(route('todos.projects.update', $project), [
            'name' => 'Secret Project A',
            'workspace_id' => $workspaceB->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_id']);

        $this->assertDatabaseHas('todo_projects', [
            'id' => $project->id,
            'workspace_id' => $workspaceA->id,
        ]);
    }

    public function test_user_cannot_update_another_users_project(): void
    {
        $userA = $this->makeUser('userA@sibeux.my.id');
        $userB = $this->makeUser('userB@sibeux.my.id');

        $workspaceA = TodoWorkspace::create([
            'user_id' => $userA->user_id,
            'name' => 'Personal A',
            'is_default' => true,
        ]);

        $project = TodoProject::create([
            'user_id' => $userA->user_id,
            'workspace_id' => $workspaceA->id,
            'name' => 'Project A',
            'color' => '#6366f1',
        ]);

        $response = $this->actingAs($userB)->putJson(route('todos.projects.update', $project), [
            'name' => 'Hacked Project',
        ]);

        $response->assertStatus(403);
    }

    public function test_todos_page_renders_workspace_select_and_move_action(): void
    {
        $user = $this->makeUser();

        $workspaceA = TodoWorkspace::create([
            'user_id' => $user->user_id,
            'name' => 'Personal',
            'is_default' => true,
        ]);

        $project = TodoProject::create([
            'user_id' => $user->user_id,
            'workspace_id' => $workspaceA->id,
            'name' => 'My Project',
            'color' => '#6366f1',
        ]);

        $response = $this->actingAs($user)->get(route('todos.index', [
            'workspace_id' => $workspaceA->id,
            'project_id' => $project->id,
        ]));

        $response->assertOk();
        $response->assertSee('id="project-workspace-id"', false);
        $response->assertSee('Move to workspace', false);
    }
}
