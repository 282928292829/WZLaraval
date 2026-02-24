<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\PostResource\Pages\CreatePost;
use App\Filament\Resources\Blog\PostResource\Pages\EditPost;
use App\Filament\Resources\Blog\PostResource\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    public static function getNavigationLabel(): string
    {
        return __('Blog Posts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Blog');
    }

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-posts') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // ── Main content (2/3 width on desktop) ──────────────────────────
            Section::make(__('Content'))->schema([
                TextInput::make('title_ar')
                    ->label(__('Title (Arabic)'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('title_en')
                    ->label(__('Title (English)'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(table: 'posts', column: 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText(__('URL-friendly identifier.')),

                Textarea::make('excerpt_ar')
                    ->label(__('Excerpt (Arabic)'))
                    ->rows(3)
                    ->maxLength(500),

                Textarea::make('excerpt_en')
                    ->label(__('Excerpt (English)'))
                    ->rows(3)
                    ->maxLength(500),

                RichEditor::make('body_ar')
                    ->label(__('Body (Arabic)'))
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('posts/attachments'),

                RichEditor::make('body_en')
                    ->label(__('Body (English)'))
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('posts/attachments'),
            ])->columns(2),

            // ── Sidebar sections ─────────────────────────────────────────────
            Section::make(__('Publish'))->schema([
                Select::make('status')
                    ->label(__('Status'))
                    ->options([
                        'draft' => __('Draft'),
                        'published' => __('Published'),
                    ])
                    ->required()
                    ->default('draft'),

                DateTimePicker::make('published_at')
                    ->label(__('Publish At'))
                    ->nullable()
                    ->helperText(__('Leave blank to publish immediately when status is set to Published.')),

                Select::make('post_category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name_en')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('user_id')
                    ->label(__('Author'))
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id()),

                Toggle::make('allow_comments')
                    ->label(__('Allow Comments'))
                    ->default(true)
                    ->helperText(__('When OFF, the comment form and list are hidden on this post.')),
            ])->collapsible(),

            Section::make(__('Featured Image'))->schema([
                FileUpload::make('featured_image')
                    ->label(__('Featured Image'))
                    ->image()
                    ->disk('public')
                    ->directory('posts/images')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('675')
                    ->nullable(),
            ])->collapsible(),

            Section::make(__('SEO'))->schema([
                TextInput::make('seo_title_ar')
                    ->label(__('SEO Title (Arabic)'))
                    ->maxLength(70),

                TextInput::make('seo_title_en')
                    ->label(__('SEO Title (English)'))
                    ->maxLength(70),

                Textarea::make('seo_description_ar')
                    ->label(__('SEO Description (Arabic)'))
                    ->rows(2)
                    ->maxLength(160),

                Textarea::make('seo_description_en')
                    ->label(__('SEO Description (English)'))
                    ->rows(2)
                    ->maxLength(160),
            ])->columns(2)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label(__('Image'))
                    ->disk('public')
                    ->width(60)
                    ->height(40),

                TextColumn::make('title_en')
                    ->label(__('Title (EN)'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('title_ar')
                    ->label(__('Title (AR)'))
                    ->searchable()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('category.name_en')
                    ->label(__('Category'))
                    ->placeholder(__('—'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label(__('Author'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->label(__('Published'))
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder(__('—')),

                TextColumn::make('comments_count')
                    ->label(__('Comments'))
                    ->counts('comments')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'draft' => __('Draft'),
                        'published' => __('Published'),
                    ]),

                SelectFilter::make('post_category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name_en')
                    ->preload(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('View page'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Post $record): string => route('blog.show', $record->slug))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'draft')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
