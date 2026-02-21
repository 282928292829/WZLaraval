<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'order_id',
        'sent_by',
        'recipient_email',
        'recipient_name',
        'type',
        'subject',
        'queued',
        'status',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'queued'  => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'order_confirmation' => __('Order Confirmation'),
            'status_update'      => __('Status Update'),
            'registration'       => __('Registration Welcome'),
            default              => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'sent'   => 'green',
            'failed' => 'red',
            default  => 'gray',
        };
    }
}
