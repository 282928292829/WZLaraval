<?php

namespace App\Filament\Resources\ShippingCompanies\Pages;

use App\Filament\Resources\ShippingCompanies\ShippingCompanyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateShippingCompany extends CreateRecord
{
    protected static string $resource = ShippingCompanyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && ! empty($data['name_en'])) {
            $data['slug'] = Str::slug($data['name_en']);
        } elseif (empty($data['slug']) && ! empty($data['name_ar'])) {
            $data['slug'] = Str::slug($data['name_ar']);
        }

        return $data;
    }
}
