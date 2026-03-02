<?php

namespace App\Filament\Resources\OrderComments\Schemas;

use App\Models\OrderComment;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderCommentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order.id')
                    ->label('Order'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('body')
                    ->columnSpanFull(),
                IconEntry::make('is_internal')
                    ->boolean(),
                IconEntry::make('is_system')
                    ->boolean(),
                IconEntry::make('is_edited')
                    ->boolean(),
                TextEntry::make('edited_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_by')
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
                    ->visible(fn (OrderComment $record): bool => $record->trashed()),
            ]);
    }
}
