<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('todo_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('name', 80);
            $table->string('system_status')->nullable();
            $table->string('color', 7)->default('#6366f1');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'system_status']);
            $table->index(['user_id', 'position']);
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->foreignId('todo_list_id')->nullable()->after('todo_project_id')->constrained('todo_lists')->nullOnDelete();
        });

        $now = now();
        $systemLists = [
            'todo' => ['To do', '#94a3b8', 0],
            'in_progress' => ['In progress', '#6366f1', 1],
            'blocked' => ['Blocked', '#f59e0b', 2],
            'done' => ['Done', '#10b981', 3],
        ];

        foreach (DB::table('users')->pluck('user_id') as $userId) {
            $listIds = [];

            foreach ($systemLists as $status => [$name, $color, $position]) {
                $listIds[$status] = DB::table('todo_lists')->insertGetId([
                    'user_id' => $userId,
                    'name' => $name,
                    'system_status' => $status,
                    'color' => $color,
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($listIds as $status => $listId) {
                DB::table('todos')
                    ->where('user_id', $userId)
                    ->where('status', $status)
                    ->update(['todo_list_id' => $listId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('todo_list_id');
        });

        Schema::dropIfExists('todo_lists');
    }
};
