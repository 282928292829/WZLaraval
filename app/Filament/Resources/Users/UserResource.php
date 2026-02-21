<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getNavigationLabel(): string
    {
        return __('Users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-users') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $allPermissions = Permission::orderBy('name')->pluck('name', 'name')->toArray();
        $allRoles       = Role::orderBy('name')->pluck('name', 'name')->toArray();

        return $schema
            ->components([
                Section::make(__('Account Details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(100),

                        TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true),

                        TextInput::make('phone')
                            ->label(__('Phone'))
                            ->nullable()
                            ->maxLength(30),

                        TextInput::make('password')
                            ->label(__('New Password'))
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make(__('Status'))
                    ->schema([
                        Toggle::make('is_banned')
                            ->label(__('Banned'))
                            ->onColor('danger')
                            ->offColor('success')
                            ->helperText(__('Banned users cannot log in.')),

                        TextInput::make('banned_reason')
                            ->label(__('Ban Reason'))
                            ->maxLength(255)
                            ->nullable()
                            ->visible(fn ($get) => (bool) $get('is_banned')),
                    ]),

                Section::make(__('Role'))
                    ->schema([
                        Select::make('roles')
                            ->label(__('Role'))
                            ->options($allRoles)
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                            )
                            ->preload()
                            ->searchable()
                            ->multiple()
                            ->helperText(__('Assign one or more roles. Roles carry bundled permissions.')),
                    ]),

                Section::make(__('Permission Overrides'))
                    ->description(__('Grant or revoke individual permissions on top of this user\'s role(s).'))
                    ->schema([
                        CheckboxList::make('direct_permissions')
                            ->label(__('Direct Permissions'))
                            ->options($allPermissions)
                            ->bulkToggleable()
                            ->columns(3)
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state(
                                        $record->getDirectPermissions()->pluck('name')->toArray()
                                    );
                                }
                            }),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('roles.name')
                    ->label(__('Role'))
                    ->badge()
                    ->separator(','),

                IconColumn::make('is_banned')
                    ->label(__('Banned'))
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedNoSymbol)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('created_at')
                    ->label(__('Joined'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_banned')
                    ->label(__('Banned Status'))
                    ->trueLabel(__('Banned only'))
                    ->falseLabel(__('Active only')),

                SelectFilter::make('roles')
                    ->label(__('Role'))
                    ->relationship('roles', 'name')
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('toggleBan')
                    ->label(fn (User $record) => $record->is_banned ? __('Unban') : __('Ban'))
                    ->icon(fn (User $record) => $record->is_banned
                        ? Heroicon::OutlinedCheckCircle
                        : Heroicon::OutlinedNoSymbol)
                    ->color(fn (User $record) => $record->is_banned ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = auth()->user();

                        return $user?->hasPermissionTo('ban-users') ?? false;
                    })
                    ->action(function (User $record) {
                        $record->is_banned = ! $record->is_banned;
                        $record->banned_at = $record->is_banned ? now() : null;
                        $record->save();
                    }),
            ])
            ->searchable();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit'  => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }
}
