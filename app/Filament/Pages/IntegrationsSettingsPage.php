<?php

namespace App\Filament\Pages;

use App\Enums\AdminNavigationGroup;
use App\Models\Setting;
use App\Services\SettingsPersistService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class IntegrationsSettingsPage extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('Integrations');
    }

    public function getTitle(): string
    {
        return __('Integrations Settings');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    /** @var list<string> */
    protected const INTEGRATION_KEYS = [
        'google_login_enabled',
        'twitter_login_enabled',
        'facebook_login_enabled',
        'apple_login_enabled',
    ];

    public function mount(): void
    {
        $data = $this->defaults();
        $allSettings = Setting::all()->keyBy('key');

        foreach (static::INTEGRATION_KEYS as $key) {
            $setting = $allSettings->get($key);
            if ($setting) {
                $data[$key] = $setting->type === 'json'
                    ? json_decode($setting->value, true)
                    : $setting->value;
            }
        }

        $this->data = $data;
        $this->form->fill($this->data);
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'google_login_enabled' => '0',
            'twitter_login_enabled' => '0',
            'facebook_login_enabled' => '0',
            'apple_login_enabled' => '0',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('integrations-settings-tabs')
                    ->tabs([
                        Tab::make(__('Social Login'))
                            ->icon(Heroicon::OutlinedUserGroup)
                            ->schema([
                                Section::make(__('Social Login'))
                                    ->icon(Heroicon::OutlinedUserGroup)
                                    ->description(__('Allow users to sign in/up using third-party accounts. Requires OAuth credentials in .env.'))
                                    ->schema([
                                        Toggle::make('google_login_enabled')
                                            ->label(__('Enable Google Sign-In'))
                                            ->helperText(__('Shows a "Sign in with Google" button on the login and register pages. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file first.'))
                                            ->onColor('success')
                                            ->columnSpanFull(),

                                        Toggle::make('twitter_login_enabled')
                                            ->label(__('Enable X (Twitter) Sign-In'))
                                            ->helperText(__('Shows a "Sign in with X" button. Set TWITTER_CLIENT_ID and TWITTER_CLIENT_SECRET in your .env file first. Uses OAuth 2.0.'))
                                            ->onColor('success')
                                            ->columnSpanFull(),

                                        Toggle::make('facebook_login_enabled')
                                            ->label(__('Enable Facebook Sign-In'))
                                            ->helperText(__('Shows a "Sign in with Facebook" button. Set FACEBOOK_CLIENT_ID and FACEBOOK_CLIENT_SECRET in your .env file first.'))
                                            ->onColor('success')
                                            ->columnSpanFull(),

                                        Toggle::make('apple_login_enabled')
                                            ->label(__('Enable Apple Sign-In'))
                                            ->helperText(__('Shows a "Sign in with Apple" button. Configure APPLE_CLIENT_ID, APPLE_KEY_ID, APPLE_TEAM_ID, APPLE_PRIVATE_KEY in your .env file. See socialiteproviders/apple docs.'))
                                            ->onColor('success')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    protected function getFormContentComponent(): \Filament\Schemas\Components\Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('integrations-settings-form')
            ->live()
            ->livewireSubmitHandler('save')
            ->footer([
                SchemaActions::make($this->getFormActions())
                    ->alignment('end')
                    ->key('form-actions'),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save Settings'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        if (empty($data) || ! is_array($data)) {
            $data = $this->data;
        }

        $groupMap = [
            'google_login_enabled' => 'social',
            'twitter_login_enabled' => 'social',
            'facebook_login_enabled' => 'social',
            'apple_login_enabled' => 'social',
        ];
        $booleanKeys = [
            'google_login_enabled',
            'twitter_login_enabled',
            'facebook_login_enabled',
            'apple_login_enabled',
        ];

        $service = app(SettingsPersistService::class);
        $service->persist(
            $data,
            $groupMap,
            $booleanKeys,
            [],
            [],
            [],
            [],
            []
        );

        $this->data = array_merge($this->data, $data);

        Notification::make()
            ->title(__('Settings saved'))
            ->success()
            ->send();
    }
}
