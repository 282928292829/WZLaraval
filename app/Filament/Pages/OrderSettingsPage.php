<?php

namespace App\Filament\Pages;

use App\Enums\AdminNavigationGroup;
use App\Models\Currency;
use App\Models\Setting;
use App\Services\SettingsPersistService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class OrderSettingsPage extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 0;

    protected static ?string $title = null;

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::OrderSetup;
    }

    public static function getNavigationLabel(): string
    {
        return __('Order Settings');
    }

    public function getTitle(): string
    {
        return __('Order Settings');
    }

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        return $user?->hasPermissionTo('manage-settings') ?? false;
    }

    /** @var list<string> Order-related setting keys to load */
    protected const ORDER_KEYS = [
        'auto_comment_with_price', 'auto_comment_no_price',
        'order_success_screen_enabled', 'order_success_title_ar', 'order_success_title_en',
        'order_success_subtitle_ar', 'order_success_subtitle_en',
        'order_success_message_ar', 'order_success_message_en',
        'order_success_go_to_order_ar', 'order_success_go_to_order_en',
        'order_success_redirect_prefix_ar', 'order_success_redirect_prefix_en',
        'order_success_redirect_suffix_ar', 'order_success_redirect_suffix_en',
        'order_success_redirect_seconds', 'order_success_screen_threshold',
        'max_products_per_order', 'order_edit_enabled', 'order_edit_click_window_minutes',
        'order_edit_resubmit_window_minutes', 'order_edit_window_minutes',
        'order_new_layout', 'order_form_show_add_test_items', 'order_form_show_reset_all',
        'orders_per_hour_customer', 'orders_per_hour_admin', 'orders_per_day_staff',
        'orders_per_month_customer', 'orders_per_month_admin', 'orders_per_day_customer',
        'max_file_size_mb', 'max_images_per_item', 'max_images_per_order',
        'max_files_per_item_after_submit', 'customer_can_add_files_after_submit',
        'comment_max_files', 'comment_max_file_size_mb',
        'payment_notify_order_max_files', 'payment_notify_standalone_max_files',
        'order_form_fields',
        'aramex_first_half_kg', 'aramex_rest_half_kg', 'aramex_over21_per_kg', 'aramex_delivery_days',
        'dhl_first_half_kg', 'dhl_rest_half_kg', 'dhl_over21_per_kg', 'dhl_delivery_days',
        'domestic_first_half_kg', 'domestic_rest_half_kg', 'domestic_delivery_days',
        'exchange_rates_markup_percent', 'exchange_rates_auto_fetch',
        'commission_threshold_sar', 'commission_rate_above', 'commission_flat_below',
        'qa_customer_section', 'qa_payment_notify', 'qa_shipping_address_btn',
        'qa_similar_order', 'qa_customer_merge', 'qa_customer_cancel',
        'qa_team_section', 'qa_transfer_order', 'qa_payment_tracking',
        'qa_shipping_tracking', 'qa_team_merge',
        'qa_mark_paid', 'qa_mark_shipped', 'qa_request_info', 'qa_cancel_order',
        'invoice_filename_pattern', 'invoice_number_pattern', 'invoice_show_type_label',
        'invoice_type_labels', 'invoice_show_company_details', 'invoice_company_details',
        'invoice_show_due_date', 'invoice_due_date_days', 'invoice_due_date_label',
        'invoice_comment_default', 'invoice_first_payment_comment_template',
        'invoice_greeting', 'invoice_confirmation', 'invoice_payment_instructions',
        'invoice_footer_text', 'invoice_show_order_items', 'invoice_custom_lines',
    ];

    public function mount(): void
    {
        $data = $this->defaults();

        $allSettings = Setting::all()->keyBy('key');

        foreach (static::ORDER_KEYS as $key) {
            $setting = $allSettings->get($key);
            if ($setting) {
                $data[$key] = $setting->type === 'json'
                    ? json_decode($setting->value, true)
                    : $setting->value;
            }
        }

        // Inflate flat exchange-rate config from the stored JSON blob
        $er = Setting::get('exchange_rates', []);
        if (is_array($er)) {
            if (isset($er['markup_percent'])) {
                $data['exchange_rates_markup_percent'] = (string) $er['markup_percent'];
            }
            if (isset($er['auto_fetch_enabled'])) {
                $data['exchange_rates_auto_fetch'] = (bool) $er['auto_fetch_enabled'];
            }
        }
        $data['exchange_rates'] = $er;

        // Load currencies from DB (for Exchange Rates repeater)
        $data['currencies'] = Currency::ordered()->get()->map(fn (Currency $c): array => [
            'id' => $c->id,
            'code' => $c->code,
            'label' => $c->label ?? '',
            'manual_rate' => $c->manual_rate !== null ? (string) $c->manual_rate : '',
            'auto_fetch' => $c->auto_fetch,
            'markup_percent' => $c->markup_percent !== null ? (string) $c->markup_percent : '',
        ])->values()->toArray();

        // Normalize repeater data to sequential arrays to avoid Filament Repeater
        // getChildSchema/getStateSnapshot null bug (filamentphp/filament#18530)
        $data['order_form_fields'] = $this->normalizeRepeaterItems($data['order_form_fields'] ?? []);
        $data['invoice_company_details'] = $this->normalizeRepeaterItems($data['invoice_company_details'] ?? []);
        $data['invoice_custom_lines'] = $this->normalizeRepeaterItems($data['invoice_custom_lines'] ?? []);

        $this->data = $data;
        $this->form->fill($this->data);
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'auto_comment_with_price' => '',
            'auto_comment_no_price' => '',
            'order_success_screen_enabled' => true,
            'order_success_title_ar' => '',
            'order_success_title_en' => '',
            'order_success_subtitle_ar' => '',
            'order_success_subtitle_en' => '',
            'order_success_message_ar' => '',
            'order_success_message_en' => '',
            'order_success_go_to_order_ar' => '',
            'order_success_go_to_order_en' => '',
            'order_success_redirect_prefix_ar' => '',
            'order_success_redirect_prefix_en' => '',
            'order_success_redirect_suffix_ar' => '',
            'order_success_redirect_suffix_en' => '',
            'order_success_redirect_seconds' => '30',
            'order_success_screen_threshold' => '10',
            'max_products_per_order' => '30',
            'order_edit_enabled' => true,
            'order_edit_click_window_minutes' => '10',
            'order_edit_resubmit_window_minutes' => '10',
            'order_edit_window_minutes' => '10',
            'order_new_layout' => 'cards',
            'order_form_show_add_test_items' => false,
            'order_form_show_reset_all' => true,
            'orders_per_hour_customer' => '50',
            'orders_per_hour_admin' => '50',
            'orders_per_day_staff' => '100',
            'orders_per_month_customer' => '500',
            'orders_per_month_admin' => '1000',
            'orders_per_day_customer' => '200',
            'max_file_size_mb' => '2',
            'max_images_per_item' => '3',
            'max_images_per_order' => '10',
            'max_files_per_item_after_submit' => '5',
            'customer_can_add_files_after_submit' => '0',
            'comment_max_files' => '10',
            'comment_max_file_size_mb' => '10',
            'payment_notify_order_max_files' => '5',
            'payment_notify_standalone_max_files' => '5',
            'order_form_fields' => [],
            'exchange_rates_markup_percent' => '3',
            'exchange_rates_auto_fetch' => true,
            'commission_threshold_sar' => '500',
            'commission_rate_above' => '8',
            'commission_flat_below' => '50',
            'qa_customer_section' => true,
            'qa_payment_notify' => true,
            'qa_shipping_address_btn' => true,
            'qa_similar_order' => true,
            'qa_customer_merge' => true,
            'qa_customer_cancel' => true,
            'qa_team_section' => true,
            'qa_transfer_order' => true,
            'qa_payment_tracking' => true,
            'qa_shipping_tracking' => true,
            'qa_team_merge' => true,
            'qa_mark_paid' => true,
            'qa_mark_shipped' => true,
            'qa_request_info' => true,
            'qa_cancel_order' => true,
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
            'invoice_filename_pattern' => 'Invoice-{order_number}.pdf',
            'invoice_number_pattern' => '{order_number}-{count}',
            'invoice_show_type_label' => false,
            'invoice_type_labels' => [],
            'invoice_show_company_details' => false,
            'invoice_company_details' => [],
            'invoice_show_due_date' => false,
            'invoice_due_date_days' => 7,
            'invoice_due_date_label' => '',
            'invoice_comment_default' => '',
            'invoice_first_payment_comment_template' => '',
            'invoice_greeting' => '',
            'invoice_confirmation' => '',
            'invoice_payment_instructions' => '',
            'invoice_footer_text' => '',
            'invoice_show_order_items' => false,
            'invoice_custom_lines' => [],
        ];
    }

    /**
     * Normalize repeater data to sequential arrays.
     * Filament may store UUID-keyed objects; converting to sequential helps avoid
     * getChildSchema/getStateSnapshot null bug (filamentphp/filament#18530).
     *
     * @param  array<int|string, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRepeaterItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }
        // If single-element array with associative child (Filament UUID structure)
        if (count($items) === 1 && is_array($first = reset($items)) && array_is_list($first) === false) {
            $items = array_values($first);
        } elseif (array_is_list($items) === false) {
            $items = array_values($items);
        }

        return array_values(array_filter($items, fn ($i) => is_array($i)));
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
                Tabs::make('order-settings-tabs')
                    ->tabs([
                        Tab::make(__('Order Flow'))
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make(__('Order Auto Reply'))
                                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                                    ->description(__('Order auto-reply posted as system comment after order creation. Leave empty to use default translations.'))
                                    ->schema([
                                        Textarea::make('auto_comment_with_price')
                                            ->label(__('When prices entered'))
                                            ->placeholder(__('orders.auto_comment_with_price', ['subtotal' => ':subtotal', 'commission' => ':commission', 'total' => ':total', 'site_name' => ':site_name', 'whatsapp' => ':whatsapp', 'payment_url' => ':payment_url', 'terms_url' => ':terms_url', 'faq_url' => ':faq_url', 'shipping_url' => ':shipping_url', 'company_name' => ':company_name']))
                                            ->helperText(__('Placeholders: :subtotal, :commission, :total, :site_name, :whatsapp, :payment_url, :terms_url, :faq_url, :shipping_url, :company_name'))
                                            ->rows(12)
                                            ->columnSpanFull(),

                                        Textarea::make('auto_comment_no_price')
                                            ->label(__('When no prices entered'))
                                            ->placeholder(__('orders.auto_comment_no_price', ['whatsapp' => ':whatsapp']))
                                            ->helperText(__('Placeholders: :whatsapp'))
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(),

                                // ── Order Success Screen ──────────────────────────────────────
                                Section::make(__('settings.order_success_screen'))
                                    ->icon(Heroicon::OutlinedCheckCircle)
                                    ->description(__('settings.order_success_screen_desc'))
                                    ->schema([
                                        Toggle::make('order_success_screen_enabled')
                                            ->label(__('settings.order_success_screen_enabled'))
                                            ->helperText(__('settings.order_success_screen_enabled_help'))
                                            ->default(true)
                                            ->onColor('success')
                                            ->columnSpanFull(),

                                        TextInput::make('order_success_title_ar')
                                            ->label(__('settings.order_success_title_ar'))
                                            ->maxLength(500)
                                            ->placeholder(__('order.success_title')),

                                        TextInput::make('order_success_title_en')
                                            ->label(__('settings.order_success_title_en'))
                                            ->maxLength(500)
                                            ->placeholder(__('order.success_title')),

                                        TextInput::make('order_success_subtitle_ar')
                                            ->label(__('settings.order_success_subtitle_ar'))
                                            ->helperText(__('settings.order_success_subtitle_help'))
                                            ->maxLength(500)
                                            ->placeholder(__('order.success_subtitle')),

                                        TextInput::make('order_success_subtitle_en')
                                            ->label(__('settings.order_success_subtitle_en'))
                                            ->helperText(__('settings.order_success_subtitle_help'))
                                            ->maxLength(500)
                                            ->placeholder(__('order.success_subtitle')),

                                        Textarea::make('order_success_message_ar')
                                            ->label(__('settings.order_success_message_ar'))
                                            ->placeholder(__('order.success_message'))
                                            ->helperText(__('settings.order_success_message_help'))
                                            ->rows(4)
                                            ->maxLength(2000)
                                            ->columnSpanFull(),

                                        Textarea::make('order_success_message_en')
                                            ->label(__('settings.order_success_message_en'))
                                            ->placeholder(__('order.success_message'))
                                            ->helperText(__('settings.order_success_message_help'))
                                            ->rows(4)
                                            ->maxLength(2000)
                                            ->columnSpanFull(),

                                        TextInput::make('order_success_go_to_order_ar')
                                            ->label(__('settings.order_success_go_to_order_ar'))
                                            ->placeholder(__('order.success_go_to_order'))
                                            ->maxLength(500),

                                        TextInput::make('order_success_go_to_order_en')
                                            ->label(__('settings.order_success_go_to_order_en'))
                                            ->placeholder(__('order.success_go_to_order'))
                                            ->maxLength(500),

                                        TextInput::make('order_success_redirect_prefix_ar')
                                            ->label(__('settings.order_success_redirect_prefix_ar'))
                                            ->placeholder(__('order.success_redirect_countdown_prefix'))
                                            ->maxLength(500),

                                        TextInput::make('order_success_redirect_prefix_en')
                                            ->label(__('settings.order_success_redirect_prefix_en'))
                                            ->placeholder(__('order.success_redirect_countdown_prefix'))
                                            ->maxLength(500),

                                        TextInput::make('order_success_redirect_suffix_ar')
                                            ->label(__('settings.order_success_redirect_suffix_ar'))
                                            ->placeholder(__('order.success_redirect_countdown_suffix'))
                                            ->maxLength(500),

                                        TextInput::make('order_success_redirect_suffix_en')
                                            ->label(__('settings.order_success_redirect_suffix_en'))
                                            ->placeholder(__('order.success_redirect_countdown_suffix'))
                                            ->maxLength(500),

                                        TextInput::make('order_success_redirect_seconds')
                                            ->label(__('settings.order_success_redirect_seconds'))
                                            ->helperText(__('settings.order_success_redirect_seconds_help'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(120)
                                            ->default(45),

                                        TextInput::make('order_success_screen_threshold')
                                            ->label(__('settings.order_success_screen_threshold'))
                                            ->helperText(__('settings.order_success_screen_threshold_help'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(10000)
                                            ->default(1000),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Rules & Form'))
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->schema([
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

                                        TextInput::make('orders_per_day_customer')
                                            ->label(__('Orders/day — Customer'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1000)
                                            ->helperText(__('Max orders per user per day. 0 = unlimited.')),

                                        TextInput::make('orders_per_day_staff')
                                            ->label(__('Orders/day — Staff'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1000)
                                            ->helperText(__('Max orders per staff per day. 0 = unlimited.')),

                                        TextInput::make('orders_per_month_customer')
                                            ->label(__('Orders/month — Customer'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(10000)
                                            ->helperText(__('Max orders per user per month. 0 = unlimited.')),

                                        TextInput::make('orders_per_month_admin')
                                            ->label(__('Orders/month — Admin'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(10000)
                                            ->helperText(__('Max orders per staff per month. 0 = unlimited.')),

                                        Select::make('order_new_layout')
                                            ->label(__('New-Order Form Layout'))
                                            ->default('cards')
                                            ->options([
                                                'hybrid' => __('order_layout.hybrid'),
                                                'table' => __('order_layout.table'),
                                                'cards' => __('order_layout.cards'),
                                                'wizard' => __('order_layout.wizard'),
                                                'cart' => __('order_layout.cart'),
                                            ]),

                                        Toggle::make('order_form_show_add_test_items')
                                            ->label(__('settings.order_form_show_add_test_items'))
                                            ->helperText(__('settings.order_form_show_add_test_items_help')),

                                        Toggle::make('order_form_show_reset_all')
                                            ->label(__('settings.order_form_show_reset_all'))
                                            ->helperText(__('settings.order_form_show_reset_all_help')),

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

                                        TextInput::make('max_images_per_item')
                                            ->label(__('settings.max_images_per_item'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(20)
                                            ->helperText(__('settings.max_images_per_item_help')),

                                        TextInput::make('max_images_per_order')
                                            ->label(__('settings.max_images_per_order'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->helperText(__('settings.max_images_per_order_help')),

                                        TextInput::make('max_files_per_item_after_submit')
                                            ->label(__('settings.max_files_per_item_after_submit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(20)
                                            ->helperText(__('settings.max_files_per_item_after_submit_help')),

                                        Toggle::make('customer_can_add_files_after_submit')
                                            ->label(__('settings.customer_can_add_files_after_submit'))
                                            ->helperText(__('settings.customer_can_add_files_after_submit_help')),

                                        TextInput::make('comment_max_files')
                                            ->label(__('settings.comment_max_files'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(20)
                                            ->helperText(__('settings.comment_max_files_help')),

                                        TextInput::make('comment_max_file_size_mb')
                                            ->label(__('settings.comment_max_file_size_mb'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->helperText(__('settings.comment_max_file_size_mb_help')),

                                        TextInput::make('payment_notify_order_max_files')
                                            ->label(__('settings.payment_notify_order_max_files'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->helperText(__('settings.payment_notify_order_max_files_help')),

                                        TextInput::make('payment_notify_standalone_max_files')
                                            ->label(__('settings.payment_notify_standalone_max_files'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->helperText(__('settings.payment_notify_standalone_max_files_help')),

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
                                            ->columns(4),
                                    ])
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Shipping & Rates'))
                            ->icon(Heroicon::OutlinedTruck)
                            ->schema([
                                Section::make(__('Shipping Rates'))
                                    ->icon(Heroicon::OutlinedTruck)
                                    ->description(__('settings.shipping_rates_desc'))
                                    ->schema([
                                        \Filament\Schemas\Components\Section::make(__('settings.shipping_aramex_section'))
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
                                                    ->placeholder(__('settings.placeholder_delivery_days'))
                                                    ->helperText(__('Shown on calculator, e.g. "7-10 days"')),
                                            ])
                                            ->columns(4),

                                        \Filament\Schemas\Components\Section::make(__('settings.shipping_dhl_section'))
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
                                                    ->placeholder(__('settings.placeholder_delivery_days')),
                                            ])
                                            ->columns(4),

                                        \Filament\Schemas\Components\Section::make(__('settings.shipping_domestic'))
                                            ->schema([
                                                TextInput::make('domestic_first_half_kg')
                                                    ->label(__('First 0.5 kg (SAR)'))
                                                    ->numeric()->suffix('SAR')->minValue(0),

                                                TextInput::make('domestic_rest_half_kg')
                                                    ->label(__('Each Additional 0.5 kg (SAR)'))
                                                    ->numeric()->suffix('SAR')->minValue(0),

                                                TextInput::make('domestic_delivery_days')
                                                    ->label(__('Est. Delivery'))
                                                    ->placeholder(__('settings.placeholder_delivery_days_short')),
                                            ])
                                            ->columns(3),
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
                                                    ->placeholder(__('settings.placeholder_currency'))
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
                                            ->addActionLabel(__('Add currency'))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Commission & Actions'))
                            ->icon(Heroicon::OutlinedBolt)
                            ->schema([
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
                                            ->onColor('success'),

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
                                            ->onColor('success'),

                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),
                        Tab::make(__('Invoice'))
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Section::make(__('Invoice'))
                                    ->icon(Heroicon::OutlinedDocumentText)
                                    ->description(__('Default filename pattern, comment text, and final invoice copy for generated invoices.'))
                                    ->schema([
                                        TextInput::make('invoice_filename_pattern')
                                            ->label(__('Filename Pattern'))
                                            ->helperText(__('Placeholders: {order_number}, {date}, {type}, {site_name}, {count}. Leave empty for default.'))
                                            ->placeholder(__('settings.placeholder_invoice_filename'))
                                            ->maxLength(120),

                                        TextInput::make('invoice_number_pattern')
                                            ->label(__('orders.invoice_number_pattern'))
                                            ->helperText(__('orders.invoice_number_pattern_help'))
                                            ->placeholder(__('settings.placeholder_invoice_count'))
                                            ->maxLength(80),

                                        Toggle::make('invoice_show_type_label')
                                            ->label(__('orders.invoice_show_type_label'))
                                            ->helperText(__('orders.invoice_show_type_label_help')),

                                        KeyValue::make('invoice_type_labels')
                                            ->label(__('orders.invoice_type_labels'))
                                            ->keyLabel(__('orders.invoice_type'))
                                            ->valueLabel(__('Label'))
                                            ->helperText(__('orders.invoice_type_labels_help'))
                                            ->addActionLabel(__('Add')),

                                        Toggle::make('invoice_show_company_details')
                                            ->label(__('orders.invoice_show_company_details'))
                                            ->helperText(__('orders.invoice_show_company_details_help')),

                                        Repeater::make('invoice_company_details')
                                            ->label(__('orders.invoice_company_details'))
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label(__('Label'))
                                                    ->placeholder(__('orders.invoice_company_address'))
                                                    ->maxLength(100),
                                                TextInput::make('value')
                                                    ->label(__('Value'))
                                                    ->maxLength(500),
                                                Toggle::make('visible')
                                                    ->label(__('Show'))
                                                    ->default(true),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->addActionLabel(__('Add')),

                                        Toggle::make('invoice_show_due_date')
                                            ->label(__('orders.invoice_show_due_date'))
                                            ->helperText(__('orders.invoice_show_due_date_help')),

                                        TextInput::make('invoice_due_date_days')
                                            ->label(__('orders.invoice_due_date_days'))
                                            ->numeric()
                                            ->default(7)
                                            ->minValue(1)
                                            ->maxValue(365),

                                        TextInput::make('invoice_due_date_label')
                                            ->label(__('orders.invoice_due_date_label'))
                                            ->placeholder(__('orders.invoice_due_date'))
                                            ->maxLength(80),

                                        Textarea::make('invoice_comment_default')
                                            ->label(__('Default Comment Message'))
                                            ->helperText(__('Default text when posting invoice as comment. Placeholders: {amount}, {order_number}, {date}, {currency}'))
                                            ->rows(3)
                                            ->placeholder(__('orders.invoice_attached'))
                                            ->maxLength(500),

                                        Textarea::make('invoice_first_payment_comment_template')
                                            ->label(__('orders.invoice_first_payment_comment_template'))
                                            ->helperText(__('orders.invoice_first_payment_comment_template_help'))
                                            ->rows(8)
                                            ->placeholder(__('orders.invoice_first_payment_comment_default'))
                                            ->columnSpanFull(),

                                        TextInput::make('invoice_greeting')
                                            ->label(__('orders.invoice_greeting'))
                                            ->placeholder(__('orders.invoice_greeting_placeholder'))
                                            ->maxLength(120),

                                        TextInput::make('invoice_confirmation')
                                            ->label(__('orders.invoice_confirmation'))
                                            ->placeholder(__('orders.invoice_confirmation_placeholder'))
                                            ->maxLength(200),

                                        Textarea::make('invoice_payment_instructions')
                                            ->label(__('orders.invoice_payment_instructions'))
                                            ->placeholder(__('orders.invoice_payment_instructions_placeholder'))
                                            ->rows(3)
                                            ->maxLength(500),

                                        TextInput::make('invoice_footer_text')
                                            ->label(__('orders.invoice_footer_text'))
                                            ->placeholder(__('orders.invoice_footer_text_placeholder'))
                                            ->maxLength(200),

                                        Toggle::make('invoice_show_order_items')
                                            ->label(__('orders.invoice_show_order_items'))
                                            ->helperText(__('orders.invoice_show_order_items_help')),

                                        Repeater::make('invoice_custom_lines')
                                            ->label(__('orders.invoice_custom_lines'))
                                            ->helperText(__('orders.invoice_custom_lines_help'))
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label(__('orders.invoice_line_label'))
                                                    ->required()
                                                    ->maxLength(200),
                                                TextInput::make('amount')
                                                    ->label(__('orders.amount'))
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),
                                                Toggle::make('visible')
                                                    ->label(__('Show'))
                                                    ->default(true),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->addActionLabel(__('orders.invoice_add_line')),
                                    ])
                                    ->columns(1)
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
            ->id('order-settings-form')
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
        try {
            $data = $this->form->getState();
        } catch (\Throwable $e) {
            // Workaround for Filament Repeater getStateSnapshot() on null bug (#18530).
            // Livewire has already applied updates to $this->data before save() runs.
            $data = $this->data;
        }
        if (empty($data) || ! is_array($data)) {
            $data = $this->data;
        }

        $service = app(SettingsPersistService::class);

        // Sync currencies repeater to Currency table
        $service->syncCurrencies($data['currencies'] ?? []);

        // Build group map and key lists for order-related settings only
        $groupMap = [
            'auto_comment_with_price' => 'orders',
            'auto_comment_no_price' => 'orders',
            'order_success_screen_enabled' => 'orders',
            'order_success_title_ar' => 'orders',
            'order_success_title_en' => 'orders',
            'order_success_subtitle_ar' => 'orders',
            'order_success_subtitle_en' => 'orders',
            'order_success_message_ar' => 'orders',
            'order_success_message_en' => 'orders',
            'order_success_go_to_order_ar' => 'orders',
            'order_success_go_to_order_en' => 'orders',
            'order_success_redirect_prefix_ar' => 'orders',
            'order_success_redirect_prefix_en' => 'orders',
            'order_success_redirect_suffix_ar' => 'orders',
            'order_success_redirect_suffix_en' => 'orders',
            'order_success_redirect_seconds' => 'orders',
            'order_success_screen_threshold' => 'orders',
            'max_products_per_order' => 'orders',
            'order_edit_enabled' => 'orders',
            'order_edit_click_window_minutes' => 'orders',
            'order_edit_resubmit_window_minutes' => 'orders',
            'order_edit_window_minutes' => 'orders',
            'order_new_layout' => 'orders',
            'order_form_show_add_test_items' => 'orders',
            'order_form_show_reset_all' => 'orders',
            'orders_per_hour_customer' => 'orders',
            'orders_per_hour_admin' => 'orders',
            'orders_per_day_staff' => 'orders',
            'orders_per_month_customer' => 'orders',
            'orders_per_month_admin' => 'orders',
            'orders_per_day_customer' => 'orders',
            'max_file_size_mb' => 'orders',
            'max_images_per_item' => 'orders',
            'max_images_per_order' => 'orders',
            'max_files_per_item_after_submit' => 'orders',
            'customer_can_add_files_after_submit' => 'orders',
            'comment_max_files' => 'orders',
            'comment_max_file_size_mb' => 'orders',
            'payment_notify_order_max_files' => 'orders',
            'payment_notify_standalone_max_files' => 'orders',
            'order_form_fields' => 'orders',
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
            'exchange_rates_markup_percent' => 'exchange_rates',
            'exchange_rates_auto_fetch' => 'exchange_rates',
            'commission_threshold_sar' => 'commission',
            'commission_rate_above' => 'commission',
            'commission_flat_below' => 'commission',
            'qa_customer_section' => 'quick_actions',
            'qa_payment_notify' => 'quick_actions',
            'qa_shipping_address_btn' => 'quick_actions',
            'qa_similar_order' => 'quick_actions',
            'qa_customer_merge' => 'quick_actions',
            'qa_customer_cancel' => 'quick_actions',
            'qa_team_section' => 'quick_actions',
            'qa_transfer_order' => 'quick_actions',
            'qa_payment_tracking' => 'quick_actions',
            'qa_shipping_tracking' => 'quick_actions',
            'qa_team_merge' => 'quick_actions',
            'qa_mark_paid' => 'quick_actions',
            'qa_mark_shipped' => 'quick_actions',
            'qa_request_info' => 'quick_actions',
            'qa_cancel_order' => 'quick_actions',
            'invoice_filename_pattern' => 'invoice',
            'invoice_number_pattern' => 'invoice',
            'invoice_show_type_label' => 'invoice',
            'invoice_type_labels' => 'invoice',
            'invoice_show_company_details' => 'invoice',
            'invoice_company_details' => 'invoice',
            'invoice_show_due_date' => 'invoice',
            'invoice_due_date_days' => 'invoice',
            'invoice_due_date_label' => 'invoice',
            'invoice_comment_default' => 'invoice',
            'invoice_first_payment_comment_template' => 'invoice',
            'invoice_greeting' => 'invoice',
            'invoice_confirmation' => 'invoice',
            'invoice_payment_instructions' => 'invoice',
            'invoice_footer_text' => 'invoice',
            'invoice_show_order_items' => 'invoice',
            'invoice_custom_lines' => 'invoice',
        ];

        $booleanKeys = [
            'order_success_screen_enabled', 'order_edit_enabled',
            'order_form_show_add_test_items', 'order_form_show_reset_all',
            'customer_can_add_files_after_submit', 'exchange_rates_auto_fetch',
            'qa_customer_section', 'qa_payment_notify', 'qa_shipping_address_btn',
            'qa_similar_order', 'qa_customer_merge', 'qa_customer_cancel',
            'qa_team_section', 'qa_transfer_order', 'qa_payment_tracking',
            'qa_shipping_tracking', 'qa_team_merge',
            'qa_mark_paid', 'qa_mark_shipped', 'qa_request_info', 'qa_cancel_order',
            'invoice_show_order_items', 'invoice_show_type_label',
            'invoice_show_company_details', 'invoice_show_due_date',
        ];

        $integerKeys = [
            'order_success_redirect_seconds', 'order_success_screen_threshold',
            'order_edit_click_window_minutes', 'order_edit_resubmit_window_minutes',
            'order_edit_window_minutes', 'max_products_per_order',
            'orders_per_hour_customer', 'orders_per_hour_admin', 'orders_per_day_staff',
            'orders_per_month_customer', 'orders_per_month_admin', 'orders_per_day_customer',
            'max_file_size_mb', 'max_images_per_item', 'max_images_per_order',
            'max_files_per_item_after_submit', 'comment_max_files', 'comment_max_file_size_mb',
            'payment_notify_order_max_files', 'payment_notify_standalone_max_files',
            'aramex_first_half_kg', 'aramex_rest_half_kg', 'aramex_over21_per_kg',
            'dhl_first_half_kg', 'dhl_rest_half_kg', 'dhl_over21_per_kg',
            'domestic_first_half_kg', 'domestic_rest_half_kg',
            'invoice_due_date_days',
        ];

        $floatKeys = [
            'exchange_rates_markup_percent',
            'commission_threshold_sar', 'commission_rate_above', 'commission_flat_below',
        ];

        $jsonKeys = ['order_form_fields', 'invoice_custom_lines', 'invoice_type_labels', 'invoice_company_details'];

        $skipKeys = ['exchange_rates', 'currencies'];

        $service->persist(
            $data,
            $groupMap,
            $booleanKeys,
            $integerKeys,
            $floatKeys,
            $jsonKeys,
            $skipKeys
        );

        // Rebuild the exchange_rates JSON blob
        $service->syncExchangeRates($data);

        $this->data = array_merge($this->data, $data);
        $this->data['exchange_rates'] = Setting::get('exchange_rates', []);

        Notification::make()
            ->title(__('Settings saved'))
            ->success()
            ->send();

        // Workaround for Filament Repeater bug: getStateSnapshot() on null when re-rendering
        // after save. Redirect forces full page reload and avoids the error.
        // See: https://github.com/filamentphp/filament/issues/18530
        $url = request()->header('Referer') ?? static::getUrl();

        $this->redirect($url);
    }
}
