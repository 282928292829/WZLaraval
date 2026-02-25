<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\PostCommentResource\Pages\ListPostComments;
use App\Models\PostComment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostCommentResource extends Resource
{
    protected static ?string $model = PostComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function getNavigationLabel(): string
    {
        return __('Blog Comments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Blog');
    }

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-posts') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Comment'))->schema([
                TextInput::make('post.title_en')
                    ->label(__('Post'))
                    ->disabled(),

                Select::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'spam' => __('Spam'),
                    ])
                    ->required(),

                TextInput::make('guest_name')
                    ->label(__('Name'))
                    ->disabled(),

                TextInput::make('guest_email')
                    ->label(__('Email'))
                    ->disabled(),

                Textarea::make('body')
                    ->label(__('Comment'))
                    ->rows(5)
                    ->disabled()
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('post.title_en')
                    ->label(__('Post'))
                    ->limit(40)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('author_display')
                    ->label(__('Author'))
                    ->getStateUsing(fn (PostComment $record): string => $record->getAuthorName())
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('guest_name', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('email_display')
                    ->label(__('Email'))
                    ->getStateUsing(fn (PostComment $record): ?string => $record->getAuthorEmail())
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('body')
                    ->label(__('Comment'))
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'spam' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('parent_id')
                    ->label(__('Reply'))
                    ->getStateUsing(fn (PostComment $record): string => $record->parent_id ? __('Reply') : __('Top-level'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Reply' ? 'info' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'spam' => __('Spam'),
                    ]),
            ])
            ->recordActions([
                Action::make('editPost')
                    ->label(__('Edit Post'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (PostComment $record): string => PostResource::getUrl('edit', ['record' => $record->post]))
                    ->openUrlInNewTab(),

                Action::make('showPost')
                    ->label(__('Show Post'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn (PostComment $record): string => route('blog.show', $record->post->slug))
                    ->openUrlInNewTab(),

                Action::make('viewComment')
                    ->label(__('View Comment'))
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (PostComment $record): string => route('blog.show', $record->post->slug).'#comment-'.$record->id)
                    ->openUrlInNewTab(),

                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (PostComment $record): bool => $record->status !== 'approved')
                    ->action(function (PostComment $record): void {
                        $record->update(['status' => 'approved']);
                        Notification::make()->title(__('Comment approved'))->success()->send();
                    }),

                Action::make('spam')
                    ->label(__('Spam'))
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('warning')
                    ->visible(fn (PostComment $record): bool => $record->status !== 'spam')
                    ->requiresConfirmation()
                    ->action(function (PostComment $record): void {
                        $record->update(['status' => 'spam']);
                        Notification::make()->title(__('Marked as spam'))->warning()->send();
                    }),

                Action::make('unspam')
                    ->label(__('Restore to Pending'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->visible(fn (PostComment $record): bool => $record->status === 'spam')
                    ->action(function (PostComment $record): void {
                        $record->update(['status' => 'pending']);
                        Notification::make()->title(__('Restored to pending'))->success()->send();
                    }),

                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostComments::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
