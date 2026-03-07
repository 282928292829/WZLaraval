<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTestimonial extends EditRecord
{
    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPage')
                ->label(__('View page'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(route('pages.show', 'testimonials'))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
