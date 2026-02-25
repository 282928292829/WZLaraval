<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingCompany extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'first_half_kg',
        'rest_half_kg',
        'over21_per_kg',
        'price_bands',
        'delivery_days',
        'tracking_url_template',
        'icon',
        'note_ar',
        'note_en',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'first_half_kg' => 'integer',
            'rest_half_kg' => 'integer',
            'over21_per_kg' => 'integer',
            'price_bands' => 'array',
        ];
    }

    /** Carriers that appear in the public shipping calculator (have formula or bands). */
    public function scopeForCalculator($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('first_half_kg')->whereNotNull('rest_half_kg');
                })->orWhereNotNull('price_bands');
            })
            ->orderBy('sort_order');
    }

    /** All active carriers for order tracking dropdown. */
    public function scopeForTracking($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Display name based on current locale. */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        $name = $locale === 'ar' ? ($this->name_ar ?? $this->name_en) : ($this->name_en ?? $this->name_ar);

        return $name ?? $this->slug;
    }

    /** Note (e.g. Economy, Express) based on current locale. */
    public function getDisplayNoteAttribute(): ?string
    {
        $locale = app()->getLocale();
        $note = $locale === 'ar' ? ($this->note_ar ?? $this->note_en) : ($this->note_en ?? $this->note_ar);

        return $note;
    }

    /** Whether this carrier has calculator rates (formula or bands). */
    public function isInCalculator(): bool
    {
        return ($this->first_half_kg !== null && $this->rest_half_kg !== null)
            || ! empty($this->price_bands);
    }

    /** Whether pricing uses weight bands (vs formula). */
    public function usesPriceBands(): bool
    {
        return ! empty($this->price_bands);
    }

    /**
     * Calculate price in SAR for a given weight (kg).
     * Weight is rounded to nearest 0.5 kg.
     */
    public function calculatePrice(float $weightKg): ?int
    {
        $rounded = $this->roundWeight($weightKg);
        if ($rounded <= 0) {
            return null;
        }

        if ($this->usesPriceBands()) {
            $bands = collect($this->price_bands)->sortBy('max_weight')->values();
            if ($bands->isEmpty()) {
                return null;
            }
            $band = $bands->first(fn ($b) => (float) ($b['max_weight'] ?? 0) >= $rounded);
            if (! $band) {
                $band = $bands->last();
            }

            return (int) ($band['price'] ?? 0);
        }

        if ($this->first_half_kg === null || $this->rest_half_kg === null) {
            return null;
        }

        if ($rounded >= 21 && $this->over21_per_kg !== null) {
            return (int) ($this->over21_per_kg * ceil($rounded));
        }

        $additionalHalves = 2 * ($rounded - 0.5);

        return (int) ($this->first_half_kg + $this->rest_half_kg * $additionalHalves);
    }

    /** Round weight to nearest 0.5 kg. */
    public static function roundWeight(float $kg): float
    {
        $intPart = (int) floor($kg);
        $frac = $kg - $intPart;
        if ($frac <= 0) {
            return $kg;
        }

        return $frac <= 0.5 ? $intPart + 0.5 : $intPart + 1;
    }

    /** Build tracking URL with tracking number. */
    public function getTrackingUrl(?string $trackingNumber): ?string
    {
        if (! $this->tracking_url_template || ! $trackingNumber) {
            return null;
        }

        return str_replace('{tracking}', rawurlencode($trackingNumber), $this->tracking_url_template);
    }
}
