<?php

namespace App\Services;

use App\Models\Setting;

class CommissionCalculator
{
    private static function hasNewCommissionSchema(): bool
    {
        return Setting::where('key', 'commission_below_type')->exists();
    }

    /**
     * Calculate commission for a given order subtotal (in SAR).
     * Uses flexible below/above threshold rules with either flat (SAR) or percent.
     */
    public static function calculate(float $subtotalSAR): float
    {
        if ($subtotalSAR <= 0) {
            return 0.0;
        }

        $threshold = (float) Setting::get('commission_threshold_sar', 500);
        $belowType = (string) Setting::get('commission_below_type', 'flat');
        $belowValue = (float) Setting::get('commission_below_value', 50);
        $aboveType = (string) Setting::get('commission_above_type', 'percent');
        $aboveValue = (float) Setting::get('commission_above_value', 8);

        // Backward compatibility: if new keys not set, use legacy keys
        if (! static::hasNewCommissionSchema()) {
            $belowType = 'flat';
            $belowValue = (float) Setting::get('commission_flat_below', 50);
            $aboveType = 'percent';
            $aboveValue = (float) Setting::get('commission_rate_above', 8);
        }

        $isAbove = $subtotalSAR >= $threshold;

        if ($isAbove) {
            return $aboveType === 'percent'
                ? $subtotalSAR * ($aboveValue / 100)
                : $aboveValue;
        }

        return $belowType === 'percent'
            ? $subtotalSAR * ($belowValue / 100)
            : $belowValue;
    }

    /**
     * Get commission settings for display (calculator, info cards).
     *
     * @return array{threshold: float, below_type: string, below_value: float, above_type: string, above_value: float}
     */
    public static function getSettings(): array
    {
        $threshold = (float) Setting::get('commission_threshold_sar', 500);
        $belowType = (string) Setting::get('commission_below_type', 'flat');
        $belowValue = (float) Setting::get('commission_below_value', 50);
        $aboveType = (string) Setting::get('commission_above_type', 'percent');
        $aboveValue = (float) Setting::get('commission_above_value', 8);

        if (! static::hasNewCommissionSchema()) {
            $belowType = 'flat';
            $belowValue = (float) Setting::get('commission_flat_below', 50);
            $aboveType = 'percent';
            $aboveValue = (float) Setting::get('commission_rate_above', 8);
        }

        return [
            'threshold' => $threshold,
            'below_type' => $belowType,
            'below_value' => $belowValue,
            'above_type' => $aboveType,
            'above_value' => $aboveValue,
        ];
    }
}
