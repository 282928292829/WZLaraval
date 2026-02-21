<?php

namespace App\Filament\Pages;

use App\Console\Commands\FetchExchangeRates;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\Facades\Artisan;

class SettingsPage extends Page
{
    protected string $view = 'filament.pages.settings-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public function getTitle(): string
    {
        return __('Site Settings');
    }

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

        // Inflate flat exchange-rate config from the stored JSON blob
        $er = $data['exchange_rates'] ?? [];
        if (is_array($er)) {
            if (isset($er['markup_percent'])) {
                $data['exchange_rates_markup_percent'] = (string) $er['markup_percent'];
            }
            if (isset($er['auto_fetch_enabled'])) {
                $data['exchange_rates_auto_fetch'] = (bool) $er['auto_fetch_enabled'];
            }
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
            'order_edit_window_minutes'  => '10',
            'order_new_layout'           => '1',
            'orders_per_hour_customer'   => '10',
            'orders_per_hour_admin'      => '50',
            'max_file_size_mb'           => '2',
            'max_orders_per_day'         => '5',

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

            // Social login
            'google_login_enabled' => '0',

            // Scripts
            'header_scripts' => '',
            'footer_scripts' => '',

            // Order form fields (JSON — managed by seeder, edited here)
            'order_form_fields' => [],

            // Exchange rates config (flat keys — synced to/from exchange_rates JSON blob)
            'exchange_rates_markup_percent' => '3',
            'exchange_rates_auto_fetch'     => true,
            // Per-currency manual overrides (empty string = use auto formula)
            'exrate_override_USD' => '',
            'exrate_override_EUR' => '',
            'exrate_override_GBP' => '',
            'exrate_override_CNY' => '',
            'exrate_override_JPY' => '',
            'exrate_override_KRW' => '',
            'exrate_override_TRY' => '',
            'exrate_override_AED' => '',

            // Commission rules
            'commission_threshold_sar' => '500',
            'commission_rate_above'    => '8',
            'commission_flat_below'    => '50',

            // Quick-action button toggles (shown to staff on order detail)
            'qa_mark_paid'    => true,
            'qa_mark_shipped' => true,
            'qa_request_info' => true,
            'qa_cancel_order' => true,

            // UI / behaviour
            'url_validation_strict' => true,

            // Shipping rates (SAR)
            'aramex_first_half_kg'   => '119',
            'aramex_rest_half_kg'    => '39',
            'aramex_over21_per_kg'   => '59',
            'aramex_delivery_days'   => '7-10',
            'dhl_first_half_kg'      => '169',
            'dhl_rest_half_kg'       => '43',
            'dhl_over21_per_kg'      => '63',
            'dhl_delivery_days'      => '7-10',
            'domestic_first_half_kg' => '69',
            'domestic_rest_half_kg'  => '19',
            'domestic_delivery_days' => '4-7',
        ];
    }

    /** Build a short status string for the exchange-rates fetch info placeholder */
    protected function buildFetchStatusString(): string
    {
        $er = $this->data['exchange_rates'] ?? null;
        if (! is_array($er) || empty($er['last_fetch_time'])) {
            return __('Never fetched. Click "Fetch Now".');
        }
        $icon = ($er['last_fetch_status'] ?? '') === 'success' ? '✅' : '⚠️';

        return "{$icon} {$er['last_fetch_time']}";
    }

    /** Build helper text showing current market / final rates for a currency */
    protected function rateInfo(string $currency): string
    {
        $er    = $this->data['exchange_rates'] ?? null;
        $rates = is_array($er) ? ($er['rates'] ?? []) : [];
        $rate  = $rates[$currency] ?? null;

        if (! $rate || empty($rate['market'])) {
            return __('No data yet — run "Fetch Now".');
        }

        $market = number_format((float) $rate['market'], 4);
        $final  = number_format((float) $rate['final'],  4);

        return __('Market: :m SAR | Final: :f SAR', ['m' => $market, 'f' => $final]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── General ──────────────────────────────────────────────────
                Section::make(__('General'))
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema([
                        TextInput::make('data.site_name')
                            ->label(__('Site Name'))
                            ->required(),

                        Select::make('data.default_language')
                            ->label(__('Default Language'))
                            ->options(['ar' => __('Arabic'), 'en' => __('English')])
                            ->required(),

                        Select::make('data.default_currency')
                            ->label(__('Default Currency'))
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

                // ── Appearance ───────────────────────────────────────────────
                Section::make(__('Appearance'))
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->schema([
                        TextInput::make('data.primary_color')
                            ->label(__('Primary Color (hex)'))
                            ->placeholder('#f97316')
                            ->maxLength(20),

                        TextInput::make('data.font_family')
                            ->label(__('Font Family'))
                            ->placeholder(__('IBM Plex Sans Arabic')),

                        TextInput::make('data.logo_text')
                            ->label(__('Logo Text'))
                            ->helperText(__('Shown when no logo image is uploaded.')),
                    ])
                    ->columns(3),

                // ── Order Rules ───────────────────────────────────────────────
                Section::make(__('Order Rules'))
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
                        TextInput::make('data.max_products_per_order')
                            ->label(__('Max Products per Order'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500),

                        TextInput::make('data.order_edit_window_minutes')
                            ->label(__('Edit Window (minutes)'))
                            ->numeric()
                            ->helperText(__('How long customers can edit after placing.')),

                        TextInput::make('data.max_orders_per_day')
                            ->label(__('Max Orders per Day (per user)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText(__('Limit how many orders one user can place per day.')),

                        Select::make('data.order_new_layout')
                            ->label(__('New-Order Form Layout'))
                            ->options([
                                '1' => __('Option 1 — Responsive (default)'),
                                '2' => __('Option 2 — Cart system'),
                                '3' => __('Option 3 — Cards everywhere'),
                            ]),

                        TextInput::make('data.orders_per_hour_customer')
                            ->label(__('Orders/hour — Customer'))
                            ->numeric(),

                        TextInput::make('data.orders_per_hour_admin')
                            ->label(__('Orders/hour — Admin'))
                            ->numeric(),

                        TextInput::make('data.max_file_size_mb')
                            ->label(__('Max File Size (MB)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText(__('Maximum size per uploaded file. Default: 2 MB.')),

                        Toggle::make('data.url_validation_strict')
                            ->label(__('Strict URL Validation'))
                            ->onColor('warning')
                            ->helperText(__('When ON, only exact Amazon/product URLs are accepted. When OFF, any URL is allowed.')),
                    ])
                    ->columns(3),

                // ── Order Form Fields ─────────────────────────────────────────
                Section::make(__('Order Form Fields'))
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->description(__('Control which fields appear in the new-order form, their order, and which are collapsed under "show more" on mobile.'))
                    ->schema([
                        Repeater::make('data.order_form_fields')
                            ->label('')
                            ->schema([
                                TextInput::make('label_en')
                                    ->label(__('Field'))
                                    ->disabled()
                                    ->dehydrated(true),

                                TextInput::make('sort_order')
                                    ->label(__('Order'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(99)
                                    ->required()
                                    ->helperText(__('Lower = appears first')),

                                Toggle::make('optional')
                                    ->label(__('In "show more" section'))
                                    ->helperText(__('Collapsed on mobile by default')),

                                Toggle::make('enabled')
                                    ->label(__('Enabled'))
                                    ->helperText(__('Uncheck to hide field entirely')),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => $state['label_en'] ?? null),
                    ])
                    ->collapsible(),

                // ── Shipping Rates ────────────────────────────────────────────
                Section::make(__('Shipping Rates'))
                    ->icon(Heroicon::OutlinedTruck)
                    ->description(__('SAR prices used by the public shipping calculator. Changes take effect immediately.'))
                    ->schema([
                        // ── Aramex ──────────────────────────────────────
                        \Filament\Schemas\Components\Section::make('Aramex — ' . __('Economy Shipping'))
                            ->schema([
                                TextInput::make('data.aramex_first_half_kg')
                                    ->label(__('First 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.aramex_rest_half_kg')
                                    ->label(__('Each Additional 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.aramex_over21_per_kg')
                                    ->label(__('Over 21 kg — per kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.aramex_delivery_days')
                                    ->label(__('Est. Delivery'))
                                    ->placeholder('7-10')
                                    ->helperText(__('Shown on calculator, e.g. "7-10 days"')),
                            ])
                            ->columns(4),

                        // ── DHL ──────────────────────────────────────────
                        \Filament\Schemas\Components\Section::make('DHL — ' . __('Express Shipping'))
                            ->schema([
                                TextInput::make('data.dhl_first_half_kg')
                                    ->label(__('First 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.dhl_rest_half_kg')
                                    ->label(__('Each Additional 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.dhl_over21_per_kg')
                                    ->label(__('Over 21 kg — per kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.dhl_delivery_days')
                                    ->label(__('Est. Delivery'))
                                    ->placeholder('7-10'),
                            ])
                            ->columns(4),

                        // ── US Domestic ───────────────────────────────────
                        \Filament\Schemas\Components\Section::make(__('US Domestic Shipping'))
                            ->schema([
                                TextInput::make('data.domestic_first_half_kg')
                                    ->label(__('First 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.domestic_rest_half_kg')
                                    ->label(__('Each Additional 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('data.domestic_delivery_days')
                                    ->label(__('Est. Delivery'))
                                    ->placeholder('4-7'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(),

                // ── Exchange Rates ────────────────────────────────────────────
                Section::make(__('Exchange Rates'))
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->description(__('Rates are fetched from open.er-api.com (free, no key needed). Manual override per currency takes priority over the auto-calculated rate.'))
                    ->schema([
                        TextInput::make('data.exchange_rates_markup_percent')
                            ->label(__('Markup %'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(50)
                            ->helperText(__('Added on top of the market rate. Default: 3%')),

                        Toggle::make('data.exchange_rates_auto_fetch')
                            ->label(__('Auto-Fetch Daily'))
                            ->onColor('success')
                            ->helperText(__('Fetch rates automatically each day via Laravel scheduler (php artisan schedule:run).')),

                        Placeholder::make('exchange_rates_fetch_status')
                            ->label(__('Last Fetch'))
                            ->content(fn () => $this->buildFetchStatusString()),

                        // Per-currency manual override inputs
                        TextInput::make('data.exrate_override_USD')
                            ->label('USD — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('USD')),

                        TextInput::make('data.exrate_override_EUR')
                            ->label('EUR — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('EUR')),

                        TextInput::make('data.exrate_override_GBP')
                            ->label('GBP — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('GBP')),

                        TextInput::make('data.exrate_override_CNY')
                            ->label('CNY — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('CNY')),

                        TextInput::make('data.exrate_override_JPY')
                            ->label('JPY — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('JPY')),

                        TextInput::make('data.exrate_override_KRW')
                            ->label('KRW — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('KRW')),

                        TextInput::make('data.exrate_override_TRY')
                            ->label('TRY — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('TRY')),

                        TextInput::make('data.exrate_override_AED')
                            ->label('AED — ' . __('Manual Rate (SAR)'))
                            ->numeric()
                            ->placeholder(__('Auto'))
                            ->helperText(fn () => $this->rateInfo('AED')),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // ── Commission Rules ──────────────────────────────────────────
                Section::make(__('Commission Rules'))
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->description(__('Controls how service commission is calculated on orders. Used by the order form and the public calculator.'))
                    ->schema([
                        TextInput::make('data.commission_threshold_sar')
                            ->label(__('Threshold (SAR)'))
                            ->numeric()
                            ->suffix('SAR')
                            ->minValue(0)
                            ->helperText(__('Order value above this threshold → percentage commission. Below → flat fee.')),

                        TextInput::make('data.commission_rate_above')
                            ->label(__('% Above Threshold'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText(__('Commission percentage when order ≥ threshold. Default: 8%')),

                        TextInput::make('data.commission_flat_below')
                            ->label(__('Flat Fee Below Threshold (SAR)'))
                            ->numeric()
                            ->suffix('SAR')
                            ->minValue(0)
                            ->helperText(__('Fixed commission when order < threshold. Default: 50 SAR')),
                    ])
                    ->columns(3),

                // ── Quick Action Toggles ──────────────────────────────────────
                Section::make(__('Quick Action Toggles'))
                    ->icon(Heroicon::OutlinedBolt)
                    ->description(__('Enable or disable each quick-action button shown to staff on the order detail page.'))
                    ->schema([
                        Toggle::make('data.qa_mark_paid')
                            ->label(__('Mark as Paid'))
                            ->onColor('success'),

                        Toggle::make('data.qa_mark_shipped')
                            ->label(__('Mark as Shipped'))
                            ->onColor('success'),

                        Toggle::make('data.qa_request_info')
                            ->label(__('Request More Info'))
                            ->onColor('success'),

                        Toggle::make('data.qa_cancel_order')
                            ->label(__('Cancel Order'))
                            ->onColor('danger'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // ── Email / SMTP ──────────────────────────────────────────────
                Section::make(__('Email / SMTP'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->description(__('Leave disabled until SMTP is configured. Use the Test button to verify.'))
                    ->schema([
                        Toggle::make('data.email_enabled')
                            ->label(__('Enable Email Sending'))
                            ->onColor('success')
                            ->columnSpanFull(),

                        TextInput::make('data.email_from_name')
                            ->label(__('From Name')),

                        TextInput::make('data.email_from_address')
                            ->label(__('From Address'))
                            ->email(),

                        TextInput::make('data.smtp_host')
                            ->label(__('SMTP Host')),

                        TextInput::make('data.smtp_port')
                            ->label(__('SMTP Port'))
                            ->numeric(),

                        TextInput::make('data.smtp_username')
                            ->label(__('SMTP Username')),

                        TextInput::make('data.smtp_password')
                            ->label(__('SMTP Password'))
                            ->password()
                            ->revealable(),

                        Select::make('data.smtp_encryption')
                            ->label(__('Encryption'))
                            ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => __('None')]),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // ── Email Type Toggles ────────────────────────────────────────
                Section::make(__('Email Type Toggles'))
                    ->icon(Heroicon::OutlinedBell)
                    ->description(__('Enable or disable each email type independently.'))
                    ->schema([
                        Toggle::make('data.email_registration')
                            ->label(__('Registration Confirmation')),

                        Toggle::make('data.email_welcome')
                            ->label(__('Welcome Email')),

                        Toggle::make('data.email_password_reset')
                            ->label(__('Password Reset')),

                        Toggle::make('data.email_comment_notification')
                            ->label(__('Comment Notifications (opt-in)')),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // ── Social Login ──────────────────────────────────────────────
                Section::make(__('Social Login'))
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->description(__('Allow users to sign in/up using third-party accounts. Requires OAuth credentials in .env.'))
                    ->schema([
                        Toggle::make('data.google_login_enabled')
                            ->label(__('Enable Google Sign-In'))
                            ->helperText(__('Shows a "Sign in with Google" button on the login and register pages. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file first.'))
                            ->onColor('success')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // ── Custom Scripts ────────────────────────────────────────────
                Section::make(__('Custom Scripts'))
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->description(__('Injected into every page. Use for analytics, chat widgets, etc.'))
                    ->schema([
                        Textarea::make('data.header_scripts')
                            ->label(__('Header Scripts (before </head>)'))
                            ->rows(4),

                        Textarea::make('data.footer_scripts')
                            ->label(__('Footer Scripts (before </body>)'))
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
                ->label(__('Save Settings'))
                ->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchNow')
                ->label(__('Fetch Rates Now'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('Fetch Exchange Rates'))
                ->modalDescription(__('This will call open.er-api.com and update the stored rates. Manual overrides will be preserved.'))
                ->modalSubmitActionLabel(__('Fetch'))
                ->action(function () {
                    $exitCode = Artisan::call('rates:fetch');

                    if ($exitCode === 0) {
                        Notification::make()
                            ->title(__('Rates updated successfully'))
                            ->success()
                            ->send();
                        // Reload stored data so the page reflects the fresh rates
                        $this->mount();
                    } else {
                        Notification::make()
                            ->title(__('Fetch failed — check API connection or logs'))
                            ->danger()
                            ->send();
                    }
                }),
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
            'max_file_size_mb'           => 'orders',
            'max_orders_per_day'         => 'orders',
            'url_validation_strict'      => 'orders',
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
            'google_login_enabled'       => 'social',
            'header_scripts'             => 'scripts',
            'footer_scripts'             => 'scripts',
            // Exchange rates flat keys
            'exchange_rates_markup_percent' => 'exchange_rates',
            'exchange_rates_auto_fetch'     => 'exchange_rates',
            'exrate_override_USD'           => 'exchange_rates',
            'exrate_override_EUR'           => 'exchange_rates',
            'exrate_override_GBP'           => 'exchange_rates',
            'exrate_override_CNY'           => 'exchange_rates',
            'exrate_override_JPY'           => 'exchange_rates',
            'exrate_override_KRW'           => 'exchange_rates',
            'exrate_override_TRY'           => 'exchange_rates',
            'exrate_override_AED'           => 'exchange_rates',
            // Commission
            'commission_threshold_sar' => 'commission',
            'commission_rate_above'    => 'commission',
            'commission_flat_below'    => 'commission',
            // Quick actions
            'qa_mark_paid'    => 'quick_actions',
            'qa_mark_shipped' => 'quick_actions',
            'qa_request_info' => 'quick_actions',
            'qa_cancel_order' => 'quick_actions',
            // Shipping rates
            'aramex_first_half_kg'   => 'shipping',
            'aramex_rest_half_kg'    => 'shipping',
            'aramex_over21_per_kg'   => 'shipping',
            'aramex_delivery_days'   => 'shipping',
            'dhl_first_half_kg'      => 'shipping',
            'dhl_rest_half_kg'       => 'shipping',
            'dhl_over21_per_kg'      => 'shipping',
            'dhl_delivery_days'      => 'shipping',
            'domestic_first_half_kg' => 'shipping',
            'domestic_rest_half_kg'  => 'shipping',
            'domestic_delivery_days' => 'shipping',
        ];

        $booleanKeys = [
            'email_enabled', 'email_registration', 'email_welcome',
            'email_password_reset', 'email_comment_notification',
            'google_login_enabled',
            'exchange_rates_auto_fetch',
            'qa_mark_paid', 'qa_mark_shipped', 'qa_request_info', 'qa_cancel_order',
            'url_validation_strict',
        ];

        $integerKeys = [
            'smtp_port', 'max_products_per_order', 'order_edit_window_minutes',
            'orders_per_hour_customer', 'orders_per_hour_admin',
            'max_file_size_mb', 'max_orders_per_day',
            'aramex_first_half_kg', 'aramex_rest_half_kg', 'aramex_over21_per_kg',
            'dhl_first_half_kg', 'dhl_rest_half_kg', 'dhl_over21_per_kg',
            'domestic_first_half_kg', 'domestic_rest_half_kg',
        ];

        $floatKeys = [
            'exchange_rates_markup_percent',
            'commission_threshold_sar', 'commission_rate_above', 'commission_flat_below',
            'exrate_override_USD', 'exrate_override_EUR', 'exrate_override_GBP',
            'exrate_override_CNY', 'exrate_override_JPY', 'exrate_override_KRW',
            'exrate_override_TRY', 'exrate_override_AED',
        ];

        $jsonKeys = ['order_form_fields'];

        // Keys not saved individually (read-only or handled via syncExchangeRatesJson)
        $skipKeys = ['exchange_rates'];

        foreach ($data as $key => $value) {
            if (in_array($key, $skipKeys)) {
                continue;
            }

            $group = $groupMap[$key] ?? 'general';

            if (in_array($key, $jsonKeys) || (is_array($value) && ! in_array($key, $floatKeys))) {
                if (is_array($value)) {
                    usort($value, fn ($a, $b) => ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99));
                }
                $type = 'json';
            } elseif (is_bool($value) || in_array($key, $booleanKeys)) {
                $type = 'boolean';
            } elseif (in_array($key, $floatKeys)) {
                // Blank override = store empty string (means "use auto")
                $type = ($value === '' || $value === null) ? 'string' : 'string';
            } elseif (is_numeric($value) && ! str_contains((string) $value, '.') && in_array($key, $integerKeys)) {
                $type = 'integer';
            } else {
                $type = 'string';
            }

            Setting::set($key, $value ?? '', $type, $group);
        }

        // Rebuild the exchange_rates JSON blob to reflect updated markup + overrides
        $this->syncExchangeRatesJson($data);

        Notification::make()
            ->title(__('Settings saved'))
            ->success()
            ->send();
    }

    /**
     * Re-save the exchange_rates JSON blob after settings are updated.
     * Applies new markup % and manual overrides to the stored rates.
     */
    protected function syncExchangeRatesJson(array $data): void
    {
        $er = Setting::get('exchange_rates', []) ?: [];

        if (empty($er['rates'])) {
            $er['rates'] = FetchExchangeRates::DEFAULT_RATES;
        }

        $markup = (float) ($data['exchange_rates_markup_percent'] ?? 3);
        $er['markup_percent']    = $markup;
        $er['auto_fetch_enabled'] = (bool) ($data['exchange_rates_auto_fetch'] ?? true);

        foreach (FetchExchangeRates::CURRENCIES as $cur) {
            $override = $data["exrate_override_{$cur}"] ?? '';
            $manual   = ($override !== '' && $override !== null && is_numeric($override))
                ? (float) $override
                : null;

            $er['rates'][$cur]['manual'] = $manual;

            if ($manual !== null) {
                $er['rates'][$cur]['final'] = $manual;
            } else {
                // Reapply markup to existing market rate (if any)
                $market = (float) ($er['rates'][$cur]['market'] ?? 0);
                if ($market > 0) {
                    $er['rates'][$cur]['final'] = round($market * (1 + $markup / 100), 4);
                }
            }
        }

        Setting::set('exchange_rates', $er, 'json', 'exchange_rates');
    }
}
