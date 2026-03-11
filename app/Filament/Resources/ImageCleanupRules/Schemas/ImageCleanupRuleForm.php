<?php

namespace App\Filament\Resources\ImageCleanupRules\Schemas;

use App\Models\ImageCleanupRule;
use App\Models\Order;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ImageCleanupRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        $statusOptions = collect(Order::getStatuses())->mapWithKeys(fn ($label, $key) => [$key => $label])->all();

        return $schema
            ->components([
                Hidden::make('rule_type'),
                Section::make(__('image_cleanup.triggers_section'))
                    ->description(__('image_cleanup.triggers_section_help'))
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([
                        CheckboxList::make('statuses')
                            ->label(__('image_cleanup.statuses'))
                            ->options($statusOptions)
                            ->columns(3)
                            ->required(),

                        TextInput::make('compression_quality')
                            ->label(__('image_cleanup.compression_quality'))
                            ->helperText(__('image_cleanup.compression_quality_help'))
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(95)
                            ->default(55)
                            ->visible(fn ($get) => $get('rule_type') === ImageCleanupRule::TYPE_COMPRESS),
                    ])
                    ->columns(2),

                Section::make(__('image_cleanup.file_types_section'))
                    ->description(__('image_cleanup.file_types_section_help'))
                    ->icon(Heroicon::OutlinedDocument)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('retention_days_customer_product')
                                ->label(__('image_cleanup.retention_customer_product'))
                                ->numeric()
                                ->minValue(1)
                                ->default(14)
                                ->required(),
                            Toggle::make('customer_product')
                                ->label(__('image_cleanup.toggle_customer_product'))
                                ->default(true),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('retention_days_staff_product')
                                ->label(__('image_cleanup.retention_staff_product'))
                                ->numeric()
                                ->minValue(1)
                                ->default(90)
                                ->required(),
                            Toggle::make('staff_product')
                                ->label(__('image_cleanup.toggle_staff_product'))
                                ->default(false),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('retention_days_customer_comment')
                                ->label(__('image_cleanup.retention_customer_comment'))
                                ->numeric()
                                ->minValue(1)
                                ->default(14)
                                ->required(),
                            Toggle::make('customer_comment')
                                ->label(__('image_cleanup.toggle_customer_comment'))
                                ->default(true),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('retention_days_staff_comment')
                                ->label(__('image_cleanup.retention_staff_comment'))
                                ->numeric()
                                ->minValue(1)
                                ->default(90)
                                ->required(),
                            Toggle::make('staff_comment')
                                ->label(__('image_cleanup.toggle_staff_comment'))
                                ->default(false),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('receipt')
                                ->label(__('image_cleanup.toggle_receipt'))
                                ->default(false),
                            Toggle::make('invoice')
                                ->label(__('image_cleanup.toggle_invoice'))
                                ->default(false),
                            Toggle::make('other')
                                ->label(__('image_cleanup.toggle_other'))
                                ->default(false),
                        ]),
                    ])
                    ->columns(2),

                Section::make(__('image_cleanup.rule_options'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('image_cleanup.is_active'))
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label(__('image_cleanup.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
