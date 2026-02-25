<?php

namespace App\Filament\Imports;

use App\Models\AdCampaign;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class AdCampaignImporter extends Importer
{
    protected static ?string $model = AdCampaign::class;

    /**
     * Columns for importing ad campaign links for use on WordPress or other sites.
     * Each row creates a /go/{slug} tracking link.
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->label(__('Campaign Title'))
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Facebook Summer Sale'),
            ImportColumn::make('slug')
                ->label(__('Slug'))
                ->requiredMapping()
                ->rules(['required', 'max:100', 'unique:ad_campaigns,slug'])
                ->example('fb-summer-2025'),
            ImportColumn::make('destination_url')
                ->label(__('Destination URL'))
                ->rules(['nullable', 'max:255'])
                ->example('https://example.com/new-order'),
            ImportColumn::make('tracking_code')
                ->label(__('Tracking Code'))
                ->rules(['nullable', 'max:255'])
                ->example('fb_cpc'),
            ImportColumn::make('platform')
                ->label(__('Platform'))
                ->rules(['nullable', 'max:255'])
                ->example('facebook'),
            ImportColumn::make('notes')
                ->label(__('Notes'))
                ->example(__('For use on WordPress banner ads')),
            ImportColumn::make('is_active')
                ->label(__('Active'))
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('1'),
        ];
    }

    public function resolveRecord(): AdCampaign
    {
        return new AdCampaign;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('ad_campaigns.import_completed', [
            'count' => Number::format($import->successful_rows),
        ]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.__('ad_campaigns.import_failed_rows', [
                'count' => Number::format($failedRowsCount),
            ]);
        }

        return $body;
    }
}
