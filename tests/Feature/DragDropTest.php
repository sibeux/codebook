<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DragDropTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'email' => 'user@sibeux.my.id',
            'name' => 'Tester',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
    }

    public function test_user_can_move_note_to_folder(): void
    {
        $folder = Folder::create([
            'user_id' => $this->user->user_id,
            'name' => 'Work',
        ]);

        $note = Note::create([
            'user_id' => $this->user->user_id,
            'title' => 'Project Plan',
            'folder_id' => null,
            'mode' => 'markdown',
        ]);

        $response = $this->actingAs($this->user)->putJson("/notes/{$note->id}", [
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'folder_id' => $folder->id,
        ]);
    }

    public function test_user_can_move_note_to_root(): void
    {
        $folder = Folder::create([
            'user_id' => $this->user->user_id,
            'name' => 'Work',
        ]);

        $note = Note::create([
            'user_id' => $this->user->user_id,
            'title' => 'Project Plan',
            'folder_id' => $folder->id,
            'mode' => 'markdown',
        ]);

        $response = $this->actingAs($this->user)->putJson("/notes/{$note->id}", [
            'folder_id' => null,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'folder_id' => null,
        ]);
    }

    public function test_user_cannot_move_note_to_another_users_folder(): void
    {
        $otherUser = User::create([
            'email' => 'other@sibeux.my.id',
            'name' => 'Other User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $otherFolder = Folder::create([
            'user_id' => $otherUser->user_id,
            'name' => 'Secret',
        ]);

        $note = Note::create([
            'user_id' => $this->user->user_id,
            'title' => 'My Note',
            'folder_id' => null,
            'mode' => 'markdown',
        ]);

        $response = $this->actingAs($this->user)->putJson("/notes/{$note->id}", [
            'folder_id' => $otherFolder->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'folder_id' => null,
        ]);
    }

    public function test_user_can_move_folder_into_another_folder(): void
    {
        $parentFolder = Folder::create([
            'user_id' => $this->user->user_id,
            'name' => 'Parent Folder',
        ]);

        $childFolder = Folder::create([
            'user_id' => $this->user->user_id,
            'name' => 'Child Folder',
        ]);

        $response = $this->actingAs($this->user)->putJson("/folders/{$childFolder->id}", [
            'parent_id' => $parentFolder->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('folders', [
            'id' => $childFolder->id,
            'parent_id' => $parentFolder->id,
        ]);
    }

    public function test_user_can_move_folder_to_root(): void
    {
        $parentFolder = Folder::create([
            'user_id' => $this->user->user_id,
            'name' => 'Parent Folder',
        ]);

        $childFolder = Folder::create([
            'user_id' => $this->user->user_id,
            'parent_id' => $parentFolder->id,
            'name' => 'Child Folder',
        ]);

        $response = $this->actingAs($this->user)->putJson("/folders/{$childFolder->id}", [
            'parent_id' => null,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('folders', [
            'id' => $childFolder->id,
            'parent_id' => null,
        ]);
    }

    public function test_user_cannot_move_folder_into_itself(): void
    {
        $folder = Folder::create([
            'user_id' => $this->user->user_id,
            'name' => 'Self Folder',
        ]);

        $response = $this->actingAs($this->user)->putJson("/folders/{$folder->id}", [
            'parent_id' => $folder->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_user_cannot_move_folder_into_its_own_descendant(): void
    {
        $folderA = Folder::create([
            'user_id' => $this->user->user_id,
            'name' => 'Folder A',
        ]);

        $folderB = Folder::create([
            'user_id' => $this->user->user_id,
            'parent_id' => $folderA->id,
            'name' => 'Folder B',
        ]);

        $folderC = Folder::create([
            'user_id' => $this->user->user_id,
            'parent_id' => $folderB->id,
            'name' => 'Folder C',
        ]);

        // Attempt to move Folder A into Folder C
        $response = $this->actingAs($this->user)->putJson("/folders/{$folderA->id}", [
            'parent_id' => $folderC->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }
}
