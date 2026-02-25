<?php

namespace Database\Seeders;

use App\Console\Commands\FetchExchangeRates;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Seed currencies from existing exchange-rate settings (flat keys and blob).
     * Idempotent: creates missing rows; does not remove or reorder existing.
     */
    public function run(): void
    {
        $codes = FetchExchangeRates::CURRENCIES;

        foreach ($codes as $index => $code) {
            $override = Setting::get("exrate_override_{$code}", '');
            $manualRate = ($override !== '' && $override !== null && is_numeric($override))
                ? (float) $override
                : null;

            $autoFetchRaw = Setting::get("exrate_auto_{$code}", null);
            $autoFetch = $autoFetchRaw === null ? true : filter_var($autoFetchRaw, FILTER_VALIDATE_BOOLEAN);

            $markupRaw = Setting::get("exrate_markup_{$code}", '');
            $markupPercent = ($markupRaw !== '' && $markupRaw !== null && is_numeric($markupRaw))
                ? (float) $markupRaw
                : null;

            Currency::updateOrCreate(
                ['code' => $code],
                [
                    'label' => null,
                    'manual_rate' => $manualRate,
                    'auto_fetch' => $autoFetch,
                    'markup_percent' => $markupPercent,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
