<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'tracking_code',
        'platform',
        'notes',
        'is_active',
        'order_count',
        'orders_cancelled',
        'orders_shipped',
        'orders_delivered',
        'click_count',
        'destination_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('title');
    }

    /**
     * Find a campaign by slug or tracking code from the request.
     * Used during registration to attribute new users to a campaign.
     */
    public static function resolveFromRequest(\Illuminate\Http\Request $request): ?self
    {
        $slug = $request->query('utm_campaign') ?? $request->query('campaign');

        if (! $slug) {
            return null;
        }

        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    /** Increment campaign counter when an attributed user's order reaches a status. */
    public static function incrementForOrderStatus(\App\Models\Order $order, string $status): void
    {
        $campaignId = $order->user?->ad_campaign_id;
        if (! $campaignId) {
            return;
        }
        $column = match ($status) {
            'cancelled' => 'orders_cancelled',
            'shipped' => 'orders_shipped',
            'delivered' => 'orders_delivered',
            default => null,
        };
        if ($column) {
            static::where('id', $campaignId)->increment($column);
        }
    }

    /** @deprecated Use incrementForOrderStatus($order, 'cancelled') */
    public static function incrementCancelledForOrder(\App\Models\Order $order): void
    {
        static::incrementForOrderStatus($order, 'cancelled');
    }
}
