<?php

use App\Models\Note;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('is_pinned');
            $table->string('share_token', 64)->nullable()->unique()->after('is_public');
        });

        // Ensure any existing notes have a unique share token
        Note::cursor()->each(function (Note $note) {
            if (empty($note->share_token)) {
                $note->forceFill([
                    'share_token' => Str::random(32),
                ])->saveQuietly();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'share_token']);
        });
    }
};
