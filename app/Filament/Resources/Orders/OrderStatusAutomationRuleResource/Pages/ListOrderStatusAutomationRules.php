<?php

namespace App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages;

use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderStatusAutomationRules extends ListRecords
{
    protected static string $resource = OrderStatusAutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
