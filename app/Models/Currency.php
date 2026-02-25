<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'label',
        'manual_rate',
        'auto_fetch',
        'markup_percent',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'manual_rate' => 'float',
            'auto_fetch' => 'boolean',
            'markup_percent' => 'float',
            'sort_order' => 'integer',
        ];
    }

    /** Scope: ordered for display and fetch */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
