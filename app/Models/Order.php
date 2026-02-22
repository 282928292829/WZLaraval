<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'layout_option',
        'notes',
        'staff_notes',
        'shipping_address_id',
        'shipping_address_snapshot',
        'is_paid',
        'paid_at',
        'payment_proof',
        'subtotal',
        'total_amount',
        'currency',
        'can_edit_until',
        'merged_into',
        'merged_at',
        'merged_by',
        'tracking_number',
        'tracking_company',
        'payment_amount',
        'payment_date',
        'payment_method',
        'payment_receipt',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'can_edit_until' => 'datetime',
        'merged_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
        'shipping_address_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(OrderComment::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(OrderTimeline::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'merged_into');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('Pending'),
            'needs_payment' => __('Needs Payment'),
            'processing' => __('Processing'),
            'purchasing' => __('Purchasing'),
            'shipped' => __('Shipped'),
            'delivered' => __('Delivered'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            'on_hold' => __('On Hold'),
            default => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'needs_payment' => 'red',
            'processing' => 'blue',
            'purchasing' => 'indigo',
            'shipped' => 'purple',
            'delivered' => 'teal',
            'completed' => 'green',
            'cancelled' => 'gray',
            'on_hold' => 'orange',
            default => 'gray',
        };
    }

    public function statusGroup(): string
    {
        return match ($this->status) {
            'needs_payment', 'on_hold' => 'needs_action',
            'completed', 'cancelled' => 'completed',
            default => 'in_progress',
        };
    }

    /** Customer can cancel when order is pending or needs_payment (matches WP: status 0 or 1) */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'needs_payment']);
    }

    public static function getStatuses(): array
    {
        return [
            'pending' => __('Pending'),
            'needs_payment' => __('Needs Payment'),
            'processing' => __('Processing'),
            'purchasing' => __('Purchasing'),
            'shipped' => __('Shipped'),
            'delivered' => __('Delivered'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            'on_hold' => __('On Hold'),
        ];
    }
}
