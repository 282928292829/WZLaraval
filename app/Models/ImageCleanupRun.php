<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageCleanupRun extends Model
{
    protected $fillable = [
        'rule_type',
        'image_cleanup_rule_id',
        'started_at',
        'finished_at',
        'dry_run',
        'orders_processed',
        'files_deleted',
        'files_compressed',
        'bytes_freed',
        'details',
        'triggered_by',
    ];

    public function rule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ImageCleanupRule::class, 'image_cleanup_rule_id');
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'dry_run' => 'boolean',
            'orders_processed' => 'integer',
            'files_deleted' => 'integer',
            'files_compressed' => 'integer',
            'bytes_freed' => 'integer',
            'details' => 'array',
        ];
    }
}
