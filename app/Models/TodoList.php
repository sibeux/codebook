<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TodoList extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const SYSTEM_LISTS = [
        'todo' => ['name' => 'To do', 'color' => '#94a3b8', 'position' => 0],
        'in_progress' => ['name' => 'In progress', 'color' => '#6366f1', 'position' => 1],
        'blocked' => ['name' => 'Blocked', 'color' => '#f59e0b', 'position' => 2],
        'done' => ['name' => 'Done', 'color' => '#10b981', 'position' => 3],
    ];

    protected $fillable = [
        'user_id',
        'todo_project_id',
        'name',
        'system_status',
        'color',
        'position',
    ];

    public static function ensureSystemLists(User $user, TodoProject $project): void
    {
        foreach (self::SYSTEM_LISTS as $status => $attributes) {
            $listExists = self::withTrashed()
                ->where('user_id', $user->user_id)
                ->where('todo_project_id', $project->id)
                ->where('system_status', $status)
                ->exists();

            if (! $listExists) {
                self::create([
                    'user_id' => $user->user_id,
                    'todo_project_id' => $project->id,
                    'system_status' => $status,
                    ...$attributes,
                ]);
            }
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(TodoProject::class, 'todo_project_id');
    }
}
