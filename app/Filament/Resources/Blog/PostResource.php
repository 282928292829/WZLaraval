<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\PostResource\Pages\CreatePost;
use App\Filament\Resources\Blog\PostResource\Pages\EditPost;
use App\Filament\Resources\Blog\PostResource\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Blog Posts';

    protected static string|\UnitEnum|null $navigationGroup = 'Blog';

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
            Section::make('Content')->schema([
                TextInput::make('title_ar')
                    ->label('Title (Arabic)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('title_en')
                    ->label('Title (English)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(table: 'posts', column: 'slug', ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->helperText('URL-friendly identifier.'),

                Textarea::make('excerpt_ar')
                    ->label('Excerpt (Arabic)')
                    ->rows(3)
                    ->maxLength(500),

                Textarea::make('excerpt_en')
                    ->label('Excerpt (English)')
                    ->rows(3)
                    ->maxLength(500),

                RichEditor::make('body_ar')
                    ->label('Body (Arabic)')
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('posts/attachments'),

                RichEditor::make('body_en')
                    ->label('Body (English)')
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('posts/attachments'),
            ])->columns(2),

            // ── Sidebar sections ─────────────────────────────────────────────
            Section::make('Publish')->schema([
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                    ])
                    ->required()
                    ->default('draft'),

                DateTimePicker::make('published_at')
                    ->label('Publish At')
                    ->nullable()
                    ->helperText('Leave blank to publish immediately when status is set to Published.'),

                Select::make('post_category_id')
                    ->label('Category')
                    ->relationship('category', 'name_en')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('user_id')
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id()),
            ])->collapsible(),

            Section::make('Featured Image')->schema([
                FileUpload::make('featured_image')
                    ->label('Featured Image')
                    ->image()
                    ->disk('public')
                    ->directory('posts/images')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('1200')
                    ->imageResizeTargetHeight('675')
                    ->nullable(),
            ])->collapsible(),

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
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->disk('public')
                    ->width(60)
                    ->height(40),

                TextColumn::make('title_en')
                    ->label('Title (EN)')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('title_ar')
                    ->label('Title (AR)')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('category.name_en')
                    ->label('Category')
                    ->placeholder('—')
                    ->badge()
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft'     => 'gray',
                        default     => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('comments_count')
                    ->label('Comments')
                    ->counts('comments')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                    ]),

                SelectFilter::make('post_category_id')
                    ->label('Category')
                    ->relationship('category', 'name_en')
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit'   => EditPost::route('/{record}/edit'),
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
