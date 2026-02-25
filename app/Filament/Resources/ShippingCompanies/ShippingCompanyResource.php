<?php

namespace App\Filament\Resources\ShippingCompanies;

use App\Filament\Resources\ShippingCompanies\Pages\CreateShippingCompany;
use App\Filament\Resources\ShippingCompanies\Pages\EditShippingCompany;
use App\Filament\Resources\ShippingCompanies\Pages\ListShippingCompanies;
use App\Models\ShippingCompany;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShippingCompanyResource extends Resource
{
    protected static ?string $model = ShippingCompany::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function getNavigationLabel(): string
    {
        return __('Shipping Companies');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getModelLabel(): string
    {
        return __('Shipping Company');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Shipping Companies');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Basic Info'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name_ar')
                        ->label(__('Name (Arabic)'))
                        ->maxLength(100),

                    TextInput::make('name_en')
                        ->label(__('Name (English)'))
                        ->maxLength(100),

                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->columnSpanFull()
                        ->helperText(__('Unique identifier, e.g. aramex, dhl. Used in URLs and order tracking.')),

                    TextInput::make('icon')
                        ->label(__('Icon'))
                        ->maxLength(10)
                        ->placeholder('📦')
                        ->helperText(__('Emoji or icon. Shown on calculator.')),

                    TextInput::make('note_ar')
                        ->label(__('Note (Arabic)'))
                        ->maxLength(100)
                        ->placeholder(__('Economy')),

                    TextInput::make('note_en')
                        ->label(__('Note (English)'))
                        ->maxLength(100)
                        ->placeholder(__('Express')),

                    TextInput::make('sort_order')
                        ->label(__('Sort Order'))
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Toggle::make('is_active')
                        ->label(__('Active'))
                        ->default(true),
                ])
                ->columns(2),

            Section::make(__('Calculator Rates'))
                ->description(__('Use formula OR weight bands. Leave both empty to hide from calculator.'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('first_half_kg')
                        ->label(__('First 0.5 kg (SAR)'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('SAR')
                        ->helperText(__('Formula: first 0.5 kg + rest per 0.5 kg')),

                    TextInput::make('rest_half_kg')
                        ->label(__('Each Additional 0.5 kg (SAR)'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('SAR'),

                    TextInput::make('over21_per_kg')
                        ->label(__('Over 21 kg — per kg (SAR)'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('SAR')
                        ->helperText(__('Leave empty for carriers without over-21 tier.')),

                    Repeater::make('price_bands')
                        ->label(__('Weight Bands (500g increments)'))
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('max_weight')
                                ->label(__('Up to (kg)'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.5)
                                ->suffix('kg')
                                ->required(),
                            TextInput::make('price')
                                ->label(__('Price (SAR)'))
                                ->numeric()
                                ->minValue(0)
                                ->suffix('SAR')
                                ->required(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->helperText(__('Use when pricing is not formula-based. Each row: up to X kg = Y SAR. Add bands in order (0.5, 1, 1.5, …).')),

                    TextInput::make('delivery_days')
                        ->label(__('Est. Delivery'))
                        ->maxLength(20)
                        ->placeholder('7-10')
                        ->helperText(__('e.g. "7-10 days"')),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make(__('Tracking'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('tracking_url_template')
                        ->label(__('Tracking URL Template'))
                        ->url()
                        ->maxLength(500)
                        ->columnSpanFull()
                        ->placeholder('https://www.aramex.com/track/results?ShipmentNumber={tracking}')
                        ->helperText(__('Use {tracking} as placeholder for the tracking number. Leave blank for no link.')),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_ar')
                    ->label(__('Name (AR)'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('name_en')
                    ->label(__('Name (EN)'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('first_half_kg')
                    ->label(__('First 0.5 kg'))
                    ->suffix(' SAR')
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('delivery_days')
                    ->label(__('Delivery'))
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('Active')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-shipping-companies') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingCompanies::route('/'),
            'create' => CreateShippingCompany::route('/create'),
            'edit' => EditShippingCompany::route('/{record}/edit'),
        ];
    }
}
