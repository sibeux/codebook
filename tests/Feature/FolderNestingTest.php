<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderNestingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'email' => 'nested@sibeux.my.id',
            'name' => 'Tester Nesting',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
    }

    public function test_user_can_create_folders_up_to_max_depth_of_10(): void
    {
        $currentParentId = null;
        $folders = [];

        // Create folders from level 1 to level 10
        for ($depth = 1; $depth <= 10; $depth++) {
            $response = $this->actingAs($this->user)->postJson('/folders', [
                'name' => "Folder Level {$depth}",
                'parent_id' => $currentParentId,
            ]);

            $response->assertStatus(200);
            $response->assertJsonPath('success', true);
            $response->assertJsonPath('depth', $depth);

            $folderId = $response->json('folder.id');
            $folders[$depth] = $folderId;
            $currentParentId = $folderId;

            $this->assertDatabaseHas('folders', [
                'id' => $folderId,
                'name' => "Folder Level {$depth}",
                'parent_id' => $depth === 1 ? null : $folders[$depth - 1],
            ]);
        }

        $this->assertCount(10, $folders);
    }

    public function test_user_cannot_create_folder_exceeding_max_depth_of_10(): void
    {
        $currentParentId = null;

        // Build a chain of 10 folders
        for ($depth = 1; $depth <= 10; $depth++) {
            $folder = Folder::create([
                'user_id' => $this->user->user_id,
                'name' => "Folder Level {$depth}",
                'parent_id' => $currentParentId,
            ]);
            $currentParentId = $folder->id;
        }

        // Now attempt to create level 11 under the level 10 folder
        $response = $this->actingAs($this->user)->postJson('/folders', [
            'name' => 'Folder Level 11 (Forbidden)',
            'parent_id' => $currentParentId,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Maksimal kedalaman folder adalah 10 level.');

        $this->assertDatabaseMissing('folders', [
            'name' => 'Folder Level 11 (Forbidden)',
        ]);
    }

    public function test_user_cannot_move_folder_if_it_exceeds_max_depth(): void
    {
        // Chain A: Depth 6 (Level 1 to 6)
        $parentA = null;
        $folderA6 = null;
        for ($i = 1; $i <= 6; $i++) {
            $folder = Folder::create([
                'user_id' => $this->user->user_id,
                'name' => "Branch A Level {$i}",
                'parent_id' => $parentA,
            ]);
            $parentA = $folder->id;
            if ($i === 6) {
                $folderA6 = $folder;
            }
        }

        // Chain B: Depth 5 (Level 1 to 5)
        $parentB = null;
        $folderB1 = null;
        for ($i = 1; $i <= 5; $i++) {
            $folder = Folder::create([
                'user_id' => $this->user->user_id,
                'name' => "Branch B Level {$i}",
                'parent_id' => $parentB,
            ]);
            if ($i === 1) {
                $folderB1 = $folder;
            }
            $parentB = $folder->id;
        }

        // Chain B has subtree height of 5 (from B1 down to B5).
        // If we move B1 into Branch A Level 6:
        // Resulting depth of Branch B Level 5 would be 6 + 5 = 11 > 10!
        $response = $this->actingAs($this->user)->putJson("/folders/{$folderB1->id}", [
            'parent_id' => $folderA6->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Maksimal kedalaman folder adalah 10 level.');
    }

    public function test_user_can_move_folder_when_within_max_depth(): void
    {
        // Chain A: Depth 5 (Level 1 to 5)
        $parentA = null;
        $folderA5 = null;
        for ($i = 1; $i <= 5; $i++) {
            $folder = Folder::create([
                'user_id' => $this->user->user_id,
                'name' => "Branch A Level {$i}",
                'parent_id' => $parentA,
            ]);
            $parentA = $folder->id;
            if ($i === 5) {
                $folderA5 = $folder;
            }
        }

        // Chain B: Depth 4 (Level 1 to 4)
        $parentB = null;
        $folderB1 = null;
        for ($i = 1; $i <= 4; $i++) {
            $folder = Folder::create([
                'user_id' => $this->user->user_id,
                'name' => "Branch B Level {$i}",
                'parent_id' => $parentB,
            ]);
            if ($i === 1) {
                $folderB1 = $folder;
            }
            $parentB = $folder->id;
        }

        // Subtree height of B is 4. Moving B1 into A5 results in depth 5 + 4 = 9 <= 10.
        $response = $this->actingAs($this->user)->putJson("/folders/{$folderB1->id}", [
            'parent_id' => $folderA5->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('folders', [
            'id' => $folderB1->id,
            'parent_id' => $folderA5->id,
        ]);
    }

    public function test_user_cannot_create_subfolder_in_another_users_folder(): void
    {
        $otherUser = User::create([
            'email' => 'other_user@sibeux.my.id',
            'name' => 'Other User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $otherFolder = Folder::create([
            'user_id' => $otherUser->user_id,
            'name' => 'Other User Folder',
            'parent_id' => null,
        ]);

        $response = $this->actingAs($this->user)->postJson('/folders', [
            'name' => 'Sneaky Subfolder',
            'parent_id' => $otherFolder->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Folder induk tidak ditemukan.');
    }

    public function test_workspace_view_renders_deeply_nested_folders(): void
    {
        $currentParentId = null;

        for ($depth = 1; $depth <= 10; $depth++) {
            $folder = Folder::create([
                'user_id' => $this->user->user_id,
                'name' => "Level_{$depth}_Folder",
                'parent_id' => $currentParentId,
            ]);
            $currentParentId = $folder->id;
        }

        // Put a note inside the deepest folder (level 10)
        $deepNote = Note::create([
            'user_id' => $this->user->user_id,
            'folder_id' => $currentParentId,
            'title' => 'Deepest Note in Level 10',
            'mode' => 'markdown',
        ]);

        $response = $this->actingAs($this->user)->get('/');
        $response->assertStatus(200);

        for ($depth = 1; $depth <= 10; $depth++) {
            $response->assertSee("Level_{$depth}_Folder");
        }

        $response->assertSee('Deepest Note in Level 10');
    }
}
