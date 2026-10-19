<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'mode',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function messages()
    {
        return $this->hasMany(NoteMessage::class, 'note_id')->orderBy('order_index', 'asc')->orderBy('id', 'asc');
    }
}

