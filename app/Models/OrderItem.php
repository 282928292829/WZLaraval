<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'url',
        'is_url',
        'source_host',
        'qty',
        'color',
        'size',
        'notes',
        'image_path',
        'currency',
        'unit_price',
        'final_price',
        'commission',
        'shipping',
        'extras',
        'sort_order',
    ];

    protected $casts = [
        'is_url' => 'boolean',
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'commission' => 'decimal:2',
        'shipping' => 'decimal:2',
        'extras' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Product images and attachments linked to this item (order_item_id set) */
    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class, 'order_item_id');
    }

    public function hasImage(): bool
    {
        return ! empty($this->image_path);
    }

    public function lineTotal(array $exchangeRates = [], float $margin = 0.03): float
    {
        if (! $this->unit_price || ! $this->currency) {
            return 0;
        }

        $rate = $exchangeRates[$this->currency] ?? 0;

        if ($rate <= 0) {
            return 0;
        }

        return round($this->unit_price * $this->qty * $rate * (1 + $margin), 2);
    }
}
