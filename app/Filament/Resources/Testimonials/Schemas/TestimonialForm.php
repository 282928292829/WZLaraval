<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Content'))->schema([
                FileUpload::make('image_path')
                    ->label(__('Image'))
                    ->image()
                    ->disk('public')
                    ->directory('testimonials')
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->maxLength(255),

                TextInput::make('name_en')
                    ->label(__('Name (English)'))
                    ->maxLength(255),

                Textarea::make('quote_ar')
                    ->label(__('Quote (Arabic)'))
                    ->rows(3),

                Textarea::make('quote_en')
                    ->label(__('Quote (English)'))
                    ->rows(3),
            ])->columns(2),

            Section::make(__('Publish'))->schema([
                TextInput::make('sort_order')
                    ->label(__('Sort Order'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Toggle::make('is_published')
                    ->label(__('Published'))
                    ->default(true),
            ])->columns(2),
        ]);
    }
}
