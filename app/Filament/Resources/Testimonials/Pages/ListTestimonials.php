<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Models\Testimonial;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTestimonials extends ListRecords
{
    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkUpload')
                ->label(__('testimonials.bulk_upload'))
                ->icon('heroicon-o-photo')
                ->form([
                    FileUpload::make('images')
                        ->label(__('testimonials.images'))
                        ->multiple()
                        ->maxFiles(50)
                        ->image()
                        ->disk('public')
                        ->directory('testimonials')
                        ->required()
                        ->helperText(__('testimonials.bulk_upload_help')),
                ])
                ->modalHeading(__('testimonials.bulk_upload_heading'))
                ->modalSubmitActionLabel(__('testimonials.bulk_upload_submit'))
                ->action(function (array $data): void {
                    $paths = $data['images'] ?? [];
                    if (! is_array($paths)) {
                        $paths = [$paths];
                    }

                    $maxOrder = (int) Testimonial::max('sort_order');
                    $created = 0;

                    foreach ($paths as $path) {
                        if (empty($path)) {
                            continue;
                        }
                        Testimonial::create([
                            'image_path' => $path,
                            'sort_order' => ++$maxOrder,
                            'is_published' => true,
                        ]);
                        $created++;
                    }

                    Notification::make()
                        ->title(__('testimonials.created_count', ['count' => $created]))
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
