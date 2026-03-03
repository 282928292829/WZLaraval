<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages\CreateOrderStatusAutomationRule;
use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages\EditOrderStatusAutomationRule;
use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages\ListOrderStatusAutomationRules;
use App\Models\Order;
use App\Models\OrderStatusAutomationRule;
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

class OrderStatusAutomationRuleResource extends Resource
{
    protected static ?string $model = OrderStatusAutomationRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('automation.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('automation.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('automation.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('manage-order-automation') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('automation.section_rule'))
                ->description(__('automation.section_description'))
                ->schema([
                    Select::make('status')
                        ->label(__('automation.status'))
                        ->options(Order::getStatuses())
                        ->required(),

                    TextInput::make('days')
                        ->label(__('automation.days'))
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->suffix(__('automation.days_suffix')),

                    Textarea::make('comment_template')
                        ->label(__('automation.comment_template'))
                        ->required()
                        ->rows(4)
                        ->maxLength(2000)
                        ->placeholder(__('automation.comment_placeholder'))
                        ->helperText(__('automation.comment_helper')),

                    Toggle::make('is_active')
                        ->label(__('automation.is_active'))
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label(__('automation.status'))
                    ->formatStateUsing(fn (string $state): string => Order::getStatuses()[$state] ?? $state)
                    ->sortable(),

                TextColumn::make('days')
                    ->label(__('automation.days'))
                    ->suffix(' '.__('automation.days_suffix'))
                    ->sortable(),

                TextColumn::make('comment_template')
                    ->label(__('automation.comment_template'))
                    ->limit(50)
                    ->wrap(),

                IconColumn::make('is_active')
                    ->label(__('automation.is_active'))
                    ->boolean(),

                TextColumn::make('logs_count')
                    ->label(__('automation.triggered_count'))
                    ->counts('logs')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('automation.is_active')),
            ])
            ->defaultSort('status');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderStatusAutomationRules::route('/'),
            'create' => CreateOrderStatusAutomationRule::route('/create'),
            'edit' => EditOrderStatusAutomationRule::route('/{record}/edit'),
        ];
    }
}
