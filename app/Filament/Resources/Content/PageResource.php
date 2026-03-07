<?php

namespace App\Filament\Resources\Content;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\Content\PageResource\Pages\CreatePage;
use App\Filament\Resources\Content\PageResource\Pages\EditPage;
use App\Filament\Resources\Content\PageResource\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::Content;
    }

    public static function getNavigationLabel(): string
    {
        return __('Static Pages');
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-pages') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Content'))->schema([
                TextInput::make('title_ar')
                    ->label(__('Title (Arabic)'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('title_en')
                    ->label(__('Title (English)'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(table: 'pages', column: 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText(__('URL path: /faq, /about, /calculator, etc.')),

                RichEditor::make('body_ar')
                    ->label(__('Body (Arabic)'))
                    ->columnSpanFull(),

                RichEditor::make('body_en')
                    ->label(__('Body (English)'))
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make(__('Publish & Navigation'))->schema([
                Toggle::make('is_published')
                    ->label(__('Published'))
                    ->onColor('success')
                    ->helperText(__('Unpublished pages return 404.')),

                Toggle::make('show_in_header')
                    ->label(__('Show in Header Menu'))
                    ->helperText(__('Adds a link to the top navigation.')),

                Toggle::make('show_in_footer')
                    ->label(__('Show in Footer Menu'))
                    ->helperText(__('Adds a link to the footer navigation.')),

                TextInput::make('menu_order')
                    ->label(__('Menu Order'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText(__('Lower number = appears first in navigation.')),

                Toggle::make('allow_comments')
                    ->label(__('Allow Comments'))
                    ->default(false)
                    ->helperText(__('When ON, comments are shown on this page.')),
            ])->columns(2)->collapsible(),

            Section::make(__('SEO'))->schema([
                TextInput::make('seo_title_ar')
                    ->label(__('SEO Title (Arabic)'))
                    ->maxLength(70)
                    ->helperText(new \Illuminate\Support\HtmlString('<span class="text-gray-400 text-xs">'.__('seo.title_help').'</span>')),

                TextInput::make('seo_title_en')
                    ->label(__('SEO Title (English)'))
                    ->maxLength(70)
                    ->helperText(new \Illuminate\Support\HtmlString('<span class="text-gray-400 text-xs">'.__('seo.title_help').'</span>')),

                Textarea::make('seo_description_ar')
                    ->label(__('SEO Description (Arabic)'))
                    ->rows(2)
                    ->maxLength(160)
                    ->helperText(new \Illuminate\Support\HtmlString('<span class="text-gray-400 text-xs">'.__('seo.description_help').'</span>')),

                Textarea::make('seo_description_en')
                    ->label(__('SEO Description (English)'))
                    ->rows(2)
                    ->maxLength(160)
                    ->helperText(new \Illuminate\Support\HtmlString('<span class="text-gray-400 text-xs">'.__('seo.description_help').'</span>')),

                FileUpload::make('og_image')
                    ->label(__('OG Image'))
                    ->helperText(new \Illuminate\Support\HtmlString('<span class="text-gray-400 text-xs">'.__('seo.og_image_help').'</span>'))
                    ->image()
                    ->directory('og-images')
                    ->nullable(),

                TextInput::make('canonical_url')
                    ->label(__('Canonical URL'))
                    ->url()
                    ->placeholder('https://example.com/slug')
                    ->nullable()
                    ->maxLength(500)
                    ->helperText(new \Illuminate\Support\HtmlString('<span class="text-gray-400 text-xs">'.__('seo.canonical_help').'</span>')),

                Select::make('robots')
                    ->label(__('Robots'))
                    ->options([
                        '' => __('Default (index, follow)'),
                        'noindex, follow' => __('noindex, follow'),
                        'index, nofollow' => __('index, nofollow'),
                        'noindex, nofollow' => __('noindex, nofollow'),
                    ])
                    ->helperText(new \Illuminate\Support\HtmlString(
                        '<span class="text-gray-400 text-xs">'.__('seo.robots_help').'</span>'
                    ))
                    ->nullable(),
            ])->columns(2)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_en')
                    ->label(__('Title (EN)'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title_ar')
                    ->label(__('Title (AR)'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->copyable()
                    ->prefix('/'),

                IconColumn::make('is_published')
                    ->label(__('Published'))
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('show_in_header')
                    ->label(__('Header'))
                    ->boolean()
                    ->trueColor('info')
                    ->falseColor('gray'),

                IconColumn::make('show_in_footer')
                    ->label(__('Footer'))
                    ->boolean()
                    ->trueColor('info')
                    ->falseColor('gray'),

                TextColumn::make('menu_order')
                    ->label(__('Order'))
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('menu_order')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label(__('Published'))
                    ->trueLabel(__('Published only'))
                    ->falseLabel(__('Unpublished only')),

                TernaryFilter::make('show_in_header')
                    ->label(__('In Header')),

                TernaryFilter::make('show_in_footer')
                    ->label(__('In Footer')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('View page'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Page $record): string => route('pages.show', $record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
