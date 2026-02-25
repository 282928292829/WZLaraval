<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\CommentTemplateResource\Pages\CreateCommentTemplate;
use App\Filament\Resources\Orders\CommentTemplateResource\Pages\EditCommentTemplate;
use App\Filament\Resources\Orders\CommentTemplateResource\Pages\ListCommentTemplates;
use App\Models\CommentTemplate;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CommentTemplateResource extends Resource
{
    protected static ?string $model = CommentTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function getNavigationLabel(): string
    {
        return __('Comment Templates');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function getTitle(): string
    {
        return __('Comment Templates');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('manage-comment-templates') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Template Details'))->schema([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255),

                Textarea::make('content')
                    ->label(__('Content'))
                    ->required()
                    ->rows(5)
                    ->maxLength(5000),

                TextInput::make('sort_order')
                    ->label(__('Sort Order'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('content')
                    ->label(__('Content'))
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('usage_count')
                    ->label(__('Uses'))
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable()
                    ->alignEnd(),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('Active')),
            ])
            ->defaultSort('usage_count', 'desc')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommentTemplates::route('/'),
            'create' => CreateCommentTemplate::route('/create'),
            'edit' => EditCommentTemplate::route('/{record}/edit'),
        ];
    }
}
