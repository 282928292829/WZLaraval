<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCommentEdit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'old_body',
        'edited_by',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(OrderComment::class, 'comment_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
