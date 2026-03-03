<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatusAutomationRule extends Model
{
    protected $fillable = [
        'status',
        'days',
        'comment_template',
        'is_active',
    ];

    protected $casts = [
        'days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(OrderStatusAutomationLog::class, 'order_status_automation_rule_id');
    }

    public function renderComment(int $daysInStatus): string
    {
        $replacements = [
            '{status}' => __(ucfirst(str_replace('_', ' ', $this->status))),
            '{days}' => (string) $daysInStatus,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $this->comment_template);
    }
}
