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
}
