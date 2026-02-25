<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
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
        $labels = config('permissions.labels', []);
        $permissionNames = Permission::orderBy('name')->pluck('name');
        $allPermissions = $permissionNames->mapWithKeys(function (string $name) use ($labels) {
            $key = 'permissions.'.$name;
            $label = __($key);

            return [$name => $label !== $key ? $label : ($labels[$name] ?? str_replace('-', ' ', ucfirst($name)))];
        })->toArray();
        $allRoles = Role::orderBy('name')->pluck('name', 'name')->toArray();

        return $schema
            ->components([
                Section::make(__('Account Info'))
                    ->description(__('Read-only account and registration details.'))
                    ->schema([
                        Placeholder::make('joined')
                            ->label(__('Joined'))
                            ->content(fn (?User $record): string => $record?->created_at
                                ? $record->created_at->format('d M Y H:i')
                                : '—'),

                        Placeholder::make('last_login')
                            ->label(__('Last login'))
                            ->content(fn (?User $record): string => $record?->last_login_at
                                ? $record->last_login_at->format('d M Y H:i')
                                : '—'),

                        Placeholder::make('email_verified')
                            ->label(__('Email verified'))
                            ->content(fn (?User $record): string => $record?->email_verified_at
                                ? $record->email_verified_at->format('d M Y')
                                : __('No')),

                        Placeholder::make('oauth_providers')
                            ->label(__('OAuth providers'))
                            ->content(function (?User $record): string {
                                if (! $record) {
                                    return '—';
                                }
                                $providers = [];
                                if ($record->google_id) {
                                    $providers[] = 'Google';
                                }
                                if ($record->twitter_id) {
                                    $providers[] = 'Twitter';
                                }
                                if ($record->apple_id) {
                                    $providers[] = 'Apple';
                                }

                                return $providers ? implode(', ', $providers) : '—';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make(__('Last Visit'))
                    ->description(__('Device and location from most recent activity.'))
                    ->schema([
                        Placeholder::make('last_visit_at')
                            ->label(__('Last visit'))
                            ->content(function (?User $record): string {
                                if (! $record) {
                                    return '—';
                                }
                                $latest = $record->activityLogs()->latest('created_at')->first();

                                return $latest?->created_at?->format('d M Y H:i') ?? '—';
                            }),

                        Placeholder::make('last_ip')
                            ->label(__('IP address'))
                            ->content(function (?User $record): string {
                                if (! $record) {
                                    return '—';
                                }
                                $latest = $record->activityLogs()->latest('created_at')->first();

                                return $latest?->ip_address ?? '—';
                            }),

                        Placeholder::make('last_city_country')
                            ->label(__('City / Country'))
                            ->content(function (?User $record): string {
                                if (! $record) {
                                    return '—';
                                }
                                $latest = $record->activityLogs()->latest('created_at')->first();

                                return $latest && ($latest->city || $latest->country)
                                    ? trim(implode(', ', array_filter([$latest->city, $latest->country])))
                                    : '—';
                            }),

                        Placeholder::make('last_device_os')
                            ->label(__('Device / OS'))
                            ->content(function (?User $record): string {
                                if (! $record) {
                                    return '—';
                                }
                                $latest = $record->activityLogs()->latest('created_at')->first();

                                return $latest && ($latest->device || $latest->os)
                                    ? trim(implode(' / ', array_filter([$latest->device, $latest->os])))
                                    : '—';
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

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

                Section::make(__('Staff Notes'))
                    ->description(__('Internal notes and attachments about this user. Not visible to the customer.'))
                    ->schema([
                        Textarea::make('staff_notes')
                            ->label(__('Notes'))
                            ->placeholder(__('e.g. VIP customer, prefers WhatsApp, special delivery instructions...'))
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

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

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Users\RelationManagers\ActivityLogsRelationManager::class,
            \App\Filament\Resources\Users\RelationManagers\BalancesRelationManager::class,
            \App\Filament\Resources\Users\RelationManagers\UserFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }
}
