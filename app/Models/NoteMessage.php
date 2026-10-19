<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'sender',
        'content',
        'order_index',
        'sent_time',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class, 'note_id');
    }
}

