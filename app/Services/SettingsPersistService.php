<?php

namespace App\Services;

use App\Console\Commands\FetchExchangeRates;
use App\Models\Currency;
use App\Models\Setting;

class SettingsPersistService
{
    /**
     * Persist settings to the database.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $groupMap
     * @param  array<string>  $booleanKeys
     * @param  array<string>  $integerKeys
     * @param  array<string>  $floatKeys
     * @param  array<string>  $jsonKeys
     * @param  array<string>  $skipKeys
     * @param  array<string>  $fileUploadKeys
     */
    public function persist(
        array $data,
        array $groupMap,
        array $booleanKeys,
        array $integerKeys,
        array $floatKeys,
        array $jsonKeys,
        array $skipKeys = [],
        array $fileUploadKeys = []
    ): void {
        foreach ($data as $key => $value) {
            if (in_array($key, $skipKeys)) {
                continue;
            }

            $group = $groupMap[$key] ?? 'general';

            if (in_array($key, $fileUploadKeys) && is_array($value)) {
                $value = $value[0] ?? '';
            }

            if (in_array($key, $jsonKeys) || (is_array($value) && ! in_array($key, $floatKeys))) {
                if (is_array($value)) {
                    usort($value, fn ($a, $b) => ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99));
                }
                $type = 'json';
            } elseif (is_bool($value) || in_array($key, $booleanKeys)) {
                $type = 'boolean';
            } elseif (in_array($key, $floatKeys)) {
                $type = 'string';
            } elseif (is_numeric($value) && ! str_contains((string) $value, '.') && in_array($key, $integerKeys)) {
                $type = 'integer';
            } else {
                $type = 'string';
            }

            Setting::set($key, $value ?? '', $type, $group);
        }
    }

    /**
     * Sync currencies from repeater data to Currency table.
     *
     * @param  array<int, array{id?: int, code?: string, label?: string, manual_rate?: string|float, auto_fetch?: bool, markup_percent?: string|float}>  $items
     */
    public function syncCurrencies(array $items): void
    {
        $keptIds = [];

        foreach ($items as $index => $item) {
            $code = trim((string) ($item['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $code = strtoupper($code);

            $manualRate = isset($item['manual_rate']) && $item['manual_rate'] !== '' && is_numeric($item['manual_rate'])
                ? (float) $item['manual_rate']
                : null;
            $markupPercent = isset($item['markup_percent']) && $item['markup_percent'] !== '' && is_numeric($item['markup_percent'])
                ? (float) $item['markup_percent']
                : null;

            $currency = isset($item['id']) && $item['id']
                ? Currency::find($item['id'])
                : null;

            if ($currency) {
                $currency->update([
                    'code' => $code,
                    'label' => trim((string) ($item['label'] ?? '')) ?: null,
                    'manual_rate' => $manualRate,
                    'auto_fetch' => (bool) ($item['auto_fetch'] ?? true),
                    'markup_percent' => $markupPercent,
                    'sort_order' => $index,
                ]);
            } else {
                $currency = Currency::create([
                    'code' => $code,
                    'label' => trim((string) ($item['label'] ?? '')) ?: null,
                    'manual_rate' => $manualRate,
                    'auto_fetch' => (bool) ($item['auto_fetch'] ?? true),
                    'markup_percent' => $markupPercent,
                    'sort_order' => $index,
                ]);
            }
            $keptIds[] = $currency->id;
        }

        Currency::whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Rebuild exchange_rates JSON blob.
     *
     * @param  array<string, mixed>  $data
     */
    public function syncExchangeRates(array $data): void
    {
        $er = Setting::get('exchange_rates', []) ?: [];

        if (empty($er['rates'])) {
            $er['rates'] = FetchExchangeRates::DEFAULT_RATES;
        }

        $markup = (float) ($data['exchange_rates_markup_percent'] ?? 3);
        $er['markup_percent'] = $markup;
        $er['auto_fetch_enabled'] = (bool) ($data['exchange_rates_auto_fetch'] ?? true);

        $currencies = Currency::ordered()->get();

        foreach ($currencies as $model) {
            $cur = $model->code;
            if (! isset($er['rates'][$cur])) {
                $er['rates'][$cur] = FetchExchangeRates::DEFAULT_RATES[$cur] ?? [
                    'auto' => true, 'market' => 0, 'manual' => null, 'final' => 0,
                ];
            }

            $er['rates'][$cur]['manual'] = $model->manual_rate;
            $perMarkup = $model->markup_percent ?? $markup;

            if ($model->manual_rate !== null) {
                $er['rates'][$cur]['final'] = (float) $model->manual_rate;
            } else {
                $market = (float) ($er['rates'][$cur]['market'] ?? 0);
                $er['rates'][$cur]['final'] = $market > 0
                    ? round($market * (1 + $perMarkup / 100), 4)
                    : ($er['rates'][$cur]['final'] ?? 0);
            }
        }

        Setting::set('exchange_rates', $er, 'json', 'exchange_rates');
    }
}
