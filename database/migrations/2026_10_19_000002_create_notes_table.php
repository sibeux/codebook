<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->string('title')->default('Untitled Note');
            $table->enum('mode', ['markdown', 'plaintext'])->default('markdown');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};

