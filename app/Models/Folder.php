<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    public const MAX_DEPTH = 10;

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

    /**
     * Calculate folder depth from root (root = 1, child = 2, ..., up to MAX_DEPTH).
     */
    public function getDepth(): int
    {
        $depth = 1;
        $current = $this->parent;
        $visited = [$this->id];

        while ($current && ! in_array($current->id, $visited, true)) {
            $depth++;
            $visited[] = $current->id;
            $current = $current->parent;
        }

        return $depth;
    }

    /**
     * Calculate subtree height (leaf = 1, parent with leaf = 2, etc.).
     */
    public function getSubtreeHeight(array &$visited = []): int
    {
        if (in_array($this->id, $visited, true)) {
            return 1;
        }

        $visited[] = $this->id;
        $maxChildHeight = 0;

        foreach ($this->children as $child) {
            $maxChildHeight = max($maxChildHeight, $child->getSubtreeHeight($visited));
        }

        return 1 + $maxChildHeight;
    }
}
