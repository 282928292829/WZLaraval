<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'needs_payment' => 'Needs payment',
                        'processing' => 'Processing',
                        'purchasing' => 'Purchasing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'on_hold' => 'On hold',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('layout_option')
                    ->required()
                    ->numeric()
                    ->default(2),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Textarea::make('staff_notes')
                    ->columnSpanFull(),
                Select::make('shipping_address_id')
                    ->relationship('shippingAddress', 'id'),
                TextInput::make('shipping_address_snapshot'),
                Toggle::make('is_paid')
                    ->required(),
                DateTimePicker::make('paid_at'),
                TextInput::make('payment_proof'),
                TextInput::make('tracking_number'),
                TextInput::make('tracking_company'),
                TextInput::make('payment_amount')
                    ->numeric(),
                DatePicker::make('payment_date'),
                TextInput::make('payment_method'),
                TextInput::make('payment_receipt'),
                TextInput::make('subtotal')
                    ->numeric(),
                TextInput::make('total_amount')
                    ->numeric(),
                TextInput::make('agent_fee')
                    ->numeric(),
                TextInput::make('local_shipping')
                    ->numeric(),
                TextInput::make('international_shipping')
                    ->numeric(),
                TextInput::make('photo_fee')
                    ->numeric(),
                TextInput::make('extra_packing')
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                DateTimePicker::make('can_edit_until'),
                TextInput::make('merged_into')
                    ->numeric(),
                DateTimePicker::make('merged_at'),
                TextInput::make('merged_by')
                    ->numeric(),
            ]);
    }
}
