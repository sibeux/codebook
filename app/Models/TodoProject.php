<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoProject extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (TodoProject $project): void {
            if ($project->workspace_id !== null) {
                return;
            }

            $workspace = TodoWorkspace::firstOrCreate(
                ['user_id' => $project->user_id, 'is_default' => true],
                ['name' => 'Personal'],
            );

            $project->workspace_id = $workspace->id;
        });
    }

    protected $fillable = [
        'user_id',
        'workspace_id',
        'name',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(TodoWorkspace::class, 'workspace_id');
    }

    public function todoLists(): HasMany
    {
        return $this->hasMany(TodoList::class, 'todo_project_id');
    }
}
