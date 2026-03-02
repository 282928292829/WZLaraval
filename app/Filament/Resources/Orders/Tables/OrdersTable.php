<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Order $record): string => route('orders.show', $record->id))
            ->modifyQueryUsing(fn ($query) => $query->with('user')->withCount('items'))
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('Order Number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => (new Order)->fill(['status' => $state])->statusLabel())
                    ->color(fn (string $state): string => (new Order)->fill(['status' => $state])->statusColor())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('Total'))
                    ->money(fn (Order $record): string => $record->currency ?? 'SAR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_paid')
                    ->label(__('Paid'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items_count')
                    ->label(__('Items'))
                    ->counts('items')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(Order::getStatuses())
                    ->multiple(),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('From date')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('Until date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $v): Builder => $q->whereDate('created_at', '>=', $v))
                            ->when($data['until'], fn (Builder $q, $v): Builder => $q->whereDate('created_at', '<=', $v));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('orders.action_open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Order $record): string => route('orders.show', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkAction::make('change_status')
                    ->label(__('orders.bulk_change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Select::make('new_status')
                            ->label(__('orders.bulk_change_status'))
                            ->options(Order::getStatuses())
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $orders = Order::whereIn('id', $records->pluck('id'))->with('user')->get();
                        foreach ($orders as $order) {
                            if (in_array($data['new_status'], ['cancelled', 'shipped', 'delivered'])) {
                                \App\Models\AdCampaign::incrementForOrderStatus($order, $data['new_status']);
                            }
                        }
                        Order::whereIn('id', $records->pluck('id'))->update(['status' => $data['new_status']]);
                    })
                    ->successNotificationTitle(fn (int $count): string => __('orders.bulk_status_updated', ['count' => $count]))
                    ->deselectRecordsAfterCompletion()
                    ->authorize(fn (): bool => auth()->user()?->can('bulk-update-orders') ?? false),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
