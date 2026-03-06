<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusAutomationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_status_automation_rule_id',
        'order_comment_id',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(OrderStatusAutomationRule::class, 'order_status_automation_rule_id');
    }

    public function orderComment(): BelongsTo
    {
        return $this->belongsTo(OrderComment::class, 'order_comment_id');
    }
}
