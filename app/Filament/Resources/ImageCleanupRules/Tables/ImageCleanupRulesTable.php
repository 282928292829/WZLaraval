<?php

namespace App\Filament\Resources\ImageCleanupRules\Tables;

use App\Models\ImageCleanupRule;
use App\Models\Order;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ImageCleanupRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('statuses')
                    ->label(__('image_cleanup.statuses'))
                    ->formatStateUsing(function (ImageCleanupRule $record): string {
                        $statuses = $record->statuses ?? [];
                        if (empty($statuses)) {
                            return '—';
                        }
                        $labels = Order::getStatuses();

                        return collect($statuses)->map(fn ($s) => $labels[$s] ?? $s)->implode(', ');
                    })
                    ->wrap(),

                TextColumn::make('retention_summary')
                    ->label(__('image_cleanup.retention_summary'))
                    ->formatStateUsing(function (ImageCleanupRule $record): string {
                        $parts = [];
                        if ($record->customer_product) {
                            $parts[] = __('image_cleanup.retention_customer_product').': '.$record->retention_days_customer_product;
                        }
                        if ($record->staff_product) {
                            $parts[] = __('image_cleanup.retention_staff_product').': '.$record->retention_days_staff_product;
                        }
                        if ($record->customer_comment) {
                            $parts[] = __('image_cleanup.retention_customer_comment').': '.$record->retention_days_customer_comment;
                        }
                        if ($record->staff_comment) {
                            $parts[] = __('image_cleanup.retention_staff_comment').': '.$record->retention_days_staff_comment;
                        }
                        if ($record->receipt || $record->invoice || $record->other) {
                            $parts[] = __('image_cleanup.other_types');
                        }

                        return implode('; ', $parts) ?: '—';
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('compression_quality')
                    ->label(__('image_cleanup.compression_quality'))
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? (string) $state : '—')
                    ->visible(fn (ImageCleanupRule $record): bool => $record->isCompress()),

                IconColumn::make('is_active')
                    ->label(__('image_cleanup.is_active'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('image_cleanup.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
