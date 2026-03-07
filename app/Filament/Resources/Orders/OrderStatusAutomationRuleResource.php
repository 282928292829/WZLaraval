<?php

namespace App\Filament\Resources\Orders;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages\CreateOrderStatusAutomationRule;
use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages\EditOrderStatusAutomationRule;
use App\Filament\Resources\Orders\OrderStatusAutomationRuleResource\Pages\ListOrderStatusAutomationRules;
use App\Models\Order;
use App\Models\OrderStatusAutomationRule;
use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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

    protected static ?int $navigationSort = 2;

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

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::OrderSetup;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('manage-order-automation') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $statusOptions = ['' => __('automation.status_any')] + Order::getStatuses();

        return $schema->components([
            Section::make(__('automation.section_rule'))
                ->description(__('automation.section_description'))
                ->schema([
                    Select::make('trigger_type')
                        ->label(__('automation.trigger_type'))
                        ->options([
                            OrderStatusAutomationRule::TRIGGER_STATUS => __('automation.trigger_status'),
                            OrderStatusAutomationRule::TRIGGER_COMMENT => __('automation.trigger_comment'),
                        ])
                        ->default(OrderStatusAutomationRule::TRIGGER_STATUS)
                        ->required()
                        ->live(),

                    Select::make('status')
                        ->label(__('automation.status'))
                        ->options(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_COMMENT
                            ? $statusOptions
                            : Order::getStatuses())
                        ->required(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_STATUS)
                        ->default(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_COMMENT ? '' : 'pending'),

                    Select::make('last_comment_from')
                        ->label(__('automation.last_comment_from'))
                        ->options([
                            OrderStatusAutomationRule::LAST_COMMENT_CUSTOMER => __('automation.last_comment_customer'),
                            OrderStatusAutomationRule::LAST_COMMENT_STAFF => __('automation.last_comment_staff'),
                            OrderStatusAutomationRule::LAST_COMMENT_ANY => __('automation.last_comment_any'),
                        ])
                        ->required(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_COMMENT)
                        ->visible(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_COMMENT),

                    Grid::make(2)->schema([
                        TextInput::make('days')
                            ->label(__('automation.days'))
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->required()
                            ->suffix(__('automation.days_suffix')),

                        TextInput::make('hours')
                            ->label(__('automation.hours'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->suffix(__('automation.hours_suffix')),
                    ]),

                    Select::make('action_type')
                        ->label(__('automation.action_type'))
                        ->options([
                            OrderStatusAutomationRule::ACTION_COMMENT => __('automation.action_comment'),
                            OrderStatusAutomationRule::ACTION_CHANGE_STATUS => __('automation.action_change_status'),
                            OrderStatusAutomationRule::ACTION_BOTH => __('automation.action_both'),
                        ])
                        ->default(OrderStatusAutomationRule::ACTION_COMMENT)
                        ->required()
                        ->live()
                        ->visible(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_STATUS),

                    Select::make('action_status')
                        ->label(__('automation.action_status'))
                        ->options(Order::getStatuses())
                        ->required(fn ($get) => in_array($get('action_type') ?? 'comment', [OrderStatusAutomationRule::ACTION_CHANGE_STATUS, OrderStatusAutomationRule::ACTION_BOTH], true))
                        ->visible(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_STATUS && in_array($get('action_type') ?? 'comment', [OrderStatusAutomationRule::ACTION_CHANGE_STATUS, OrderStatusAutomationRule::ACTION_BOTH], true))
                        ->helperText(__('automation.action_status_help')),

                    Textarea::make('comment_template')
                        ->label(__('automation.comment_template'))
                        ->required(fn ($get) => in_array($get('action_type') ?? 'comment', [OrderStatusAutomationRule::ACTION_COMMENT, OrderStatusAutomationRule::ACTION_BOTH], true) || ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_COMMENT)
                        ->rows(4)
                        ->maxLength(2000)
                        ->placeholder(__('automation.comment_placeholder'))
                        ->helperText(__('automation.comment_helper'))
                        ->visible(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_COMMENT || in_array($get('action_type') ?? 'comment', [OrderStatusAutomationRule::ACTION_COMMENT, OrderStatusAutomationRule::ACTION_BOTH], true)),

                    Grid::make(2)->schema([
                        TextInput::make('pause_if_no_reply_days')
                            ->label(__('automation.pause_if_no_reply_days'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix(__('automation.days_suffix'))
                            ->helperText(__('automation.pause_if_no_reply_help')),

                        TextInput::make('pause_if_no_reply_hours')
                            ->label(__('automation.pause_if_no_reply_hours'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix(__('automation.hours_suffix')),
                    ])
                        ->visible(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_STATUS),

                    Radio::make('comment_is_internal')
                        ->label(__('automation.comment_visibility'))
                        ->boolean(__('automation.comment_visible_team'), __('automation.comment_visible_public'))
                        ->default(false)
                        ->helperText(__('automation.comment_visibility_help'))
                        ->visible(fn ($get) => ($get('trigger_type') ?? 'status') === OrderStatusAutomationRule::TRIGGER_COMMENT || in_array($get('action_type') ?? 'comment', [OrderStatusAutomationRule::ACTION_COMMENT, OrderStatusAutomationRule::ACTION_BOTH], true)),

                    Toggle::make('is_active')
                        ->label(__('automation.is_active'))
                        ->default(true),

                    Toggle::make('notify_customer_email')
                        ->label(__('automation.notify_customer_email'))
                        ->helperText(__('automation.notify_customer_email_help'))
                        ->default(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trigger_type')
                    ->label(__('automation.trigger_type'))
                    ->formatStateUsing(fn (string $state): string => $state === OrderStatusAutomationRule::TRIGGER_STATUS
                        ? __('automation.trigger_status')
                        : __('automation.trigger_comment'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('automation.status'))
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null || $state === '') {
                            return __('automation.status_any');
                        }

                        return Order::getStatuses()[$state] ?? $state;
                    })
                    ->sortable(),

                TextColumn::make('last_comment_from')
                    ->label(__('automation.last_comment_from'))
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null || $state === '') {
                            return '—';
                        }

                        return match ($state) {
                            OrderStatusAutomationRule::LAST_COMMENT_CUSTOMER => __('automation.last_comment_customer'),
                            OrderStatusAutomationRule::LAST_COMMENT_STAFF => __('automation.last_comment_staff'),
                            OrderStatusAutomationRule::LAST_COMMENT_ANY => __('automation.last_comment_any'),
                            default => $state,
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('days')
                    ->label(__('automation.duration'))
                    ->formatStateUsing(function (OrderStatusAutomationRule $record): string {
                        $parts = [];
                        if ($record->days > 0) {
                            $parts[] = $record->days.' '.__('automation.days_suffix');
                        }
                        if ($record->hours > 0) {
                            $parts[] = $record->hours.' '.__('automation.hours_suffix');
                        }

                        return implode(' ', $parts) ?: '0 '.__('automation.hours_suffix');
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

                        return $query->orderByRaw("(days * 24 + hours) {$dir}");
                    }),

                TextColumn::make('action_type')
                    ->label(__('automation.action_type'))
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        OrderStatusAutomationRule::ACTION_COMMENT => __('automation.action_comment'),
                        OrderStatusAutomationRule::ACTION_CHANGE_STATUS => __('automation.action_change_status'),
                        OrderStatusAutomationRule::ACTION_BOTH => __('automation.action_both'),
                        default => '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('action_status')
                    ->label(__('automation.action_status'))
                    ->formatStateUsing(fn (?string $state): string => $state ? (Order::getStatuses()[$state] ?? $state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('comment_template')
                    ->label(__('automation.comment_template'))
                    ->limit(50)
                    ->wrap(),

                IconColumn::make('comment_is_internal')
                    ->label(__('automation.comment_visibility'))
                    ->boolean(),

                TextColumn::make('pause_if_no_reply')
                    ->label(__('automation.pause_label'))
                    ->formatStateUsing(function (OrderStatusAutomationRule $record): string {
                        if (! $record->hasPauseIfNoReply()) {
                            return '—';
                        }
                        $parts = [];
                        if ($record->pause_if_no_reply_days > 0) {
                            $parts[] = $record->pause_if_no_reply_days.' '.__('automation.days_suffix');
                        }
                        if ($record->pause_if_no_reply_hours > 0) {
                            $parts[] = $record->pause_if_no_reply_hours.' '.__('automation.hours_suffix');
                        }

                        return implode(' ', $parts);
                    }),

                IconColumn::make('is_active')
                    ->label(__('automation.is_active'))
                    ->boolean(),

                IconColumn::make('notify_customer_email')
                    ->label(__('automation.notify_email'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

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
