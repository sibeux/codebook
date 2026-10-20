<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_note_via_share_link_without_login(): void
    {
        $user = User::create([
            'email' => 'owner@sibeux.my.id',
            'name' => 'John Owner',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'title' => 'Dokumentasi Publik API',
            'mode' => 'markdown',
            'is_public' => true,
        ]);

        $note->messages()->create([
            'sender' => 'me',
            'content' => '## Halo Dunia!\nIni adalah isi catatan publik.',
            'sent_time' => '14:30',
            'order_index' => 1,
        ]);

        $this->assertNotEmpty($note->share_token);

        $response = $this->get("/share/{$note->share_token}");

        $response->assertStatus(200);
        $response->assertSee('Dokumentasi Publik API');
        $response->assertSee('John Owner');
        $response->assertSee('Publik');
        $response->assertSee('Halo Dunia!');
    }

    public function test_guest_cannot_view_private_note_via_share_link(): void
    {
        $user = User::create([
            'email' => 'owner@sibeux.my.id',
            'name' => 'Private Owner',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'title' => 'Catatan Rahasia',
            'mode' => 'markdown',
            'is_public' => false,
        ]);

        $response = $this->get("/share/{$note->share_token}");

        $response->assertStatus(403);
    }

    public function test_other_authenticated_user_cannot_view_private_note_via_share_link(): void
    {
        $owner = User::create([
            'email' => 'owner@sibeux.my.id',
            'name' => 'Private Owner',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $otherUser = User::create([
            'email' => 'other@sibeux.my.id',
            'name' => 'Other User',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $owner->user_id,
            'title' => 'Catatan Rahasia Lain',
            'mode' => 'markdown',
            'is_public' => false,
        ]);

        $response = $this->actingAs($otherUser)->get("/share/{$note->share_token}");

        $response->assertStatus(403);
    }

    public function test_owner_can_view_own_private_note_via_share_link(): void
    {
        $owner = User::create([
            'email' => 'owner@sibeux.my.id',
            'name' => 'Owner Person',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $owner->user_id,
            'title' => 'Catatan Milik Sendiri',
            'mode' => 'markdown',
            'is_public' => false,
        ]);

        $response = $this->actingAs($owner)->get("/share/{$note->share_token}");

        $response->assertStatus(200);
        $response->assertSee('Catatan Milik Sendiri');
        $response->assertSee('Pribadi (Hanya Pemilik)');
        $response->assertSee('Buka di Workspace');
    }

    public function test_user_can_create_public_note(): void
    {
        $user = User::create([
            'email' => 'creator@sibeux.my.id',
            'name' => 'Creator',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->postJson('/notes', [
            'title' => 'Catatan Baru Publik',
            'is_public' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('note.is_public', true);
        $this->assertNotEmpty($response->json('note.share_token'));
        $this->assertNotEmpty($response->json('note.share_url'));

        $this->assertDatabaseHas('notes', [
            'title' => 'Catatan Baru Publik',
            'is_public' => 1,
        ]);
    }

    public function test_owner_can_toggle_note_visibility(): void
    {
        $user = User::create([
            'email' => 'toggler@sibeux.my.id',
            'name' => 'Toggler',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'title' => 'Catatan Toggle',
            'mode' => 'markdown',
            'is_public' => false,
        ]);

        $response = $this->actingAs($user)->putJson("/notes/{$note->id}", [
            'is_public' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'is_public' => 1,
        ]);

        // Toggle back to private
        $response2 = $this->actingAs($user)->putJson("/notes/{$note->id}", [
            'is_public' => false,
        ]);

        $response2->assertStatus(200);
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'is_public' => 0,
        ]);
    }

    public function test_owner_can_regenerate_share_token(): void
    {
        $user = User::create([
            'email' => 'regen@sibeux.my.id',
            'name' => 'Regenerator',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $user->user_id,
            'title' => 'Catatan Token',
            'mode' => 'markdown',
            'is_public' => true,
        ]);

        $oldToken = $note->share_token;

        $response = $this->actingAs($user)->postJson("/notes/{$note->id}/share-token");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $newToken = $response->json('share_token');

        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($oldToken, $newToken);

        $note->refresh();
        $this->assertEquals($newToken, $note->share_token);
    }

    public function test_non_owner_cannot_regenerate_share_token(): void
    {
        $owner = User::create([
            'email' => 'owner2@sibeux.my.id',
            'name' => 'Owner Two',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $other = User::create([
            'email' => 'other2@sibeux.my.id',
            'name' => 'Other Two',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $note = Note::create([
            'user_id' => $owner->user_id,
            'title' => 'Catatan Aman',
            'mode' => 'markdown',
            'is_public' => true,
        ]);

        $response = $this->actingAs($other)->postJson("/notes/{$note->id}/share-token");

        $response->assertStatus(403);
    }

    public function test_invalid_share_token_returns_404(): void
    {
        $response = $this->get('/share/invalid-non-existent-token');

        $response->assertStatus(404);
    }
}
