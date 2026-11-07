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
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['todo_list_id']);
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->unsignedBigInteger('todo_list_id')->nullable()->change();
            $table->foreign('todo_list_id')->references('id')->on('todo_lists')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('todos')->whereNull('todo_list_id')->orderBy('id')->get(['id', 'todo_project_id', 'status']) as $todo) {
            $todoList = DB::table('todo_lists')
                ->where('todo_project_id', $todo->todo_project_id)
                ->where('system_status', $todo->status)
                ->orderBy('id')
                ->first();

            if ($todoList === null) {
                $todoList = DB::table('todo_lists')
                    ->where('todo_project_id', $todo->todo_project_id)
                    ->orderBy('id')
                    ->first();
            }

            if ($todoList === null) {
                throw new RuntimeException('Cannot restore required list placement for todo '.$todo->id.'.');
            }

            if ($todoList->deleted_at !== null) {
                DB::table('todo_lists')->where('id', $todoList->id)->update(['deleted_at' => null]);
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
};
