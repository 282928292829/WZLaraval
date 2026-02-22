<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'balances';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Balance Ledger');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label(__('Type'))
                    ->options([
                        'credit' => __('account.balance_credit'),
                        'debit' => __('account.balance_debit'),
                    ])
                    ->required()
                    ->native(false),

                TextInput::make('amount')
                    ->label(__('account.balance_amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),

                Select::make('currency')
                    ->label(__('account.balance_currency'))
                    ->options([
                        'SAR' => 'SAR — ر.س',
                        'USD' => 'USD — $',
                        'EUR' => 'EUR — €',
                        'GBP' => 'GBP — £',
                        'AED' => 'AED — د.إ',
                        'KWD' => 'KWD — د.ك',
                        'QAR' => 'QAR — ر.ق',
                        'BHD' => 'BHD — .د.ب',
                        'OMR' => 'OMR — ر.ع.',
                        'EGP' => 'EGP — ج.م',
                        'TRY' => 'TRY — ₺',
                        'CAD' => 'CAD — CA$',
                        'AUD' => 'AUD — A$',
                    ])
                    ->default('SAR')
                    ->required()
                    ->native(false)
                    ->searchable(),

                DatePicker::make('date')
                    ->label(__('account.balance_date'))
                    ->default(now())
                    ->required()
                    ->native(false),

                Textarea::make('note')
                    ->label(__('account.balance_note'))
                    ->placeholder(__('e.g. Bank transfer from order #1234, PayPal payment, leftover from order #999...'))
                    ->rows(3)
                    ->required()
                    ->maxLength(1000),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->label(__('account.balance_date'))
                    ->date('d M Y')
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label(__('account.balance_type'))
                    ->colors([
                        'success' => 'credit',
                        'danger' => 'debit',
                    ])
                    ->formatStateUsing(fn (string $state) => $state === 'credit'
                        ? __('account.balance_credit')
                        : __('account.balance_debit')),

                TextColumn::make('amount')
                    ->label(__('account.balance_amount'))
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2).' '.$record->currency)
                    ->sortable(),

                TextColumn::make('note')
                    ->label(__('account.balance_note'))
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->note),

                TextColumn::make('creator.name')
                    ->label(__('account.balance_added_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Add Entry'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
