<?php

namespace App\Filament\Resources\OrderComments\Tables;

use App\Models\OrderComment;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrderCommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order', 'user']))
            ->columns([
                TextColumn::make('body')
                    ->label(__('orders.comments'))
                    ->limit(60)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('order.order_number')
                    ->label(__('Order Number'))
                    ->searchable()
                    ->sortable()
                    ->url(fn (OrderComment $record): string => route('orders.show', $record->order_id)),
                TextColumn::make('user.name')
                    ->label(__('Author'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_internal')
                    ->label(__('orders.internal_note'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_internal')
                    ->label(__('orders.internal_note'))
                    ->placeholder(__('All')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('open_order')
                    ->label(__('orders.action_open'))
                    ->icon('heroicon-o-document-text')
                    ->url(fn (OrderComment $record): string => route('orders.show', $record->order_id)),
                Action::make('open_comment')
                    ->label(__('Open comment'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (OrderComment $record): string => route('orders.show', $record->order_id).'#comment-'.$record->id),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
