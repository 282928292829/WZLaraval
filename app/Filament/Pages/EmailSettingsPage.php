<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\SettingsPersistService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailSettingsPage extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Email');
    }

    public function getTitle(): string
    {
        return __('Email Settings');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    /** @var list<string> Email setting keys to load */
    protected const EMAIL_KEYS = [
        'email_enabled', 'email_from_name', 'email_from_address',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
        'email_registration', 'email_welcome', 'email_password_reset', 'email_comment_notification',
        'email_registration_subject_ar', 'email_registration_subject_en',
        'email_registration_body_ar', 'email_registration_body_en',
        'email_welcome_subject_ar', 'email_welcome_subject_en',
        'email_welcome_body_ar', 'email_welcome_body_en',
        'email_password_reset_subject_ar', 'email_password_reset_subject_en',
        'email_password_reset_body_ar', 'email_password_reset_body_en',
        'email_comment_notification_subject_ar', 'email_comment_notification_subject_en',
        'email_comment_notification_body_ar', 'email_comment_notification_body_en',
        'email_order_confirmation_subject_ar', 'email_order_confirmation_subject_en',
        'email_order_confirmation_body_ar', 'email_order_confirmation_body_en',
        'email_status_change_subject_ar', 'email_status_change_subject_en',
        'email_status_change_body_ar', 'email_status_change_body_en',
    ];

    public function mount(): void
    {
        $data = $this->defaults();
        $allSettings = Setting::all()->keyBy('key');

        foreach (static::EMAIL_KEYS as $key) {
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
            'email_enabled' => '0',
            'email_from_name' => config('app.name'),
            'email_from_address' => '',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'email_registration' => '0',
            'email_welcome' => '0',
            'email_password_reset' => '1',
            'email_comment_notification' => '0',
            'email_registration_subject_ar' => '',
            'email_registration_subject_en' => '',
            'email_registration_body_ar' => '',
            'email_registration_body_en' => '',
            'email_welcome_subject_ar' => '',
            'email_welcome_subject_en' => '',
            'email_welcome_body_ar' => '',
            'email_welcome_body_en' => '',
            'email_password_reset_subject_ar' => '',
            'email_password_reset_subject_en' => '',
            'email_password_reset_body_ar' => '',
            'email_password_reset_body_en' => '',
            'email_comment_notification_subject_ar' => '',
            'email_comment_notification_subject_en' => '',
            'email_comment_notification_body_ar' => '',
            'email_comment_notification_body_en' => '',
            'email_order_confirmation_subject_ar' => '',
            'email_order_confirmation_subject_en' => '',
            'email_order_confirmation_body_ar' => '',
            'email_order_confirmation_body_en' => '',
            'email_status_change_subject_ar' => '',
            'email_status_change_subject_en' => '',
            'email_status_change_body_ar' => '',
            'email_status_change_body_en' => '',
        ];
    }

    /** Build email template form section for one type. */
    protected function emailTemplateSection(string $type, string $label, string $placeholderHelp): \Filament\Schemas\Components\Section
    {
        return Section::make($label)
            ->schema([
                TextInput::make("email_{$type}_subject_ar")
                    ->label(__('Subject').' — '.__('Arabic'))
                    ->maxLength(200)
                    ->placeholder(__('Leave empty to use default')),
                TextInput::make("email_{$type}_subject_en")
                    ->label(__('Subject').' — '.__('English'))
                    ->maxLength(200)
                    ->placeholder(__('Leave empty to use default')),
                Textarea::make("email_{$type}_body_ar")
                    ->label(__('Body').' — '.__('Arabic'))
                    ->rows(6)
                    ->placeholder(__('Leave empty to use default'))
                    ->helperText($placeholderHelp)
                    ->columnSpanFull(),
                Textarea::make("email_{$type}_body_en")
                    ->label(__('Body').' — '.__('English'))
                    ->rows(6)
                    ->placeholder(__('Leave empty to use default'))
                    ->helperText($placeholderHelp)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible()
            ->collapsed(true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('email-settings-tabs')
                    ->tabs([
                        Tab::make(__('SMTP'))
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema([
                                Section::make(__('Email / SMTP'))
                                    ->icon(Heroicon::OutlinedEnvelope)
                                    ->description(__('Leave disabled until SMTP is configured. Use the Test button to verify.'))
                                    ->schema([
                                        SchemaActions::make([
                                            Action::make('sendTestEmail')
                                                ->label(__('Send Test Email'))
                                                ->icon(Heroicon::OutlinedPaperAirplane)
                                                ->color('info')
                                                ->action(function (): void {
                                                    $data = $this->form->getState();
                                                    $host = trim((string) ($data['smtp_host'] ?? ''));
                                                    $port = (int) ($data['smtp_port'] ?? 587);
                                                    $username = trim((string) ($data['smtp_username'] ?? ''));
                                                    $password = (string) ($data['smtp_password'] ?? '');
                                                    $encryption = trim((string) ($data['smtp_encryption'] ?? 'tls'));
                                                    $fromName = trim((string) ($data['email_from_name'] ?? config('app.name')));
                                                    $fromAddress = trim((string) ($data['email_from_address'] ?? ''));

                                                    if ($host === '') {
                                                        Notification::make()
                                                            ->title(__('settings.test_email_configure_first'))
                                                            ->warning()
                                                            ->send();

                                                        return;
                                                    }

                                                    $recipient = auth()->user()?->email;
                                                    if (! $recipient) {
                                                        Notification::make()
                                                            ->title(__('settings.test_email_no_recipient'))
                                                            ->warning()
                                                            ->send();

                                                        return;
                                                    }

                                                    $originalHost = config('mail.mailers.smtp.host');
                                                    $originalPort = config('mail.mailers.smtp.port');
                                                    $originalUsername = config('mail.mailers.smtp.username');
                                                    $originalPassword = config('mail.mailers.smtp.password');
                                                    $originalEncryption = config('mail.mailers.smtp.encryption');
                                                    $originalFrom = config('mail.from');

                                                    try {
                                                        Config::set('mail.mailers.smtp.host', $host);
                                                        Config::set('mail.mailers.smtp.port', $port);
                                                        Config::set('mail.mailers.smtp.username', $username ?: null);
                                                        Config::set('mail.mailers.smtp.password', $password ?: null);
                                                        Config::set('mail.mailers.smtp.encryption', $encryption !== '' ? $encryption : null);
                                                        Config::set('mail.from', [
                                                            'address' => $fromAddress ?: 'noreply@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'example.com'),
                                                            'name' => $fromName ?: config('app.name'),
                                                        ]);

                                                        Mail::mailer('smtp')
                                                            ->raw(__('settings.test_email_body'), function ($message) use ($recipient): void {
                                                                $message->to($recipient)
                                                                    ->subject(__('settings.test_email_subject'));
                                                            });

                                                        Notification::make()
                                                            ->title(__('settings.test_email_sent', ['email' => $recipient]))
                                                            ->success()
                                                            ->send();
                                                    } catch (\Throwable $e) {
                                                        Notification::make()
                                                            ->title(__('settings.test_email_failed'))
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    } finally {
                                                        Config::set('mail.mailers.smtp.host', $originalHost);
                                                        Config::set('mail.mailers.smtp.port', $originalPort);
                                                        Config::set('mail.mailers.smtp.username', $originalUsername);
                                                        Config::set('mail.mailers.smtp.password', $originalPassword);
                                                        Config::set('mail.mailers.smtp.encryption', $originalEncryption);
                                                        Config::set('mail.from', $originalFrom);
                                                    }
                                                }),
                                        ]),

                                        Toggle::make('email_enabled')
                                            ->label(__('Enable Email Sending'))
                                            ->onColor('success')
                                            ->columnSpanFull(),

                                        TextInput::make('email_from_name')
                                            ->label(__('From Name')),

                                        TextInput::make('email_from_address')
                                            ->label(__('From Address'))
                                            ->email()
                                            ->nullable(),

                                        TextInput::make('smtp_host')
                                            ->label(__('SMTP Host')),

                                        TextInput::make('smtp_port')
                                            ->label(__('SMTP Port'))
                                            ->numeric(),

                                        TextInput::make('smtp_username')
                                            ->label(__('SMTP Username')),

                                        TextInput::make('smtp_password')
                                            ->label(__('SMTP Password'))
                                            ->password()
                                            ->revealable(),

                                        Select::make('smtp_encryption')
                                            ->label(__('Encryption'))
                                            ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => __('None')]),
                                    ])
                                    ->columns(3)
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Email Type Toggles'))
                            ->icon(Heroicon::OutlinedBell)
                            ->schema([
                                Section::make(__('Email Type Toggles'))
                                    ->icon(Heroicon::OutlinedBell)
                                    ->description(__('Enable or disable each email type independently.'))
                                    ->schema([
                                        Toggle::make('email_registration')
                                            ->label(__('Registration Confirmation')),

                                        Toggle::make('email_welcome')
                                            ->label(__('Welcome Email')),

                                        Toggle::make('email_password_reset')
                                            ->label(__('Password Reset')),

                                        Toggle::make('email_comment_notification')
                                            ->label(__('Comment Notifications (opt-in)')),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Email Templates'))
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Section::make(__('Email Templates'))
                                    ->icon(Heroicon::OutlinedDocumentText)
                                    ->description(__('settings.email_templates_desc'))
                                    ->schema([
                                        $this->emailTemplateSection('registration', __('Registration Confirmation'), __('settings.email_placeholders_registration')),
                                        $this->emailTemplateSection('welcome', __('Welcome Email'), __('settings.email_placeholders_welcome')),
                                        $this->emailTemplateSection('password_reset', __('Password Reset'), __('settings.email_placeholders_password_reset')),
                                        $this->emailTemplateSection('comment_notification', __('Comment Notifications'), __('settings.email_placeholders_comment')),
                                        $this->emailTemplateSection('order_confirmation', __('Order Confirmation'), __('settings.email_placeholders_order_confirmation')),
                                        $this->emailTemplateSection('status_change', __('Status Change'), __('settings.email_placeholders_status_change')),
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
            ->id('email-settings-form')
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

        $groupMap = [];
        foreach (static::EMAIL_KEYS as $key) {
            $groupMap[$key] = 'email';
        }

        $booleanKeys = [
            'email_enabled', 'email_registration', 'email_welcome',
            'email_password_reset', 'email_comment_notification',
        ];

        $integerKeys = ['smtp_port'];

        $service = app(SettingsPersistService::class);
        $service->persist(
            $data,
            $groupMap,
            $booleanKeys,
            $integerKeys,
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
