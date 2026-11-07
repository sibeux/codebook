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
        foreach (DB::table('todos')->whereNull('todo_list_id')->orderBy('id')->get(['id', 'user_id', 'todo_project_id']) as $todo) {
            $todoList = DB::table('todo_lists')
                ->where('todo_project_id', $todo->todo_project_id)
                ->whereNull('deleted_at')
                ->orderBy('position')
                ->orderBy('id')
                ->first();

            if ($todoList === null) {
                $todoList = DB::table('todo_lists')
                    ->where('todo_project_id', $todo->todo_project_id)
                    ->orderBy('id')
                    ->first();

                if ($todoList !== null) {
                    DB::table('todo_lists')->where('id', $todoList->id)->update(['deleted_at' => null]);
                } else {
                    $now = now();
                    $todoListId = DB::table('todo_lists')->insertGetId([
                        'user_id' => $todo->user_id,
                        'todo_project_id' => $todo->todo_project_id,
                        'name' => 'To do',
                        'system_status' => null,
                        'color' => '#6366f1',
                        'position' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ]);
                    $todoList = (object) ['id' => $todoListId];
                }
            }

            DB::table('todos')->where('id', $todo->id)->update(['todo_list_id' => $todoList->id]);
        }

        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['todo_list_id']);
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->unsignedBigInteger('todo_list_id')->nullable(false)->change();
            $table->foreign('todo_list_id')->references('id')->on('todo_lists')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['todo_list_id']);
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->unsignedBigInteger('todo_list_id')->nullable()->change();
            $table->foreign('todo_list_id')->references('id')->on('todo_lists')->nullOnDelete();
        });
    }
};
