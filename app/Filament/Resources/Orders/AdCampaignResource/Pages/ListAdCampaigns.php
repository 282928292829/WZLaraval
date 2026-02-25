<?php

namespace App\Filament\Resources\Orders\AdCampaignResource\Pages;

use App\Filament\Imports\AdCampaignImporter;
use App\Filament\Resources\Orders\AdCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListAdCampaigns extends ListRecords
{
    protected static string $resource = AdCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(AdCampaignImporter::class)
                ->label(__('Import'))
                ->modalHeading(__('Import Ad Campaign Links'))
                ->modalDescription(__('Upload a CSV with ad campaign links for use on WordPress or other sites. Each row creates a /go/{slug} tracking link.'))
                ->modalSubmitActionLabel(__('Import')),
            CreateAction::make(),
        ];
    }
}
