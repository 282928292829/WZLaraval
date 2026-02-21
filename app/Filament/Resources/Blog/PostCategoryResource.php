<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\PostCategoryResource\Pages\CreatePostCategory;
use App\Filament\Resources\Blog\PostCategoryResource\Pages\EditPostCategory;
use App\Filament\Resources\Blog\PostCategoryResource\Pages\ListPostCategories;
use App\Models\PostCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostCategoryResource extends Resource
{
    protected static ?string $model = PostCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    public static function getNavigationLabel(): string
    {
        return __('Categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Blog');
    }

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-posts') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Category Details'))->schema([
                TextInput::make('name_ar')
                    ->label(__('Name (Arabic)'))
                    ->required()
                    ->maxLength(100),

                TextInput::make('name_en')
                    ->label(__('Name (English)'))
                    ->required()
                    ->maxLength(100),

                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(table: 'post_categories', column: 'slug', ignoreRecord: true)
                    ->maxLength(120)
                    ->helperText(__('URL-friendly identifier. Leave blank to auto-generate.'))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null),

                Select::make('parent_id')
                    ->label(__('Parent Category'))
                    ->relationship('parent', 'name_en')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText(__('Optional. Creates a sub-category.')),

                TextInput::make('sort_order')
                    ->label(__('Sort Order'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])->columns(2),

            Section::make(__('Description'))->schema([
                Textarea::make('description_ar')
                    ->label(__('Description (Arabic)'))
                    ->rows(3),

                Textarea::make('description_en')
                    ->label(__('Description (English)'))
                    ->rows(3),
            ])->columns(2)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')
                    ->label(__('Name (EN)'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_ar')
                    ->label(__('Name (AR)'))
                    ->searchable(),

                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parent.name_en')
                    ->label(__('Parent'))
                    ->placeholder(__('—')),

                TextColumn::make('posts_count')
                    ->label(__('Posts'))
                    ->counts('posts')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPostCategories::route('/'),
            'create' => CreatePostCategory::route('/create'),
            'edit'   => EditPostCategory::route('/{record}/edit'),
        ];
    }
}
