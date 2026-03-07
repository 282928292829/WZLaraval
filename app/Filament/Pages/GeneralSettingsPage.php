<?php

namespace App\Filament\Pages;

use App\Enums\AdminNavigationGroup;
use App\Models\Setting;
use App\Services\SettingsPersistService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GeneralSettingsPage extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 0;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('General');
    }

    public function getTitle(): string
    {
        return __('General Settings');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    /** @var list<string> General setting keys to load */
    protected const GENERAL_KEYS = [
        'site_name', 'default_language', 'default_currency',
        'site_timezone', 'times_use_user_timezone',
        'seo_default_og_image', 'seo_default_meta_description',
        'seo_twitter_handle', 'seo_google_verification', 'seo_bing_verification',
        'blog_comments_enabled', 'page_comments_enabled',
        'hero_title', 'hero_subtitle', 'hero_input_placeholder', 'hero_button_text',
        'hero_input_required', 'hero_show_whatsapp', 'hero_whatsapp_button_text',
        'hero_whatsapp_number', 'hero_show_name_change_notice',
        'whatsapp', 'contact_email', 'commercial_registration', 'copyright_year_initiated',
        'payment_company_name', 'payment_banks',
        'header_scripts', 'footer_scripts',
    ];

    public function mount(): void
    {
        $data = $this->defaults();
        $allSettings = Setting::all()->keyBy('key');

        foreach (static::GENERAL_KEYS as $key) {
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
            'site_name' => config('app.name'),
            'default_language' => 'ar',
            'default_currency' => 'USD',
            'site_timezone' => 'Asia/Riyadh',
            'times_use_user_timezone' => false,
            'seo_default_og_image' => '',
            'seo_default_meta_description' => '',
            'seo_twitter_handle' => '',
            'seo_google_verification' => '',
            'seo_bing_verification' => '',
            'blog_comments_enabled' => true,
            'page_comments_enabled' => true,
            'hero_title' => '',
            'hero_subtitle' => '',
            'hero_input_placeholder' => '',
            'hero_button_text' => '',
            'hero_input_required' => false,
            'hero_show_whatsapp' => true,
            'hero_whatsapp_button_text' => '',
            'hero_whatsapp_number' => '',
            'hero_show_name_change_notice' => true,
            'whatsapp' => '',
            'contact_email' => '',
            'commercial_registration' => '',
            'copyright_year_initiated' => '',
            'payment_company_name' => '',
            'payment_banks' => [],
            'header_scripts' => '',
            'footer_scripts' => '',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('general-settings-tabs')
                    ->tabs([
                        Tab::make(__('Site'))
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->schema([
                                Section::make(__('General'))
                                    ->icon(Heroicon::OutlinedGlobeAlt)
                                    ->schema([
                                        TextInput::make('site_name')
                                            ->label(__('Site Name'))
                                            ->default(config('app.name'))
                                            ->required(),

                                        Select::make('default_language')
                                            ->label(__('Default Language'))
                                            ->options(['ar' => __('Arabic'), 'en' => __('English')])
                                            ->default('ar')
                                            ->required(),

                                        Select::make('default_currency')
                                            ->label(__('Default Currency'))
                                            ->options([
                                                'USD' => __('settings.currency_usd'),
                                                'EUR' => __('settings.currency_eur'),
                                                'GBP' => __('settings.currency_gbp'),
                                                'SAR' => __('settings.currency_sar'),
                                                'AED' => __('settings.currency_aed'),
                                                'KWD' => __('settings.currency_kwd'),
                                                'QAR' => __('settings.currency_qar'),
                                                'BHD' => __('settings.currency_bhd'),
                                                'OMR' => __('settings.currency_omr'),
                                                'EGP' => __('settings.currency_egp'),
                                                'TRY' => __('settings.currency_try'),
                                                'CNY' => __('settings.currency_cny'),
                                            ])
                                            ->searchable()
                                            ->default('USD')
                                            ->required(),
                                    ])
                                    ->columns(3),

                                Section::make(__('Date & Time'))
                                    ->icon(Heroicon::OutlinedClock)
                                    ->description(__('How dates and times are displayed. Site timezone is used when user timezone is OFF or when the user has not set their timezone.'))
                                    ->schema([
                                        Select::make('site_timezone')
                                            ->label(__('Site timezone'))
                                            ->options([
                                                'Asia/Riyadh' => __('settings.timezone_riyadh'),
                                                'Asia/Dubai' => __('settings.timezone_dubai'),
                                                'Asia/Kuwait' => __('settings.timezone_kuwait'),
                                                'Asia/Bahrain' => __('settings.timezone_bahrain'),
                                                'Asia/Qatar' => __('settings.timezone_qatar'),
                                                'Africa/Cairo' => __('settings.timezone_cairo'),
                                                'Europe/London' => __('settings.timezone_london'),
                                                'Europe/Paris' => __('settings.timezone_paris'),
                                                'America/New_York' => __('settings.timezone_new_york'),
                                                'America/Los_Angeles' => __('settings.timezone_los_angeles'),
                                                'America/Chicago' => __('settings.timezone_chicago'),
                                                'UTC' => 'UTC',
                                            ])
                                            ->default('Asia/Riyadh')
                                            ->searchable()
                                            ->required(),

                                        Toggle::make('times_use_user_timezone')
                                            ->label(__('Use user timezone when set'))
                                            ->helperText(__('When ON, dates are shown in the user\'s timezone if they have set one in their profile. When OFF or if the user has no timezone, site timezone is used.'))
                                            ->default(false)
                                            ->onColor('success'),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make(__('SEO'))
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema([
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
                                            ->helperText(__('Your Twitter/X username without @. Example: yoursite. Shown when links are shared on Twitter.'))
                                            ->placeholder(__('settings.placeholder_twitter'))
                                            ->maxLength(30),

                                        TextInput::make('seo_google_verification')
                                            ->label(__('Google Search Console Verification'))
                                            ->helperText(__('The "content" value from the meta tag Google gives you when you add your site. Example: abc123xyz. Leave empty if not using Google Search Console.'))
                                            ->placeholder(__('settings.placeholder_google_verification'))
                                            ->maxLength(100),

                                        TextInput::make('seo_bing_verification')
                                            ->label(__('Bing Webmaster Verification'))
                                            ->helperText(__('The "content" value from the meta tag Bing gives you when you add your site. Example: 1234567890ABCD. Leave empty if not using Bing Webmaster Tools.'))
                                            ->placeholder(__('settings.placeholder_bing_verification'))
                                            ->maxLength(100),
                                    ])
                                    ->columns(3)
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Content'))
                            ->icon(Heroicon::OutlinedNewspaper)
                            ->schema([
                                Section::make(__('Blog'))
                                    ->icon(Heroicon::OutlinedNewspaper)
                                    ->schema([
                                        Toggle::make('blog_comments_enabled')
                                            ->label(__('Allow Blog Comments'))
                                            ->default(true)
                                            ->helperText(__('When OFF, the comment form and list are hidden on all blog posts.')),
                                        Toggle::make('page_comments_enabled')
                                            ->label(__('Allow Page Comments'))
                                            ->default(true)
                                            ->helperText(__('When OFF, the comment form and list are hidden on all static pages.')),
                                    ]),

                                Section::make(__('Hero Section'))
                                    ->icon(Heroicon::OutlinedHome)
                                    ->description(__('Homepage hero: title, subtitle, product input, and WhatsApp button. Leave text fields empty to use default translations.'))
                                    ->collapsed(false)
                                    ->schema([
                                        TextInput::make('hero_title')
                                            ->label(__('hero.main_title'))
                                            ->placeholder(__('Shop from :store worldwide', ['store' => __('any store')]))
                                            ->maxLength(120),

                                        Textarea::make('hero_subtitle')
                                            ->label(__('hero.subtitle'))
                                            ->placeholder(__('Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.'))
                                            ->rows(2)
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
                                            ->placeholder(__('settings.placeholder_whatsapp'))
                                            ->maxLength(20)
                                            ->visible(fn ($get) => (bool) $get('hero_show_whatsapp')),

                                        Toggle::make('hero_show_name_change_notice')
                                            ->label(__('hero.show_name_change_notice'))
                                            ->helperText(__('hero.show_name_change_notice_help'))
                                            ->default(true),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Contact & Payment'))
                            ->icon(Heroicon::OutlinedPhone)
                            ->schema([
                                Section::make(__('Contact'))
                                    ->icon(Heroicon::OutlinedPhone)
                                    ->description(__('Used in footer and hero WhatsApp button. Hero uses hero_whatsapp_number when set, otherwise this number.'))
                                    ->schema([
                                        TextInput::make('whatsapp')
                                            ->label(__('WhatsApp Number'))
                                            ->helperText(__('Include country code, e.g. 966501234567'))
                                            ->placeholder(__('settings.placeholder_whatsapp'))
                                            ->maxLength(20),

                                        TextInput::make('contact_email')
                                            ->label(__('Contact Email'))
                                            ->email()
                                            ->nullable()
                                            ->maxLength(120),

                                        TextInput::make('commercial_registration')
                                            ->label(__('Commercial Registration'))
                                            ->maxLength(100),

                                        TextInput::make('copyright_year_initiated')
                                            ->label(__('settings.copyright_year_initiated'))
                                            ->helperText(__('settings.copyright_year_initiated_help'))
                                            ->placeholder((string) date('Y'))
                                            ->numeric()
                                            ->maxLength(4)
                                            ->minValue(1900)
                                            ->maxValue((int) date('Y')),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),

                                Section::make(__('Payment'))
                                    ->icon(Heroicon::OutlinedCreditCard)
                                    ->description(__('payment.settings_section_desc'))
                                    ->schema([
                                        TextInput::make('payment_company_name')
                                            ->label(__('payment.beneficiary_company_label'))
                                            ->helperText(__('payment.beneficiary_company_help'))
                                            ->placeholder(config('app.name'))
                                            ->maxLength(120),

                                        Repeater::make('payment_banks')
                                            ->label(__('payment.bank_accounts'))
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('payment.bank_name'))
                                                    ->required()
                                                    ->maxLength(80),
                                                TextInput::make('logo')
                                                    ->label(__('payment.logo_path'))
                                                    ->helperText(__('payment.logo_path_help'))
                                                    ->placeholder('/images/banks/rajhi.svg')
                                                    ->maxLength(200),
                                                TextInput::make('account')
                                                    ->label(__('payment.account_number'))
                                                    ->maxLength(50),
                                                TextInput::make('iban')
                                                    ->label(__('payment.iban'))
                                                    ->maxLength(50),
                                                TextInput::make('beneficiary')
                                                    ->label(__('payment.beneficiary_name'))
                                                    ->helperText(__('payment.beneficiary_per_bank_help'))
                                                    ->maxLength(120),
                                            ])
                                            ->columns(2)
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                            ->addActionLabel(__('payment.add_bank')),
                                    ])
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Scripts'))
                            ->icon(Heroicon::OutlinedCodeBracket)
                            ->schema([
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
            ->id('general-settings-form')
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
            'site_name' => 'general',
            'default_language' => 'general',
            'default_currency' => 'general',
            'site_timezone' => 'general',
            'times_use_user_timezone' => 'general',
            'seo_default_og_image' => 'seo',
            'seo_default_meta_description' => 'seo',
            'seo_twitter_handle' => 'seo',
            'seo_google_verification' => 'seo',
            'seo_bing_verification' => 'seo',
            'blog_comments_enabled' => 'blog',
            'page_comments_enabled' => 'blog',
            'hero_title' => 'hero',
            'hero_subtitle' => 'hero',
            'hero_input_placeholder' => 'hero',
            'hero_button_text' => 'hero',
            'hero_input_required' => 'hero',
            'hero_show_whatsapp' => 'hero',
            'hero_whatsapp_button_text' => 'hero',
            'hero_whatsapp_number' => 'hero',
            'hero_show_name_change_notice' => 'hero',
            'whatsapp' => 'contact',
            'contact_email' => 'contact',
            'commercial_registration' => 'contact',
            'copyright_year_initiated' => 'contact',
            'payment_company_name' => 'payment',
            'payment_banks' => 'payment',
            'header_scripts' => 'scripts',
            'footer_scripts' => 'scripts',
        ];

        $booleanKeys = [
            'blog_comments_enabled',
            'page_comments_enabled',
            'page_comments_enabled',
            'hero_input_required',
            'hero_show_whatsapp',
            'hero_show_name_change_notice',
            'times_use_user_timezone',
        ];

        $integerKeys = ['copyright_year_initiated'];

        $jsonKeys = ['payment_banks'];

        $fileUploadKeys = ['seo_default_og_image'];

        $service = app(SettingsPersistService::class);
        $service->persist(
            $data,
            $groupMap,
            $booleanKeys,
            $integerKeys,
            [],
            $jsonKeys,
            [],
            $fileUploadKeys
        );

        $this->data = array_merge($this->data, $data);

        Notification::make()
            ->title(__('Settings saved'))
            ->success()
            ->send();
    }
}
