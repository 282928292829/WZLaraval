<?php

namespace App\Filament\Resources\Blog\PostCategoryResource\Pages;

use App\Filament\Resources\Blog\PostCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePostCategory extends CreateRecord
{
    protected static string $resource = PostCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name_en'] ?? $data['name_ar'] ?? '');
        }

        return $data;
    }
}
