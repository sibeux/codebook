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
        Schema::create('note_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->onDelete('cascade');
            $table->enum('sender', ['me', 'other'])->default('me');
            $table->longText('content');
            $table->integer('order_index')->default(0);
            $table->string('sent_time', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_messages');
    }
};

