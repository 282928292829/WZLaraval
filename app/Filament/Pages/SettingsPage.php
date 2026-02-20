<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SettingsPage extends Page
{
    protected string $view = 'filament.pages.settings-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Site Settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    public function mount(): void
    {
        $data = $this->defaults();

        foreach (Setting::all() as $setting) {
            $data[$setting->key] = $setting->type === 'json'
                ? json_decode($setting->value, true)
                : $setting->value;
        }

        $this->data = $data;
        $this->form->fill($this->data);
    }

    /** @return array<string,mixed> */
    protected function defaults(): array
    {
        return [
            // General
            'site_name'             => 'Wasetzon',
            'default_language'      => 'ar',
            'default_currency'      => 'USD',

            // Appearance
            'primary_color'         => '#f97316',
            'font_family'           => 'IBM Plex Sans Arabic',
            'logo_text'             => 'Wasetzon',

            // Order rules
            'max_products_per_order'     => '30',
            'order_edit_window_minutes'  => '30',
            'order_new_layout'           => '1',
            'orders_per_hour_customer'   => '10',
            'orders_per_hour_admin'      => '50',

            // Email (disabled by default, SMTP not configured)
            'email_enabled'               => '0',
            'email_from_name'             => 'Wasetzon',
            'email_from_address'          => '',
            'smtp_host'                   => '',
            'smtp_port'                   => '587',
            'smtp_username'               => '',
            'smtp_password'               => '',
            'smtp_encryption'             => 'tls',
            'email_registration'          => '0',
            'email_welcome'               => '0',
            'email_password_reset'        => '1',
            'email_comment_notification'  => '0',

            // Scripts
            'header_scripts' => '',
            'footer_scripts' => '',

            // Order form fields (JSON — managed by seeder, edited here)
            'order_form_fields' => [],
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema([
                        TextInput::make('data.site_name')
                            ->label('Site Name')
                            ->required(),

                        Select::make('data.default_language')
                            ->label('Default Language')
                            ->options(['ar' => 'Arabic', 'en' => 'English'])
                            ->required(),

                        Select::make('data.default_currency')
                            ->label('Default Currency')
                            ->options([
                                'USD' => 'USD — US Dollar ($)',
                                'EUR' => 'EUR — Euro (€)',
                                'GBP' => 'GBP — British Pound (£)',
                                'SAR' => 'SAR — Saudi Riyal (ر.س)',
                                'AED' => 'AED — UAE Dirham (د.إ)',
                                'KWD' => 'KWD — Kuwaiti Dinar (د.ك)',
                                'QAR' => 'QAR — Qatari Riyal (ر.ق)',
                                'BHD' => 'BHD — Bahraini Dinar (د.ب)',
                                'OMR' => 'OMR — Omani Rial (ر.ع.)',
                                'EGP' => 'EGP — Egyptian Pound (ج.م)',
                                'TRY' => 'TRY — Turkish Lira (₺)',
                                'CNY' => 'CNY — Chinese Yuan (¥)',
                            ])
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Appearance')
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->schema([
                        TextInput::make('data.primary_color')
                            ->label('Primary Color (hex)')
                            ->placeholder('#f97316')
                            ->maxLength(20),

                        TextInput::make('data.font_family')
                            ->label('Font Family')
                            ->placeholder('IBM Plex Sans Arabic'),

                        TextInput::make('data.logo_text')
                            ->label('Logo Text')
                            ->helperText('Shown when no logo image is uploaded.'),
                    ])
                    ->columns(3),

                Section::make('Order Rules')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
                        TextInput::make('data.max_products_per_order')
                            ->label('Max Products per Order')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500),

                        TextInput::make('data.order_edit_window_minutes')
                            ->label('Edit Window (minutes)')
                            ->numeric()
                            ->helperText('How long customers can edit after placing.'),

                        Select::make('data.order_new_layout')
                            ->label('New-Order Form Layout')
                            ->options([
                                '1' => 'Option 1 — Responsive (default)',
                                '2' => 'Option 2 — Cart system',
                                '3' => 'Option 3 — Cards everywhere',
                            ]),

                        TextInput::make('data.orders_per_hour_customer')
                            ->label('Orders/hour — Customer')
                            ->numeric(),

                        TextInput::make('data.orders_per_hour_admin')
                            ->label('Orders/hour — Admin')
                            ->numeric(),
                    ])
                    ->columns(3),

                Section::make('Order Form Fields')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->description('Control which fields appear in the new-order form, their order, and which are collapsed under "show more" on mobile.')
                    ->schema([
                        Repeater::make('data.order_form_fields')
                            ->label('')
                            ->schema([
                                TextInput::make('label_en')
                                    ->label('Field')
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(99)
                                    ->required()
                                    ->helperText('Lower = appears first'),

                                Toggle::make('optional')
                                    ->label('In "show more" section')
                                    ->helperText('Collapsed on mobile by default'),

                                Toggle::make('enabled')
                                    ->label('Enabled')
                                    ->helperText('Uncheck to hide field entirely'),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => $state['label_en'] ?? null),
                    ])
                    ->collapsible(),

                Section::make('Email / SMTP')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->description('Leave disabled until SMTP is configured. Use the Test button to verify.')
                    ->schema([
                        Toggle::make('data.email_enabled')
                            ->label('Enable Email Sending')
                            ->onColor('success')
                            ->columnSpanFull(),

                        TextInput::make('data.email_from_name')
                            ->label('From Name'),

                        TextInput::make('data.email_from_address')
                            ->label('From Address')
                            ->email(),

                        TextInput::make('data.smtp_host')
                            ->label('SMTP Host'),

                        TextInput::make('data.smtp_port')
                            ->label('SMTP Port')
                            ->numeric(),

                        TextInput::make('data.smtp_username')
                            ->label('SMTP Username'),

                        TextInput::make('data.smtp_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable(),

                        Select::make('data.smtp_encryption')
                            ->label('Encryption')
                            ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None']),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Email Type Toggles')
                    ->icon(Heroicon::OutlinedBell)
                    ->description('Enable or disable each email type independently.')
                    ->schema([
                        Toggle::make('data.email_registration')
                            ->label('Registration Confirmation'),

                        Toggle::make('data.email_welcome')
                            ->label('Welcome Email'),

                        Toggle::make('data.email_password_reset')
                            ->label('Password Reset'),

                        Toggle::make('data.email_comment_notification')
                            ->label('Comment Notifications (opt-in)'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Custom Scripts')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->description('Injected into every page. Use for analytics, chat widgets, etc.')
                    ->schema([
                        Textarea::make('data.header_scripts')
                            ->label('Header Scripts (before </head>)')
                            ->rows(4),

                        Textarea::make('data.footer_scripts')
                            ->label('Footer Scripts (before </body>)')
                            ->rows(4),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $groupMap = [
            'site_name'                  => 'general',
            'default_language'           => 'general',
            'default_currency'           => 'general',
            'primary_color'              => 'appearance',
            'font_family'                => 'appearance',
            'logo_text'                  => 'appearance',
            'max_products_per_order'     => 'orders',
            'order_edit_window_minutes'  => 'orders',
            'order_new_layout'           => 'orders',
            'orders_per_hour_customer'   => 'orders',
            'orders_per_hour_admin'      => 'orders',
            'order_form_fields'          => 'orders',
            'email_enabled'              => 'email',
            'email_from_name'            => 'email',
            'email_from_address'         => 'email',
            'smtp_host'                  => 'email',
            'smtp_port'                  => 'email',
            'smtp_username'              => 'email',
            'smtp_password'              => 'email',
            'smtp_encryption'            => 'email',
            'email_registration'         => 'email',
            'email_welcome'              => 'email',
            'email_password_reset'       => 'email',
            'email_comment_notification' => 'email',
            'header_scripts'             => 'scripts',
            'footer_scripts'             => 'scripts',
        ];

        $booleanKeys = ['email_enabled', 'email_registration', 'email_welcome', 'email_password_reset', 'email_comment_notification'];
        $integerKeys = ['smtp_port', 'max_products_per_order', 'order_edit_window_minutes', 'orders_per_hour_customer', 'orders_per_hour_admin'];
        $jsonKeys    = ['order_form_fields'];

        foreach ($data as $key => $value) {
            $group = $groupMap[$key] ?? 'general';

            if (in_array($key, $jsonKeys) || is_array($value)) {
                // Re-sort by sort_order before saving
                if (is_array($value)) {
                    usort($value, fn($a, $b) => ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99));
                }
                $type = 'json';
            } elseif (is_bool($value) || in_array($key, $booleanKeys)) {
                $type = 'boolean';
            } elseif (is_numeric($value) && ! str_contains((string) $value, '.') && in_array($key, $integerKeys)) {
                $type = 'integer';
            } else {
                $type = 'string';
            }

            Setting::set($key, $value, $type, $group);
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
