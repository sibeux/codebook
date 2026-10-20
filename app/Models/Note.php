<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'mode',
        'is_pinned',
        'is_public',
        'share_token',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected $appends = [
        'share_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Note $note) {
            if (empty($note->share_token)) {
                $note->share_token = Str::random(32);
            }
        });
    }

    public function getShareUrlAttribute(): string
    {
        if (empty($this->share_token)) {
            return '';
        }

        return route('notes.share', ['token' => $this->share_token]);
    }

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
