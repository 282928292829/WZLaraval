<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentTemplate extends Model
{
    protected $fillable = [
        'title',
        'content',
        'usage_count',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'usage_count' => 'integer',
        'sort_order'  => 'integer',
    ];

    /** Active templates ordered by usage then sort_order. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->orderByDesc('usage_count')
                     ->orderBy('sort_order');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
