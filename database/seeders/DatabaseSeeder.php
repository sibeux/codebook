<?php

namespace Database\Seeders;

use App\Models\Folder;
use App\Models\Note;
use App\Models\NoteMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'nasrul@sibeux.my.id'],
            [
                'name' => 'Nasrul Wahabi',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $folder = Folder::firstOrCreate(
            ['user_id' => $user->user_id, 'name' => 'Projek Codebook'],
            ['icon' => 'folder']
        );

        $subfolder = Folder::firstOrCreate(
            ['user_id' => $user->user_id, 'parent_id' => $folder->id, 'name' => 'Arsitektur'],
            ['icon' => 'folder']
        );

        $note = Note::firstOrCreate(
            ['user_id' => $user->user_id, 'folder_id' => $subfolder->id, 'title' => 'Diskusi Format Markdown Chat'],
            ['mode' => 'markdown']
        );

        if ($note->messages()->count() === 0) {
            NoteMessage::create([
                'note_id' => $note->id,
                'sender' => 'me',
                'content' => "# Inisialisasi Codebook\nCatatan ini menggunakan mode **Markdown Chat** dengan dukungan:",
                'sent_time' => '20:00',
                'order_index' => 1,
            ]);

            NoteMessage::create([
                'note_id' => $note->id,
                'sender' => 'other',
                'content' => "Fitur utama yang sudah siap:\n- **Direktori Folder Bersarang** (Nested Subfolders)\n- **Render Markdown** via Marked.js & DOMPurify\n- **Syntax Highlighting** via Highlight.js\n- **Autentikasi SSO Cybeat** (`https://cybeat.sibeux.my.id/api/auth/login`)",
                'sent_time' => '20:01',
                'order_index' => 2,
            ]);

            NoteMessage::create([
                'note_id' => $note->id,
                'sender' => 'me',
                'content' => "```php\n// Contoh struktur query pesan\n\$messages = \$note->messages()->orderBy('order_index')->get();\n```\nSemua perubahan tersimpan secara real-time ke database.",
                'sent_time' => '20:02',
                'order_index' => 3,
            ]);
        }
    }
}
