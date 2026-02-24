<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\AdCampaignResource\Pages\CreateAdCampaign;
use App\Filament\Resources\Orders\AdCampaignResource\Pages\EditAdCampaign;
use App\Filament\Resources\Orders\AdCampaignResource\Pages\ListAdCampaigns;
use App\Models\AdCampaign;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class AdCampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function getNavigationLabel(): string
    {
        return __('Ad Campaigns');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function getTitle(): string
    {
        return __('Ad Campaigns');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Campaign Details'))->schema([
                TextInput::make('title')
                    ->label(__('Campaign Title'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->helperText(__('Used in /go/{slug} and utm_campaign'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                TextInput::make('destination_url')
                    ->label(__('Destination URL'))
                    ->helperText(__('Optional. If empty, redirects to homepage.'))
                    ->placeholder('https://… or /path')
                    ->nullable()
                    ->maxLength(500),

                TextInput::make('tracking_code')
                    ->label(__('Tracking Code'))
                    ->nullable()
                    ->maxLength(255),

                Select::make('platform')
                    ->label(__('Platform'))
                    ->nullable()
                    ->options([
                        'google' => 'Google',
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter / X',
                        'tiktok' => 'TikTok',
                        'snapchat' => 'Snapchat',
                        'other' => __('Other'),
                    ]),

                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->nullable()
                    ->rows(3),

                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Campaign Title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->copyable()
                    ->sortable(),

                TextColumn::make('platform')
                    ->label(__('Platform'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('tracking_code')
                    ->label(__('Tracking Code'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('click_count')
                    ->label(__('Clicks'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('order_count')
                    ->label(__('Orders'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('orders_cancelled')
                    ->label(__('Cancelled'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('orders_shipped')
                    ->label(__('Shipped'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('orders_delivered')
                    ->label(__('Delivered'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('users_count')
                    ->label(__('Users'))
                    ->counts('users')
                    ->sortable()
                    ->alignEnd(),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('Active')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdCampaigns::route('/'),
            'create' => CreateAdCampaign::route('/create'),
            'edit' => EditAdCampaign::route('/{record}/edit'),
        ];
    }
}
