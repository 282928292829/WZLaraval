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

    protected static ?string $navigationLabel = 'Categories';

    protected static string|\UnitEnum|null $navigationGroup = 'Blog';

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
            Section::make('Category Details')->schema([
                TextInput::make('name_ar')
                    ->label('Name (Arabic)')
                    ->required()
                    ->maxLength(100),

                TextInput::make('name_en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(100),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(table: 'post_categories', column: 'slug', ignoreRecord: true)
                    ->maxLength(120)
                    ->helperText('URL-friendly identifier. Leave blank to auto-generate.')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null),

                Select::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'name_en')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Optional. Creates a sub-category.'),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ])->columns(2),

            Section::make('Description')->schema([
                Textarea::make('description_ar')
                    ->label('Description (Arabic)')
                    ->rows(3),

                Textarea::make('description_en')
                    ->label('Description (English)')
                    ->rows(3),
            ])->columns(2)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')
                    ->label('Name (EN)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_ar')
                    ->label('Name (AR)')
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parent.name_en')
                    ->label('Parent')
                    ->placeholder('—'),

                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->counts('posts')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Order')
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
