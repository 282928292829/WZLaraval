<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCommentNotificationLog extends Model
{
    protected $table = 'order_comment_notification_log';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'comment_id',
        'user_id',
        'channel',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(OrderComment::class, 'comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
