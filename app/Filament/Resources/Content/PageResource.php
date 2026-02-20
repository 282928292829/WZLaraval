<?php

namespace App\Filament\Resources\Content;

use App\Filament\Resources\Content\PageResource\Pages\CreatePage;
use App\Filament\Resources\Content\PageResource\Pages\EditPage;
use App\Filament\Resources\Content\PageResource\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Static Pages';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-pages') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Content')->schema([
                TextInput::make('title_ar')
                    ->label('Title (Arabic)')
                    ->required()
                    ->maxLength(255),

                TextInput::make('title_en')
                    ->label('Title (English)')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(table: 'pages', column: 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText('URL path: /faq, /about, /calculator, etc.'),

                RichEditor::make('body_ar')
                    ->label('Body (Arabic)')
                    ->columnSpanFull(),

                RichEditor::make('body_en')
                    ->label('Body (English)')
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Publish & Navigation')->schema([
                Toggle::make('is_published')
                    ->label('Published')
                    ->onColor('success')
                    ->helperText('Unpublished pages return 404.'),

                Toggle::make('show_in_header')
                    ->label('Show in Header Menu')
                    ->helperText('Adds a link to the top navigation.'),

                Toggle::make('show_in_footer')
                    ->label('Show in Footer Menu')
                    ->helperText('Adds a link to the footer navigation.'),

                TextInput::make('menu_order')
                    ->label('Menu Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Lower number = appears first in navigation.'),
            ])->columns(2)->collapsible(),

            Section::make('SEO')->schema([
                TextInput::make('seo_title_ar')
                    ->label('SEO Title (Arabic)')
                    ->maxLength(70),

                TextInput::make('seo_title_en')
                    ->label('SEO Title (English)')
                    ->maxLength(70),

                Textarea::make('seo_description_ar')
                    ->label('SEO Description (Arabic)')
                    ->rows(2)
                    ->maxLength(160),

                Textarea::make('seo_description_en')
                    ->label('SEO Description (English)')
                    ->rows(2)
                    ->maxLength(160),
            ])->columns(2)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_en')
                    ->label('Title (EN)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title_ar')
                    ->label('Title (AR)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->prefix('/'),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('show_in_header')
                    ->label('Header')
                    ->boolean()
                    ->trueColor('info')
                    ->falseColor('gray'),

                IconColumn::make('show_in_footer')
                    ->label('Footer')
                    ->boolean()
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('menu_order')
                    ->label('Order')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('menu_order')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Published')
                    ->trueLabel('Published only')
                    ->falseLabel('Unpublished only'),

                TernaryFilter::make('show_in_header')
                    ->label('In Header'),

                TernaryFilter::make('show_in_footer')
                    ->label('In Footer'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit'   => EditPage::route('/{record}/edit'),
        ];
    }
}
