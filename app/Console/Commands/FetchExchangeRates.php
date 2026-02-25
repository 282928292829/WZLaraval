<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchExchangeRates extends Command
{
    protected $signature = 'rates:fetch';

    protected $description = 'Fetch exchange rates from open.er-api.com and update settings table';

    /** Legacy: fallback when no currencies in DB yet */
    public const CURRENCIES = ['USD', 'EUR', 'GBP', 'CNY', 'JPY', 'KRW', 'TRY', 'AED'];

    /** Fallback rates (SAR per 1 unit of currency) used when no API data exists yet */
    public const DEFAULT_RATES = [
        'SAR' => ['final' => 1.0],
        'USD' => ['auto' => true, 'market' => 3.75,   'manual' => null, 'final' => 3.86],
        'EUR' => ['auto' => true, 'market' => 4.10,   'manual' => null, 'final' => 4.22],
        'GBP' => ['auto' => true, 'market' => 4.75,   'manual' => null, 'final' => 4.89],
        'CNY' => ['auto' => true, 'market' => 0.53,   'manual' => null, 'final' => 0.55],
        'JPY' => ['auto' => true, 'market' => 0.024,  'manual' => null, 'final' => 0.025],
        'KRW' => ['auto' => true, 'market' => 0.0026, 'manual' => null, 'final' => 0.0027],
        'TRY' => ['auto' => true, 'market' => 0.11,   'manual' => null, 'final' => 0.11],
        'AED' => ['auto' => true, 'market' => 1.02,   'manual' => null, 'final' => 1.05],
    ];

    /** @return array<string> */
    protected function currencyCodes(): array
    {
        $currencies = Currency::ordered()->pluck('code')->toArray();

        return $currencies !== [] ? $currencies : self::CURRENCIES;
    }

    public function handle(): int
    {
        $er = Setting::get('exchange_rates', []) ?: [];

        if (empty($er['rates'])) {
            $er['rates'] = self::DEFAULT_RATES;
        }

        $globalMarkup = (float) (Setting::get('exchange_rates_markup_percent', null) ?? $er['markup_percent'] ?? 3);

        $currencies = Currency::ordered()->get()->keyBy('code');
        $codes = $this->currencyCodes();

        // Ensure each tracked currency has a blob entry; apply overrides from DB
        foreach ($codes as $cur) {
            if (! isset($er['rates'][$cur])) {
                $er['rates'][$cur] = self::DEFAULT_RATES[$cur] ?? [
                    'auto' => true, 'market' => 0, 'manual' => null, 'final' => 0,
                ];
            }
            $model = $currencies->get($cur);
            if ($model) {
                $er['rates'][$cur]['manual'] = $model->manual_rate;
                $er['rates'][$cur]['auto'] = $model->auto_fetch;
            } else {
                // Legacy: read from settings when no row yet
                $override = Setting::get("exrate_override_{$cur}", '');
                if ($override !== '' && $override !== null && is_numeric($override)) {
                    $er['rates'][$cur]['manual'] = (float) $override;
                }
                $er['rates'][$cur]['auto'] = (bool) (Setting::get("exrate_auto_{$cur}", true) ?? true);
            }
        }

        // Fetch from open.er-api.com (free, no API key required)
        try {
            $response = Http::timeout(10)->get('https://open.er-api.com/v6/latest/USD');
        } catch (\Exception $e) {
            $er['last_fetch_status'] = 'error';
            $er['last_fetch_time'] = now()->toDateTimeString();
            Setting::set('exchange_rates', $er, 'json', 'exchange_rates');
            $this->error("Fetch failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $er['last_fetch_status'] = 'error';
            $er['last_fetch_time'] = now()->toDateTimeString();
            Setting::set('exchange_rates', $er, 'json', 'exchange_rates');
            $this->error("API returned HTTP {$response->status()}");

            return self::FAILURE;
        }

        $data = $response->json();

        if (empty($data['rates']['SAR'])) {
            $er['last_fetch_status'] = 'error';
            $er['last_fetch_time'] = now()->toDateTimeString();
            Setting::set('exchange_rates', $er, 'json', 'exchange_rates');
            $this->error('Invalid API response — SAR rate missing.');

            return self::FAILURE;
        }

        // How many SAR per 1 USD (e.g. 3.75)
        $usdToSar = (float) $data['rates']['SAR'];

        foreach ($codes as $cur) {
            $auto = $er['rates'][$cur]['auto'] ?? true;
            $manual = $er['rates'][$cur]['manual'] ?? null;

            $model = $currencies->get($cur);
            $perCurMarkup = $model?->markup_percent;
            if ($perCurMarkup === null) {
                $perCurMarkup = Setting::get("exrate_markup_{$cur}", '');
            }
            $markup = ($perCurMarkup !== '' && $perCurMarkup !== null && is_numeric($perCurMarkup))
                ? (float) $perCurMarkup
                : $globalMarkup;

            if (! isset($data['rates'][$cur])) {
                // No API data for this currency — keep existing or apply markup to market
                if (! $auto) {
                    continue;
                }
                $market = (float) ($er['rates'][$cur]['market'] ?? 0);
                if ($manual !== null) {
                    $er['rates'][$cur]['final'] = $manual;
                } elseif ($market > 0) {
                    $er['rates'][$cur]['final'] = round($market * (1 + $markup / 100), 4);
                }

                continue;
            }

            // How many of this currency per 1 USD
            $usdToCur = (float) $data['rates'][$cur];
            $sarRate = $usdToCur > 0 ? $usdToSar / $usdToCur : 0;

            if ($auto) {
                $er['rates'][$cur]['market'] = round($sarRate, 6);
            }

            $er['rates'][$cur]['final'] = ($manual !== null)
                ? (float) $manual
                : round(($er['rates'][$cur]['market'] ?? $sarRate) * (1 + $markup / 100), 4);
        }

        $er['markup_percent'] = $globalMarkup;
        $er['last_fetch_status'] = 'success';
        $er['last_fetch_time'] = now()->toDateTimeString();

        Setting::set('exchange_rates', $er, 'json', 'exchange_rates');

        $this->info("Exchange rates updated. USD → SAR: {$usdToSar} (global markup: {$globalMarkup}%)");

        return self::SUCCESS;
    }
}
