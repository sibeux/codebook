<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'icon',
        'order',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('order')->orderBy('name');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'folder_id')->orderBy('is_pinned', 'desc')->orderBy('updated_at', 'desc');
    }
}

