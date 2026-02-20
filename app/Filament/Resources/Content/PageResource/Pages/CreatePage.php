<?php

namespace App\Filament\Resources\Content\PageResource\Pages;

use App\Filament\Resources\Content\PageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title_en'] ?? $data['title_ar'] ?? '');
        }

        return $data;
    }
}
