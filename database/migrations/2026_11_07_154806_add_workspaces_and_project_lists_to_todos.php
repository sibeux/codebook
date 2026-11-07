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
        Schema::create('todo_workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'is_default']);
        });

        foreach (DB::table('users')->get(['user_id']) as $user) {
            DB::table('todo_workspaces')->insert([
                'user_id' => $user->user_id,
                'name' => 'Personal',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('todo_projects', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained('todo_workspaces')->cascadeOnDelete();
        });

        foreach (DB::table('todo_workspaces')->get(['id', 'user_id']) as $workspace) {
            DB::table('todo_projects')
                ->where('user_id', $workspace->user_id)
                ->update(['workspace_id' => $workspace->id]);
        }

        Schema::table('todo_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id')->nullable(false)->change();
        });

        Schema::table('todo_lists', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'system_status']);
            $table->foreignId('todo_project_id')->nullable()->after('user_id')->constrained('todo_projects')->cascadeOnDelete();
        });

        $systemLists = [
            'todo' => ['To do', '#94a3b8', 0],
            'in_progress' => ['In progress', '#6366f1', 1],
            'blocked' => ['Blocked', '#f59e0b', 2],
            'done' => ['Done', '#10b981', 3],
        ];

        foreach (DB::table('users')->get(['user_id']) as $user) {
            $projects = DB::table('todo_projects')->where('user_id', $user->user_id)->orderBy('id')->get();
            $lists = DB::table('todo_lists')->where('user_id', $user->user_id)->orderBy('id')->get();
            $hasUnassignedCards = DB::table('todos')
                ->where('user_id', $user->user_id)
                ->whereNull('todo_project_id')
                ->exists();

            if ($hasUnassignedCards || ($projects->isEmpty() && $lists->isNotEmpty())) {
                $workspaceId = DB::table('todo_workspaces')
                    ->where('user_id', $user->user_id)
                    ->where('is_default', true)
                    ->value('id');
                $now = now();
                $projectId = DB::table('todo_projects')->insertGetId([
                    'user_id' => $user->user_id,
                    'workspace_id' => $workspaceId,
                    'name' => 'Inbox',
                    'color' => '#6366f1',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $projects = $projects->push(DB::table('todo_projects')->where('id', $projectId)->first());
            }

            $listIdsByProject = [];
            $systemListIdsByProject = [];
            $deletedListIds = array_fill_keys(
                array_map('intval', $lists->whereNotNull('deleted_at')->pluck('id')->all()),
                true,
            );

            foreach ($projects as $projectIndex => $project) {
                foreach ($lists as $list) {
                    if ($list->system_status !== null && $list->deleted_at !== null) {
                        continue;
                    }

                    $attributes = [
                        'todo_project_id' => $project->id,
                        'name' => $list->name,
                        'system_status' => $list->system_status,
                        'color' => $list->color,
                        'position' => $list->position,
                        'created_at' => $list->created_at,
                        'updated_at' => $list->updated_at,
                        'deleted_at' => $list->deleted_at,
                    ];

                    if ($projectIndex === 0) {
                        DB::table('todo_lists')->where('id', $list->id)->update($attributes);
                        $listId = $list->id;
                    } else {
                        $listId = DB::table('todo_lists')->insertGetId([
                            'user_id' => $user->user_id,
                            ...$attributes,
                        ]);
                    }

                    $listIdsByProject[$project->id][$list->id] = $listId;

                    if ($list->system_status !== null) {
                        $systemListIdsByProject[$project->id][$list->system_status] = $listId;
                    }
                }

                foreach ($systemLists as $status => [$name, $color, $position]) {
                    if (isset($systemListIdsByProject[$project->id][$status])) {
                        continue;
                    }

                    $now = now();
                    $listId = DB::table('todo_lists')->insertGetId([
                        'user_id' => $user->user_id,
                        'todo_project_id' => $project->id,
                        'name' => $name,
                        'system_status' => $status,
                        'color' => $color,
                        'position' => $position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $systemListIdsByProject[$project->id][$status] = $listId;
                }
            }

            $todos = DB::table('todos')->where('user_id', $user->user_id)->get();

            foreach ($todos as $todo) {
                $projectId = $todo->todo_project_id;

                if ($projectId === null) {
                    $projectId = $projects->first()->id;
                }

                $listId = $todo->todo_list_id === null || isset($deletedListIds[(int) $todo->todo_list_id])
                    ? null
                    : ($listIdsByProject[$projectId][$todo->todo_list_id] ?? null);
                $listId ??= $systemListIdsByProject[$projectId][$todo->status]
                    ?? $systemListIdsByProject[$projectId]['todo'];

                DB::table('todos')->where('id', $todo->id)->update([
                    'todo_project_id' => $projectId,
                    'todo_list_id' => $listId,
                ]);
            }
        }

        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['todo_project_id']);
            $table->dropForeign(['todo_list_id']);
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->unsignedBigInteger('todo_project_id')->nullable(false)->change();
            $table->unsignedBigInteger('todo_list_id')->nullable(false)->change();
            $table->foreign('todo_project_id')->references('id')->on('todo_projects')->restrictOnDelete();
            $table->foreign('todo_list_id')->references('id')->on('todo_lists')->restrictOnDelete();
        });

        Schema::table('todo_lists', function (Blueprint $table) {
            $table->unique(['todo_project_id', 'system_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('users')->get(['user_id']) as $user) {
            $systemLists = DB::table('todo_lists')
                ->where('user_id', $user->user_id)
                ->whereNotNull('system_status')
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->get()
                ->groupBy('system_status');

            foreach ($systemLists as $lists) {
                $keptListId = $lists->first()->id;
                $duplicateListIds = $lists->skip(1)->pluck('id')->all();

                if ($duplicateListIds === []) {
                    continue;
                }

                DB::table('todos')->whereIn('todo_list_id', $duplicateListIds)->update(['todo_list_id' => $keptListId]);
                DB::table('todo_lists')->whereIn('id', $duplicateListIds)->delete();
            }
        }

        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['todo_project_id']);
            $table->dropForeign(['todo_list_id']);
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->unsignedBigInteger('todo_project_id')->nullable()->change();
            $table->unsignedBigInteger('todo_list_id')->nullable()->change();
            $table->foreign('todo_project_id')->references('id')->on('todo_projects')->nullOnDelete();
            $table->foreign('todo_list_id')->references('id')->on('todo_lists')->nullOnDelete();
        });

        Schema::table('todo_lists', function (Blueprint $table) {
            $table->dropUnique(['todo_project_id', 'system_status']);
            $table->dropConstrainedForeignId('todo_project_id');
        });

        Schema::table('todo_lists', function (Blueprint $table) {
            $table->unique(['user_id', 'system_status']);
        });

        Schema::table('todo_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::dropIfExists('todo_workspaces');
    }
};
