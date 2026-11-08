<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_user_with_expired_sso_jwt_is_logged_out_and_redirected_to_login(): void
    {
        Http::fake([
            'cybeat.sibeux.my.id/api/auth/refresh' => Http::response(['status' => 'error'], 401),
        ]);

        $user = User::create([
            'email' => 'expired@sibeux.my.id',
            'name' => 'Expired User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
        $payload = rtrim(strtr(base64_encode(json_encode(['exp' => time() - 60])), '+/', '-_'), '=');
        $expiredToken = 'header.'.$payload.'.signature';

        $response = $this->actingAs($user)->withSession([
            'sso_access_token' => $expiredToken,
            'sso_refresh_token' => 'refresh-token',
        ])->get('/');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_with_expired_access_token_gets_a_new_token_pair(): void
    {
        Http::fake([
            'cybeat.sibeux.my.id/api/auth/refresh' => Http::response([
                'status' => 'success',
                'access_token' => 'new-access-token',
                'refresh_token' => 'rotated-refresh-token',
            ]),
        ]);

        $user = User::create([
            'email' => 'refreshed@sibeux.my.id',
            'name' => 'Refreshed User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
        $payload = rtrim(strtr(base64_encode(json_encode(['exp' => time() - 60])), '+/', '-_'), '=');
        $expiredToken = 'header.'.$payload.'.signature';

        $response = $this->actingAs($user)->withSession([
            'sso_access_token' => $expiredToken,
            'sso_refresh_token' => 'current-refresh-token',
        ])->get('/');

        $response->assertOk();
        $response->assertSessionHas('sso_access_token', 'new-access-token');
        $response->assertSessionHas('sso_refresh_token', 'rotated-refresh-token');
        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === 'https://cybeat.sibeux.my.id/api/auth/refresh'
            && $request->hasHeader('Authorization', 'Bearer current-refresh-token')
            && $request->body() === '');
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

    public function test_user_can_insert_message_below_another_message(): void
    {
        $user = User::create([
            'email' => 'insert_test@sibeux.my.id',
            'name' => 'Insert Tester',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'title' => 'Thread Note',
            'mode' => 'markdown',
        ]);

        $msg1 = $note->messages()->create([
            'sender' => 'me',
            'content' => 'First Message',
            'sent_time' => '10:00',
            'order_index' => 1,
        ]);

        $msg2 = $note->messages()->create([
            'sender' => 'other',
            'content' => 'Second Message',
            'sent_time' => '10:01',
            'order_index' => 2,
        ]);

        // Insert new message below msg1 (between msg1 and msg2)
        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/messages", [
            'sender' => 'me',
            'content' => 'Inserted Message Between 1 and 2',
            'sent_time' => '10:00:30',
            'after_message_id' => $msg1->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $insertedId = $response->json('message.id');

        $messages = $note->fresh()->messages()->get();
        $this->assertCount(3, $messages);
        $this->assertEquals($msg1->id, $messages[0]->id);
        $this->assertEquals($insertedId, $messages[1]->id);
        $this->assertEquals($msg2->id, $messages[2]->id);

        $this->assertEquals(1, $messages[0]->order_index);
        $this->assertEquals(2, $messages[1]->order_index);
        $this->assertEquals(3, $messages[2]->order_index);
    }

    public function test_user_can_create_and_fetch_message_containing_links(): void
    {
        $user = User::create([
            'email' => 'link_test@sibeux.my.id',
            'name' => 'Link Tester',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'title' => 'Link Note',
            'mode' => 'markdown',
        ]);

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/messages", [
            'sender' => 'me',
            'content' => 'Silakan cek website [Codebook](https://codebook.example.com) dan link mentah https://google.com',
            'sent_time' => '12:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message.content', 'Silakan cek website [Codebook](https://codebook.example.com) dan link mentah https://google.com');

        $showResponse = $this->actingAs($user)->getJson("/notes/{$note->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('note.messages.0.content', 'Silakan cek website [Codebook](https://codebook.example.com) dan link mentah https://google.com');
    }
}
