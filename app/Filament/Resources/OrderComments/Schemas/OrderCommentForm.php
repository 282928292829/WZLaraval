<?php

namespace App\Filament\Resources\OrderComments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrderCommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_internal')
                    ->required(),
                Toggle::make('is_system')
                    ->required(),
                Toggle::make('is_edited')
                    ->required(),
                DateTimePicker::make('edited_at'),
                TextInput::make('deleted_by')
                    ->numeric(),
            ]);
    }
}
