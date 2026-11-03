<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'todo_project_id',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(TodoProject::class, 'todo_project_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TodoItem::class)->latest();
    }
}
