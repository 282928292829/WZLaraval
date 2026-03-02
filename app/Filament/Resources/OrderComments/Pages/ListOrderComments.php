<?php

namespace App\Filament\Resources\OrderComments\Pages;

use App\Filament\Resources\OrderComments\OrderCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderComments extends ListRecords
{
    protected static string $resource = OrderCommentResource::class;
}
