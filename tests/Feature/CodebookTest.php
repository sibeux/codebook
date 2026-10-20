<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodebookTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_properly(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('SSO Cybeat');
    }

    public function test_authenticated_user_can_access_workspace(): void
    {
        $user = User::create([
            'email' => 'author@sibeux.my.id',
            'name' => 'Author',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $folder = Folder::create([
            'user_id' => $user->user_id,
            'name' => 'General',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'folder_id' => $folder->id,
            'title' => 'First Note',
            'mode' => 'markdown',
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('General');
        $response->assertSee('First Note');
    }

    public function test_user_can_create_and_delete_message(): void
    {
        $user = User::create([
            'email' => 'dev@sibeux.my.id',
            'name' => 'Developer',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'title' => 'Chat Note',
            'mode' => 'markdown',
        ]);

        // Add message
        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/messages", [
            'sender' => 'me',
            'content' => '## Test Markdown Message',
            'sent_time' => '12:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('note_messages', [
            'note_id' => $note->id,
            'sender' => 'me',
        ]);

        $messageId = $response->json('message.id');

        // Update message
        $updateResponse = $this->actingAs($user)->putJson("/notes/{$note->id}/messages/{$messageId}", [
            'content' => '## Updated Markdown Content',
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('success', true);
        $updateResponse->assertJsonPath('message.content', '## Updated Markdown Content');
        $this->assertDatabaseHas('note_messages', [
            'id' => $messageId,
            'content' => '## Updated Markdown Content',
        ]);

        // Delete message
        $deleteResponse = $this->actingAs($user)->deleteJson("/notes/{$note->id}/messages/{$messageId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('note_messages', [
            'id' => $messageId,
        ]);
    }

    public function test_user_cannot_update_message_of_another_user(): void
    {
        $user1 = User::create([
            'email' => 'user1@sibeux.my.id',
            'name' => 'User One',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $user2 = User::create([
            'email' => 'user2@sibeux.my.id',
            'name' => 'User Two',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user1->user_id,
            'title' => 'User 1 Note',
            'mode' => 'markdown',
        ]);

        $message = $note->messages()->create([
            'sender' => 'me',
            'content' => 'Original Content',
            'sent_time' => '10:00',
            'order_index' => 1,
        ]);

        $response = $this->actingAs($user2)->putJson("/notes/{$note->id}/messages/{$message->id}", [
            'content' => 'Malicious Update',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('note_messages', [
            'id' => $message->id,
            'content' => 'Original Content',
        ]);
    }
}
