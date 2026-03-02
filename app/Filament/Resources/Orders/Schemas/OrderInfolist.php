<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_number'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('layout_option')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('staff_notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('shippingAddress.id')
                    ->label('Shipping address')
                    ->placeholder('-'),
                IconEntry::make('is_paid')
                    ->boolean(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('payment_proof')
                    ->placeholder('-'),
                TextEntry::make('tracking_number')
                    ->placeholder('-'),
                TextEntry::make('tracking_company')
                    ->placeholder('-'),
                TextEntry::make('payment_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('payment_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('payment_method')
                    ->placeholder('-'),
                TextEntry::make('payment_receipt')
                    ->placeholder('-'),
                TextEntry::make('subtotal')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('total_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('agent_fee')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('local_shipping')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('international_shipping')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('photo_fee')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('extra_packing')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('currency'),
                TextEntry::make('can_edit_until')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('merged_into')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('merged_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('merged_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Order $record): bool => $record->trashed()),
            ]);
    }
}
