<?php

namespace App\Filament\Resources\Blog;

use App\Filament\Resources\Blog\PostCommentResource\Pages\ListPostComments;
use App\Models\PostComment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostCommentResource extends Resource
{
    protected static ?string $model = PostComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Blog Comments';

    protected static string|\UnitEnum|null $navigationGroup = 'Blog';

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
            Section::make('Comment')->schema([
                TextInput::make('post.title_en')
                    ->label('Post')
                    ->disabled(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'spam'     => 'Spam',
                    ])
                    ->required(),

                TextInput::make('guest_name')
                    ->label('Name')
                    ->disabled(),

                TextInput::make('guest_email')
                    ->label('Email')
                    ->disabled(),

                Textarea::make('body')
                    ->label('Comment')
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
                    ->label('Post')
                    ->limit(40)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('author_display')
                    ->label('Author')
                    ->getStateUsing(fn (PostComment $record): string => $record->getAuthorName())
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('guest_name', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('email_display')
                    ->label('Email')
                    ->getStateUsing(fn (PostComment $record): ?string => $record->getAuthorEmail())
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('body')
                    ->label('Comment')
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending'  => 'warning',
                        'spam'     => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('parent_id')
                    ->label('Reply')
                    ->getStateUsing(fn (PostComment $record): string => $record->parent_id ? 'Reply' : 'Top-level')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Reply' ? 'info' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'spam'     => 'Spam',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (PostComment $record): bool => $record->status !== 'approved')
                    ->action(function (PostComment $record): void {
                        $record->update(['status' => 'approved']);
                        Notification::make()->title('Comment approved')->success()->send();
                    }),

                Action::make('spam')
                    ->label('Spam')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('warning')
                    ->visible(fn (PostComment $record): bool => $record->status !== 'spam')
                    ->requiresConfirmation()
                    ->action(function (PostComment $record): void {
                        $record->update(['status' => 'spam']);
                        Notification::make()->title('Marked as spam')->warning()->send();
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
