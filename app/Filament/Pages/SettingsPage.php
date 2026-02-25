<?php

namespace App\Filament\Pages;

use App\Console\Commands\FetchExchangeRates;
use App\Models\Currency;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class SettingsPage extends Page
{
    use InteractsWithFormActions;

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

        // Load currencies from DB (for Exchange Rates repeater)
        $data['currencies'] = Currency::ordered()->get()->map(fn (Currency $c): array => [
            'id' => $c->id,
            'code' => $c->code,
            'label' => $c->label ?? '',
            'manual_rate' => $c->manual_rate !== null ? (string) $c->manual_rate : '',
            'auto_fetch' => $c->auto_fetch,
            'markup_percent' => $c->markup_percent !== null ? (string) $c->markup_percent : '',
        ])->values()->toArray();

        $this->data = $data;
        $this->form->fill($this->data);
    }

    /** @return array<string,mixed> */
    protected function defaults(): array
    {
        return [
            // General
            'site_name' => 'Wasetzon',
            'default_language' => 'ar',
            'default_currency' => 'USD',

            // Contact (footer, hero WhatsApp)
            'whatsapp' => '',
            'contact_email' => '',
            'commercial_registration' => '',

            // Appearance
            'primary_color' => '#f97316',
            'font_family' => 'IBM Plex Sans Arabic',
            'logo_use_per_language' => false,
            'logo_image' => '',
            'logo_image_ar' => '',
            'logo_image_en' => '',
            'logo_text' => 'Wasetzon',
            'logo_text_ar' => '',
            'logo_text_en' => '',
            'logo_alt' => '',
            'logo_alt_ar' => '',
            'logo_alt_en' => '',

            // Order rules
            'max_products_per_order' => '30',
            'order_edit_enabled' => true,
            'order_edit_click_window_minutes' => '10',
            'order_edit_resubmit_window_minutes' => '10',
            'order_edit_window_minutes' => '10',
            'order_new_layout' => '1',
            'orders_per_hour_customer' => '50',
            'orders_per_hour_admin' => '50',
            'max_file_size_mb' => '2',
            'max_orders_per_day' => '200',
            'comment_max_files' => '5',
            'comment_max_file_size_mb' => '10',

            // Email (disabled by default, SMTP not configured)
            'email_enabled' => '0',
            'email_from_name' => 'Wasetzon',
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

            // Social login
            'google_login_enabled' => '0',

            // Scripts
            'header_scripts' => '',
            'footer_scripts' => '',

            // Order form fields (JSON — managed by seeder, edited here)
            'order_form_fields' => [],

            // Exchange rates (global; per-currency in Currency model)
            'exchange_rates_markup_percent' => '3',
            'exchange_rates_auto_fetch' => true,

            // Commission rules
            'commission_threshold_sar' => '500',
            'commission_rate_above' => '8',
            'commission_flat_below' => '50',

            // Customer quick-action toggles
            'qa_customer_section' => true,
            'qa_payment_notify' => true,
            'qa_shipping_address_btn' => true,
            'qa_similar_order' => true,
            'qa_customer_merge' => true,
            'qa_customer_cancel' => true,

            // Staff quick-action toggles
            'qa_team_section' => true,
            'qa_transfer_order' => true,
            'qa_payment_tracking' => true,
            'qa_shipping_tracking' => true,
            'qa_team_merge' => true,

            // Legacy staff quick-action button toggles
            'qa_mark_paid' => true,
            'qa_mark_shipped' => true,
            'qa_request_info' => true,
            'qa_cancel_order' => true,

            // Blog
            'blog_comments_enabled' => true,

            // SEO (site-wide defaults)
            'seo_default_og_image' => '',
            'seo_default_meta_description' => '',
            'seo_twitter_handle' => '',
            'seo_google_verification' => '',
            'seo_bing_verification' => '',

            // Shipping rates (SAR)
            'aramex_first_half_kg' => '119',
            'aramex_rest_half_kg' => '39',
            'aramex_over21_per_kg' => '59',
            'aramex_delivery_days' => '7-10',
            'dhl_first_half_kg' => '169',
            'dhl_rest_half_kg' => '43',
            'dhl_over21_per_kg' => '63',
            'dhl_delivery_days' => '7-10',
            'domestic_first_half_kg' => '69',
            'domestic_rest_half_kg' => '19',
            'domestic_delivery_days' => '4-7',

            // Carrier tracking URL templates ({tracking} replaced with the actual number)
            'carrier_url_aramex' => 'https://www.aramex.com/track/results?mode=0&ShipmentNumber={tracking}',
            'carrier_url_smsa' => 'https://www.smsaexpress.com/track/?tracknumbers={tracking}',
            'carrier_url_dhl' => 'https://www.dhl.com/sa-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}',
            'carrier_url_fedex' => 'https://www.fedextrack/?trknbr={tracking}',
            'carrier_url_ups' => 'https://www.ups.com/track?tracknum={tracking}',

            // Invoice defaults
            'invoice_filename_pattern' => 'Invoice-{order_number}.pdf',
            'invoice_comment_default' => '',

            // Hero section (homepage)
            'hero_title' => '',
            'hero_subtitle' => '',
            'hero_input_placeholder' => '',
            'hero_button_text' => '',
            'hero_show_whatsapp' => true,
            'hero_whatsapp_button_text' => '',
            'hero_whatsapp_number' => '',
            'hero_show_name_change_notice' => true,
            'hero_input_required' => false,
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
        return $this->rateInfoForCode($currency);
    }

    /** Build helper text for a currency code (used by Repeater item). */
    public function rateInfoForCode(?string $code): string
    {
        if ($code === null || $code === '') {
            return __('No data yet — run "Fetch Now".');
        }
        $er = $this->data['exchange_rates'] ?? null;
        $rates = is_array($er) ? ($er['rates'] ?? []) : [];
        $rate = $rates[$code] ?? null;

        if (! $rate || (empty($rate['market']) && empty($rate['final']))) {
            return __('No data yet — run "Fetch Now".');
        }

        $market = number_format((float) ($rate['market'] ?? 0), 4);
        $final = number_format((float) ($rate['final'] ?? 0), 4);

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
                        TextInput::make('site_name')
                            ->label(__('Site Name'))
                            ->default('Wasetzon')
                            ->required(),

                        Select::make('default_language')
                            ->label(__('Default Language'))
                            ->options(['ar' => __('Arabic'), 'en' => __('English')])
                            ->default('ar')
                            ->required(),

                        Select::make('default_currency')
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
                            ->default('USD')
                            ->required(),
                    ])
                    ->columns(3),

                // ── SEO (Site-Wide Defaults) ───────────────────────────────────
                Section::make(__('SEO'))
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->description(__('These settings apply across your entire site. Individual pages and blog posts can override some of these. Used by search engines (Google, Bing) and social networks (Facebook, Twitter) when your links are shared.'))
                    ->schema([
                        FileUpload::make('seo_default_og_image')
                            ->label(__('Default Share Image'))
                            ->helperText(__('The image shown when a page or post is shared on social media (Facebook, Twitter, WhatsApp). Used when a page or post has no image of its own. Recommended size: 1200×630 pixels. Leave empty to show no image.'))
                            ->image()
                            ->directory('og-images')
                            ->nullable()
                            ->columnSpanFull(),

                        Textarea::make('seo_default_meta_description')
                            ->label(__('Default Meta Description'))
                            ->helperText(__('Short text shown when your site appears in search results. Used when a page has no description of its own. Keep it under 160 characters. Example: "Buy from any store worldwide. We deliver to your door."'))
                            ->rows(2)
                            ->maxLength(200)
                            ->columnSpanFull(),

                        TextInput::make('seo_twitter_handle')
                            ->label(__('Twitter / X Handle'))
                            ->helperText(__('Your Twitter/X username without @. Example: wasetzon. Shown when links are shared on Twitter.'))
                            ->placeholder('wasetzon')
                            ->maxLength(30),

                        TextInput::make('seo_google_verification')
                            ->label(__('Google Search Console Verification'))
                            ->helperText(__('The "content" value from the meta tag Google gives you when you add your site. Example: abc123xyz. Leave empty if not using Google Search Console.'))
                            ->placeholder('abc123xyz...')
                            ->maxLength(100),

                        TextInput::make('seo_bing_verification')
                            ->label(__('Bing Webmaster Verification'))
                            ->helperText(__('The "content" value from the meta tag Bing gives you when you add your site. Example: 1234567890ABCD. Leave empty if not using Bing Webmaster Tools.'))
                            ->placeholder('1234567890ABCD...')
                            ->maxLength(100),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // ── Blog ─────────────────────────────────────────────────────
                Section::make(__('Blog'))
                    ->icon(Heroicon::OutlinedNewspaper)
                    ->schema([
                        Toggle::make('blog_comments_enabled')
                            ->label(__('Allow Blog Comments'))
                            ->default(true)
                            ->helperText(__('When OFF, the comment form and list are hidden on all blog posts.')),
                    ]),

                // ── Appearance ───────────────────────────────────────────────
                Section::make(__('Appearance'))
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->description(__('Logo, colors, and typography. Use per-language when you have different logos or text for Arabic and English.'))
                    ->schema([
                        TextInput::make('primary_color')
                            ->label(__('Primary Color (hex)'))
                            ->placeholder('#f97316')
                            ->maxLength(20),

                        TextInput::make('font_family')
                            ->label(__('Font Family'))
                            ->placeholder(__('IBM Plex Sans Arabic')),

                        Toggle::make('logo_use_per_language')
                            ->label(__('Use per-language logo and text'))
                            ->helperText(__('When ON, you can set different logo image and text for Arabic and English.'))
                            ->columnSpanFull()
                            ->onColor('success'),

                        // Single logo (when per-language is OFF)
                        FileUpload::make('logo_image')
                            ->label(__('Logo Image (all languages)'))
                            ->helperText(__('Shown in header. Recommended: PNG/SVG, max height 48px. Leave empty to show text logo.'))
                            ->image()
                            ->directory('logos')
                            ->maxSize(512)
                            ->nullable()
                            ->visible(fn ($get) => ! $get('logo_use_per_language')),

                        TextInput::make('logo_text')
                            ->label(__('Logo Text (all languages)'))
                            ->helperText(__('Shown when no logo image is uploaded.'))
                            ->visible(fn ($get) => ! $get('logo_use_per_language')),

                        TextInput::make('logo_alt')
                            ->label(__('Logo Alt Text (SEO)'))
                            ->helperText(__('Used in img alt attribute for accessibility and SEO. Leave empty to use logo text.'))
                            ->placeholder(__('Site name and tagline'))
                            ->maxLength(120)
                            ->visible(fn ($get) => ! $get('logo_use_per_language')),

                        // Per-language logos (when per-language is ON)
                        FileUpload::make('logo_image_ar')
                            ->label(__('Logo Image').' — '.__('Arabic'))
                            ->helperText(__('Arabic version. Recommended: PNG/SVG, max height 48px.'))
                            ->image()
                            ->directory('logos')
                            ->maxSize(512)
                            ->nullable()
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        FileUpload::make('logo_image_en')
                            ->label(__('Logo Image').' — '.__('English'))
                            ->helperText(__('English version. Recommended: PNG/SVG, max height 48px.'))
                            ->image()
                            ->directory('logos')
                            ->maxSize(512)
                            ->nullable()
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        TextInput::make('logo_text_ar')
                            ->label(__('Logo Text').' — '.__('Arabic'))
                            ->helperText(__('Shown when no logo image is uploaded.'))
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        TextInput::make('logo_text_en')
                            ->label(__('Logo Text').' — '.__('English'))
                            ->helperText(__('Shown when no logo image is uploaded.'))
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        TextInput::make('logo_alt_ar')
                            ->label(__('Logo Alt Text (SEO)').' — '.__('Arabic'))
                            ->helperText(__('Used in img alt for Arabic pages. Leave empty to use logo text.'))
                            ->maxLength(120)
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),

                        TextInput::make('logo_alt_en')
                            ->label(__('Logo Alt Text (SEO)').' — '.__('English'))
                            ->helperText(__('Used in img alt for English pages. Leave empty to use logo text.'))
                            ->maxLength(120)
                            ->visible(fn ($get) => (bool) $get('logo_use_per_language')),
                    ])
                    ->columns(3)
                    ->collapsible(false),

                // ── Hero Section ─────────────────────────────────────────────
                Section::make(__('Hero Section'))
                    ->icon(Heroicon::OutlinedHome)
                    ->description(__('Homepage hero: title, subtitle, product input, and WhatsApp button. Leave text fields empty to use default translations.'))
                    ->collapsed(false)
                    ->schema([
                        TextInput::make('hero_title')
                            ->label(__('hero.main_title'))
                            ->placeholder(__('Shop from any store in the world'))
                            ->maxLength(120),

                        Textarea::make('hero_subtitle')
                            ->label(__('hero.subtitle'))
                            ->rows(2)
                            ->placeholder(__('Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door.'))
                            ->maxLength(400),

                        TextInput::make('hero_input_placeholder')
                            ->label(__('hero.input_placeholder'))
                            ->placeholder(__('Paste a product link, or describe it if you don\'t have one'))
                            ->maxLength(120),

                        TextInput::make('hero_button_text')
                            ->label(__('hero.button_text'))
                            ->placeholder(__('Start Order'))
                            ->maxLength(40),

                        Toggle::make('hero_input_required')
                            ->label(__('hero.input_required'))
                            ->helperText(__('hero.input_required_help'))
                            ->default(false),

                        Toggle::make('hero_show_whatsapp')
                            ->label(__('hero.show_whatsapp'))
                            ->helperText(__('hero.show_whatsapp_help'))
                            ->default(true)
                            ->onColor('success'),

                        TextInput::make('hero_whatsapp_button_text')
                            ->label(__('hero.whatsapp_button_text'))
                            ->placeholder(__('Or order via WhatsApp'))
                            ->maxLength(60)
                            ->visible(fn ($get) => (bool) $get('hero_show_whatsapp')),

                        TextInput::make('hero_whatsapp_number')
                            ->label(__('hero.whatsapp_number'))
                            ->helperText(__('hero.whatsapp_number_help'))
                            ->placeholder('966501234567')
                            ->maxLength(20)
                            ->visible(fn ($get) => (bool) $get('hero_show_whatsapp')),

                        Toggle::make('hero_show_name_change_notice')
                            ->label(__('hero.show_name_change_notice'))
                            ->helperText(__('hero.show_name_change_notice_help'))
                            ->default(true),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // ── Order Rules ───────────────────────────────────────────────
                Section::make(__('Order Rules'))
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->schema([
                        TextInput::make('max_products_per_order')
                            ->label(__('Max Products per Order'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500),

                        Toggle::make('order_edit_enabled')
                            ->label(__('Allow Order Edit'))
                            ->helperText(__('When OFF, the edit link is hidden from customers.')),

                        TextInput::make('order_edit_click_window_minutes')
                            ->label(__('Minutes to Click Edit (from submission)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->helperText(__('Customer must click Edit within this time after placing order.')),

                        TextInput::make('order_edit_resubmit_window_minutes')
                            ->label(__('Minutes to Resubmit (from clicking Edit)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->helperText(__('After clicking Edit, customer has this long to save changes.')),

                        TextInput::make('order_edit_window_minutes')
                            ->label(__('Edit Window (minutes) — legacy'))
                            ->numeric()
                            ->helperText(__('Deprecated. Use the two windows above.')),

                        TextInput::make('max_orders_per_day')
                            ->label(__('Max Orders per Day (per user)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->helperText(__('Limit how many orders one user can place per day.')),

                        Select::make('order_new_layout')
                            ->label(__('New-Order Form Layout'))
                            ->options([
                                '1' => __('Option 1 — Responsive (default)'),
                                '2' => __('Option 2 — Cart system'),
                                '3' => __('Option 3 — Cards everywhere'),
                            ]),

                        TextInput::make('orders_per_hour_customer')
                            ->label(__('Orders/hour — Customer'))
                            ->numeric(),

                        TextInput::make('orders_per_hour_admin')
                            ->label(__('Orders/hour — Admin'))
                            ->numeric(),

                        TextInput::make('max_file_size_mb')
                            ->label(__('Max File Size (MB)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText(__('Maximum size per uploaded file. Default: 2 MB.')),

                        TextInput::make('comment_max_files')
                            ->label(__('Max Files per Comment'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->helperText(__('Max number of files a user can attach to one comment. Default: 5.')),

                        TextInput::make('comment_max_file_size_mb')
                            ->label(__('Max Comment File Size (MB)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText(__('Maximum size per file attached to a comment. Default: 10 MB.')),

                    ])
                    ->columns(3),

                // ── Order Form Fields ─────────────────────────────────────────
                Section::make(__('Order Form Fields'))
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->description(__('Control which fields appear in the new-order form, their order, and which are collapsed under "show more" on mobile.'))
                    ->schema([
                        Repeater::make('order_form_fields')
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
                        \Filament\Schemas\Components\Section::make('Aramex — '.__('Economy Shipping'))
                            ->schema([
                                TextInput::make('aramex_first_half_kg')
                                    ->label(__('First 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('aramex_rest_half_kg')
                                    ->label(__('Each Additional 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('aramex_over21_per_kg')
                                    ->label(__('Over 21 kg — per kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('aramex_delivery_days')
                                    ->label(__('Est. Delivery'))
                                    ->placeholder('7-10')
                                    ->helperText(__('Shown on calculator, e.g. "7-10 days"')),
                            ])
                            ->columns(4),

                        // ── DHL ──────────────────────────────────────────
                        \Filament\Schemas\Components\Section::make('DHL — '.__('Express Shipping'))
                            ->schema([
                                TextInput::make('dhl_first_half_kg')
                                    ->label(__('First 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('dhl_rest_half_kg')
                                    ->label(__('Each Additional 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('dhl_over21_per_kg')
                                    ->label(__('Over 21 kg — per kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('dhl_delivery_days')
                                    ->label(__('Est. Delivery'))
                                    ->placeholder('7-10'),
                            ])
                            ->columns(4),

                        // ── US Domestic ───────────────────────────────────
                        \Filament\Schemas\Components\Section::make(__('US Domestic Shipping'))
                            ->schema([
                                TextInput::make('domestic_first_half_kg')
                                    ->label(__('First 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('domestic_rest_half_kg')
                                    ->label(__('Each Additional 0.5 kg (SAR)'))
                                    ->numeric()->suffix('SAR')->minValue(0),

                                TextInput::make('domestic_delivery_days')
                                    ->label(__('Est. Delivery'))
                                    ->placeholder('4-7'),
                            ])
                            ->columns(3),

                        // ── Carrier Tracking URLs ─────────────────────────
                        \Filament\Schemas\Components\Section::make(__('Carrier Tracking URLs'))
                            ->description(__('Use {tracking} as the placeholder for the tracking number. Shown to customers as a clickable link on the order page.'))
                            ->schema([
                                TextInput::make('carrier_url_aramex')
                                    ->label(__('carriers.aramex'))
                                    ->nullable()
                                    ->rules(['nullable', 'regex:/^https?:\/\/.+/'])
                                    ->placeholder('https://www.aramex.com/track/results?mode=0&ShipmentNumber={tracking}')
                                    ->helperText(__('Leave blank to show tracking number only (no link).')),

                                TextInput::make('carrier_url_smsa')
                                    ->label(__('carriers.smsa'))
                                    ->nullable()
                                    ->rules(['nullable', 'regex:/^https?:\/\/.+/'])
                                    ->placeholder('https://www.smsaexpress.com/track/?tracknumbers={tracking}'),

                                TextInput::make('carrier_url_dhl')
                                    ->label(__('carriers.dhl'))
                                    ->nullable()
                                    ->rules(['nullable', 'regex:/^https?:\/\/.+/'])
                                    ->placeholder('https://www.dhl.com/sa-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}'),

                                TextInput::make('carrier_url_fedex')
                                    ->label(__('carriers.fedex'))
                                    ->nullable()
                                    ->rules(['nullable', 'regex:/^https?:\/\/.+/'])
                                    ->placeholder('https://www.fedextrack/?trknbr={tracking}'),

                                TextInput::make('carrier_url_ups')
                                    ->label(__('carriers.ups'))
                                    ->nullable()
                                    ->rules(['nullable', 'regex:/^https?:\/\/.+/'])
                                    ->placeholder('https://www.ups.com/track?tracknum={tracking}'),
                            ])
                            ->columns(1),
                    ])
                    ->collapsible(),

                // ── Exchange Rates ────────────────────────────────────────────
                Section::make(__('Exchange Rates'))
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->description(__('Rates are fetched from open.er-api.com (free, no key needed). Manual override per currency takes priority over the auto-calculated rate.'))
                    ->schema([
                        SchemaActions::make([
                            Action::make('fetchRatesNow')
                                ->label(__('Fetch Rates Now'))
                                ->icon(Heroicon::OutlinedArrowPath)
                                ->color('info')
                                ->requiresConfirmation()
                                ->modalHeading(__('Fetch Exchange Rates'))
                                ->modalDescription(__('This will call open.er-api.com and update the stored rates. Manual overrides will be preserved.'))
                                ->modalSubmitActionLabel(__('Fetch'))
                                ->action(function (): void {
                                    $exitCode = Artisan::call('rates:fetch');

                                    if ($exitCode === 0) {
                                        Notification::make()
                                            ->title(__('Rates updated successfully'))
                                            ->success()
                                            ->send();
                                        $this->mount();
                                    } else {
                                        Notification::make()
                                            ->title(__('Fetch failed — check API connection or logs'))
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),

                        Placeholder::make('exchange_rates_fetch_status')
                            ->label(__('Last Fetch'))
                            ->content(fn () => $this->buildFetchStatusString()),

                        Toggle::make('exchange_rates_auto_fetch')
                            ->label(__('Auto-Fetch Daily'))
                            ->onColor('success')
                            ->helperText(__('Fetch rates automatically each day via Laravel scheduler (php artisan schedule:run).')),

                        // Markup % and per-currency list (add/edit/remove currencies)
                        TextInput::make('exchange_rates_markup_percent')
                            ->label(__('Markup %'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(50)
                            ->helperText(__('Added on top of the market rate. Default: 3%')),

                        Repeater::make('currencies')
                            ->label(__('Currencies'))
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('Code'))
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('USD')
                                    ->helperText(__('ISO 4217 code (e.g. USD, EUR)')),

                                TextInput::make('label')
                                    ->label(__('Label'))
                                    ->maxLength(100)
                                    ->placeholder(__('Optional display name')),

                                TextInput::make('manual_rate')
                                    ->label(__('Manual Rate (SAR)'))
                                    ->numeric()
                                    ->placeholder(__('Auto'))
                                    ->helperText(fn ($get): string => $this->rateInfoForCode($get('code') ?? '')),

                                Toggle::make('auto_fetch')
                                    ->label(__('Auto-fetch'))
                                    ->default(true)
                                    ->helperText(__('Include in daily rate fetch')),

                                TextInput::make('markup_percent')
                                    ->label(__('Markup %'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->placeholder(__('Use global')),
                            ])
                            ->columns(5)
                            ->reorderable()
                            ->itemLabel(fn (array $state): string => ($state['code'] ?? '') ?: __('New currency'))
                            ->addActionLabel(__('Add currency'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // ── Commission Rules ──────────────────────────────────────────
                Section::make(__('Commission Rules'))
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->description(__('Controls how service commission is calculated on orders. Used by the order form and the public calculator.'))
                    ->schema([
                        TextInput::make('commission_threshold_sar')
                            ->label(__('Threshold (SAR)'))
                            ->numeric()
                            ->suffix('SAR')
                            ->minValue(0)
                            ->helperText(__('Order value above this threshold → percentage commission. Below → flat fee.')),

                        TextInput::make('commission_rate_above')
                            ->label(__('% Above Threshold'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText(__('Commission percentage when order ≥ threshold. Default: 8%')),

                        TextInput::make('commission_flat_below')
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
                    ->description(__('Enable or disable each quick-action button shown on the order detail page.'))
                    ->schema([
                        // Customer section
                        Toggle::make('qa_customer_section')
                            ->label(__('Show Customer Quick Actions Section'))
                            ->onColor('success')
                            ->columnSpanFull(),

                        Toggle::make('qa_payment_notify')
                            ->label('💰 '.__('Customer: Report Payment Transfer'))
                            ->onColor('success'),

                        Toggle::make('qa_shipping_address_btn')
                            ->label('📍 '.__('Customer: Set Shipping Address Button'))
                            ->onColor('success'),

                        Toggle::make('qa_similar_order')
                            ->label('📝 '.__('Customer: Similar Order'))
                            ->onColor('success'),

                        Toggle::make('qa_customer_merge')
                            ->label('🔀 '.__('Customer: Request Order Merge'))
                            ->onColor('success'),

                        Toggle::make('qa_customer_cancel')
                            ->label('❌ '.__('Customer: Cancel Order'))
                            ->onColor('danger'),

                        // Staff section
                        Toggle::make('qa_team_section')
                            ->label(__('Show Staff Quick Actions Section'))
                            ->onColor('success')
                            ->columnSpanFull(),

                        Toggle::make('qa_transfer_order')
                            ->label('🔄 '.__('Staff: Transfer Order Ownership'))
                            ->onColor('success'),

                        Toggle::make('qa_payment_tracking')
                            ->label('💰 '.__('Staff: Payment Tracking'))
                            ->onColor('success'),

                        Toggle::make('qa_shipping_tracking')
                            ->label('📦 '.__('Staff: Update Shipping Tracking'))
                            ->onColor('success'),

                        Toggle::make('qa_team_merge')
                            ->label('🔗 '.__('Staff: Merge Orders'))
                            ->onColor('success'),

                        // Legacy staff quick buttons
                        Toggle::make('qa_mark_paid')
                            ->label(__('Staff: Mark as Paid'))
                            ->onColor('success'),

                        Toggle::make('qa_mark_shipped')
                            ->label(__('Staff: Mark as Shipped'))
                            ->onColor('success'),

                        Toggle::make('qa_request_info')
                            ->label(__('Staff: Request More Info'))
                            ->onColor('success'),

                        Toggle::make('qa_cancel_order')
                            ->label(__('Staff: Cancel Order'))
                            ->onColor('danger'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // ── Email / SMTP ──────────────────────────────────────────────
                Section::make(__('Email / SMTP'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->description(__('Leave disabled until SMTP is configured. Use the Test button to verify.'))
                    ->schema([
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

                // ── Email Type Toggles ────────────────────────────────────────
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

                // ── Social Login ──────────────────────────────────────────────
                Section::make(__('Social Login'))
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->description(__('Allow users to sign in/up using third-party accounts. Requires OAuth credentials in .env.'))
                    ->schema([
                        Toggle::make('google_login_enabled')
                            ->label(__('Enable Google Sign-In'))
                            ->helperText(__('Shows a "Sign in with Google" button on the login and register pages. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file first.'))
                            ->onColor('success')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // ── Invoice ───────────────────────────────────────────────────
                Section::make(__('Invoice'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->description(__('Default filename pattern and comment text for generated invoices.'))
                    ->schema([
                        TextInput::make('invoice_filename_pattern')
                            ->label(__('Filename Pattern'))
                            ->helperText(__('Placeholders: {order_number}, {date}, {type}, {site_name}, {count}. Leave empty for default.'))
                            ->placeholder('Invoice-{order_number}.pdf')
                            ->maxLength(120),

                        Textarea::make('invoice_comment_default')
                            ->label(__('Default Comment Message'))
                            ->helperText(__('Default text when posting invoice as comment. Placeholders: {amount}, {order_number}, {date}, {currency}'))
                            ->rows(3)
                            ->placeholder(__('orders.invoice_attached'))
                            ->maxLength(500),
                    ])
                    ->columns(1)
                    ->collapsible(),

                // ── Contact ───────────────────────────────────────────────────
                Section::make(__('Contact'))
                    ->icon(Heroicon::OutlinedPhone)
                    ->description(__('Used in footer and hero WhatsApp button. Hero uses hero_whatsapp_number when set, otherwise this number.'))
                    ->schema([
                        TextInput::make('whatsapp')
                            ->label(__('WhatsApp Number'))
                            ->helperText(__('Include country code, e.g. 966501234567'))
                            ->placeholder('966501234567')
                            ->maxLength(20),

                        TextInput::make('contact_email')
                            ->label(__('Contact Email'))
                            ->email()
                            ->nullable()
                            ->maxLength(120),

                        TextInput::make('commercial_registration')
                            ->label(__('Commercial Registration'))
                            ->maxLength(100),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // ── Custom Scripts ────────────────────────────────────────────
                Section::make(__('Custom Scripts'))
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->description(__('Injected into every page. Use for analytics, chat widgets, etc.'))
                    ->schema([
                        Textarea::make('header_scripts')
                            ->label(__('Header Scripts (before </head>)'))
                            ->rows(4),

                        Textarea::make('footer_scripts')
                            ->label(__('Footer Scripts (before </body>)'))
                            ->rows(4),
                    ])
                    ->collapsible(),
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
            ->id('settings-form')
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
            'site_name' => 'general',
            'default_language' => 'general',
            'default_currency' => 'general',
            'seo_default_og_image' => 'seo',
            'seo_default_meta_description' => 'seo',
            'seo_twitter_handle' => 'seo',
            'seo_google_verification' => 'seo',
            'seo_bing_verification' => 'seo',
            'blog_comments_enabled' => 'blog',
            'primary_color' => 'appearance',
            'font_family' => 'appearance',
            'logo_use_per_language' => 'appearance',
            'logo_image' => 'appearance',
            'logo_image_ar' => 'appearance',
            'logo_image_en' => 'appearance',
            'logo_text' => 'appearance',
            'logo_text_ar' => 'appearance',
            'logo_text_en' => 'appearance',
            'logo_alt' => 'appearance',
            'logo_alt_ar' => 'appearance',
            'logo_alt_en' => 'appearance',
            'hero_title' => 'hero',
            'hero_subtitle' => 'hero',
            'hero_input_placeholder' => 'hero',
            'hero_button_text' => 'hero',
            'hero_input_required' => 'hero',
            'hero_show_whatsapp' => 'hero',
            'hero_whatsapp_button_text' => 'hero',
            'hero_whatsapp_number' => 'hero',
            'hero_show_name_change_notice' => 'hero',
            'max_products_per_order' => 'orders',
            'order_edit_enabled' => 'orders',
            'order_edit_click_window_minutes' => 'orders',
            'order_edit_resubmit_window_minutes' => 'orders',
            'order_edit_window_minutes' => 'orders',
            'order_new_layout' => 'orders',
            'orders_per_hour_customer' => 'orders',
            'orders_per_hour_admin' => 'orders',
            'max_file_size_mb' => 'orders',
            'max_orders_per_day' => 'orders',
            'comment_max_files' => 'orders',
            'comment_max_file_size_mb' => 'orders',
            'order_form_fields' => 'orders',
            'email_enabled' => 'email',
            'email_from_name' => 'email',
            'email_from_address' => 'email',
            'smtp_host' => 'email',
            'smtp_port' => 'email',
            'smtp_username' => 'email',
            'smtp_password' => 'email',
            'smtp_encryption' => 'email',
            'email_registration' => 'email',
            'email_welcome' => 'email',
            'email_password_reset' => 'email',
            'email_comment_notification' => 'email',
            'google_login_enabled' => 'social',
            'header_scripts' => 'scripts',
            'footer_scripts' => 'scripts',
            // Exchange rates (global only; per-currency in Currency model)
            'exchange_rates_markup_percent' => 'exchange_rates',
            'exchange_rates_auto_fetch' => 'exchange_rates',
            // Commission
            'commission_threshold_sar' => 'commission',
            'commission_rate_above' => 'commission',
            'commission_flat_below' => 'commission',
            // Quick actions — customer section
            'qa_customer_section' => 'quick_actions',
            'qa_payment_notify' => 'quick_actions',
            'qa_shipping_address_btn' => 'quick_actions',
            'qa_similar_order' => 'quick_actions',
            'qa_customer_merge' => 'quick_actions',
            'qa_customer_cancel' => 'quick_actions',
            // Quick actions — staff/team section
            'qa_team_section' => 'quick_actions',
            'qa_transfer_order' => 'quick_actions',
            'qa_payment_tracking' => 'quick_actions',
            'qa_shipping_tracking' => 'quick_actions',
            'qa_team_merge' => 'quick_actions',
            // Quick actions — legacy
            'qa_mark_paid' => 'quick_actions',
            'qa_mark_shipped' => 'quick_actions',
            'qa_request_info' => 'quick_actions',
            'qa_cancel_order' => 'quick_actions',
            // Shipping rates
            'aramex_first_half_kg' => 'shipping',
            'aramex_rest_half_kg' => 'shipping',
            'aramex_over21_per_kg' => 'shipping',
            'aramex_delivery_days' => 'shipping',
            'dhl_first_half_kg' => 'shipping',
            'dhl_rest_half_kg' => 'shipping',
            'dhl_over21_per_kg' => 'shipping',
            'dhl_delivery_days' => 'shipping',
            'domestic_first_half_kg' => 'shipping',
            'domestic_rest_half_kg' => 'shipping',
            'domestic_delivery_days' => 'shipping',
            // Carrier tracking URLs
            'carrier_url_aramex' => 'shipping',
            'carrier_url_smsa' => 'shipping',
            'carrier_url_dhl' => 'shipping',
            'carrier_url_fedex' => 'shipping',
            'carrier_url_ups' => 'shipping',
            'invoice_filename_pattern' => 'invoice',
            'invoice_comment_default' => 'invoice',
            'whatsapp' => 'contact',
            'contact_email' => 'contact',
            'commercial_registration' => 'contact',
        ];

        $booleanKeys = [
            'email_enabled', 'email_registration', 'email_welcome',
            'email_password_reset', 'email_comment_notification',
            'google_login_enabled',
            'exchange_rates_auto_fetch',
            'qa_customer_section', 'qa_payment_notify', 'qa_shipping_address_btn',
            'qa_similar_order', 'qa_customer_merge', 'qa_customer_cancel',
            'qa_team_section', 'qa_transfer_order', 'qa_payment_tracking',
            'qa_shipping_tracking', 'qa_team_merge',
            'qa_mark_paid', 'qa_mark_shipped', 'qa_request_info', 'qa_cancel_order',
            'order_edit_enabled',
            'blog_comments_enabled',
            'hero_input_required',
            'hero_show_whatsapp',
            'hero_show_name_change_notice',
            'logo_use_per_language',
        ];

        $integerKeys = [
            'smtp_port', 'max_products_per_order',
            'order_edit_click_window_minutes', 'order_edit_resubmit_window_minutes', 'order_edit_window_minutes',
            'orders_per_hour_customer', 'orders_per_hour_admin',
            'max_file_size_mb', 'max_orders_per_day',
            'comment_max_files', 'comment_max_file_size_mb',
            'aramex_first_half_kg', 'aramex_rest_half_kg', 'aramex_over21_per_kg',
            'dhl_first_half_kg', 'dhl_rest_half_kg', 'dhl_over21_per_kg',
            'domestic_first_half_kg', 'domestic_rest_half_kg',
        ];

        $floatKeys = [
            'exchange_rates_markup_percent',
            'commission_threshold_sar', 'commission_rate_above', 'commission_flat_below',
        ];

        $jsonKeys = ['order_form_fields'];

        // Keys not saved to settings table (handled separately or read-only)
        $skipKeys = ['exchange_rates', 'currencies'];

        // Sync currencies repeater to Currency table
        $this->syncCurrenciesFromData($data['currencies'] ?? []);

        foreach ($data as $key => $value) {
            if (in_array($key, $skipKeys)) {
                continue;
            }

            $group = $groupMap[$key] ?? 'general';

            // FileUpload may return array with single path; normalize to string
            if (in_array($key, ['logo_image', 'logo_image_ar', 'logo_image_en']) && is_array($value)) {
                $value = $value[0] ?? '';
            }

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
     * Persist repeater currencies to the Currency table (create/update/delete).
     *
     * @param  array<int, array{id?: int, code?: string, label?: string, manual_rate?: string|float, auto_fetch?: bool, markup_percent?: string|float}>  $items
     */
    protected function syncCurrenciesFromData(array $items): void
    {
        $keptIds = [];

        foreach ($items as $index => $item) {
            $code = trim((string) ($item['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $code = strtoupper($code);

            $manualRate = isset($item['manual_rate']) && $item['manual_rate'] !== '' && is_numeric($item['manual_rate'])
                ? (float) $item['manual_rate']
                : null;
            $markupPercent = isset($item['markup_percent']) && $item['markup_percent'] !== '' && is_numeric($item['markup_percent'])
                ? (float) $item['markup_percent']
                : null;

            $currency = isset($item['id']) && $item['id']
                ? Currency::find($item['id'])
                : null;

            if ($currency) {
                $currency->update([
                    'code' => $code,
                    'label' => trim((string) ($item['label'] ?? '')) ?: null,
                    'manual_rate' => $manualRate,
                    'auto_fetch' => (bool) ($item['auto_fetch'] ?? true),
                    'markup_percent' => $markupPercent,
                    'sort_order' => $index,
                ]);
            } else {
                $currency = Currency::create([
                    'code' => $code,
                    'label' => trim((string) ($item['label'] ?? '')) ?: null,
                    'manual_rate' => $manualRate,
                    'auto_fetch' => (bool) ($item['auto_fetch'] ?? true),
                    'markup_percent' => $markupPercent,
                    'sort_order' => $index,
                ]);
            }
            $keptIds[] = $currency->id;
        }

        Currency::whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Re-save the exchange_rates JSON blob after settings are updated.
     * Applies global markup and per-currency manual overrides from Currency model.
     */
    protected function syncExchangeRatesJson(array $data): void
    {
        $er = Setting::get('exchange_rates', []) ?: [];

        if (empty($er['rates'])) {
            $er['rates'] = FetchExchangeRates::DEFAULT_RATES;
        }

        $markup = (float) ($data['exchange_rates_markup_percent'] ?? 3);
        $er['markup_percent'] = $markup;
        $er['auto_fetch_enabled'] = (bool) ($data['exchange_rates_auto_fetch'] ?? true);

        $currencies = Currency::ordered()->get();

        foreach ($currencies as $model) {
            $cur = $model->code;
            if (! isset($er['rates'][$cur])) {
                $er['rates'][$cur] = FetchExchangeRates::DEFAULT_RATES[$cur] ?? [
                    'auto' => true, 'market' => 0, 'manual' => null, 'final' => 0,
                ];
            }

            $er['rates'][$cur]['manual'] = $model->manual_rate;
            $perMarkup = $model->markup_percent ?? $markup;

            if ($model->manual_rate !== null) {
                $er['rates'][$cur]['final'] = (float) $model->manual_rate;
            } else {
                $market = (float) ($er['rates'][$cur]['market'] ?? 0);
                $er['rates'][$cur]['final'] = $market > 0
                    ? round($market * (1 + $perMarkup / 100), 4)
                    : ($er['rates'][$cur]['final'] ?? 0);
            }
        }

        Setting::set('exchange_rates', $er, 'json', 'exchange_rates');
    }
}
