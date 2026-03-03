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
}
