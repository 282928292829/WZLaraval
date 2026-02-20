<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('New Order')]
class NewOrder extends Component
{
    use WithFileUploads;

    // -------------------------------------------------------------------------
    // Order state
    // -------------------------------------------------------------------------

    /** @var array<int, array{url: string, qty: string, color: string, size: string, price: string, currency: string, notes: string}> */
    public array $items = [];

    public string $orderNotes = '';

    // -------------------------------------------------------------------------
    // File uploads (keyed by item index, max 1 per item, 10 per order)
    // -------------------------------------------------------------------------

    public array $itemFiles = [];

    // -------------------------------------------------------------------------
    // Settings (loaded on mount, passed to JS)
    // -------------------------------------------------------------------------

    public int    $maxProducts     = 30;
    public string $defaultCurrency = 'USD';
    public float  $margin          = 0.03;
    public array  $currencies      = [];
    public array  $exchangeRates   = [];

    // -------------------------------------------------------------------------
    // Field configuration (loaded from settings, drives view rendering)
    // -------------------------------------------------------------------------

    /** All enabled fields sorted by sort_order */
    public array $fieldConfig = [];

    /** Enabled fields that are NOT in the optional section (excl. 'url') */
    public array $requiredFields = [];

    /** Enabled fields in the optional collapsible section */
    public array $optionalFields = [];

    /** Desktop flex-width CSS class per field key */
    public array $desktopWidths = [
        'url'      => 'flex-[3] min-w-0',
        'qty'      => 'w-14 shrink-0 text-center',
        'size'     => 'flex-1 min-w-[70px]',
        'color'    => 'flex-1 min-w-[70px]',
        'price'    => 'flex-1 min-w-[80px]',
        'currency' => 'w-24 shrink-0',
        'notes'    => 'flex-1 min-w-[80px]',
        'file'     => 'w-10 shrink-0',
    ];

    // -------------------------------------------------------------------------
    // Guest login modal
    // -------------------------------------------------------------------------

    public bool   $showLoginModal = false;
    public string $modalStep      = 'email'; // email | login | register | reset
    public string $modalEmail     = '';
    public string $modalPassword  = '';
    public string $modalName      = '';
    public string $modalPhone     = '';
    public string $modalError     = '';
    public string $modalSuccess   = '';

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $this->maxProducts     = (int)   Setting::get('max_products', 30);
        $this->defaultCurrency = (string) Setting::get('default_currency', 'USD');
        $this->margin          = (float)  Setting::get('order_margin', 0.03);
        $this->currencies      = $this->buildCurrencies();
        $this->exchangeRates   = $this->buildExchangeRates();
        $this->loadFieldConfig();

        // Start with one empty item; pre-fill URL if passed from hero form
        $item = $this->emptyItem($this->defaultCurrency);
        $prefill = request()->query('product_url', '');
        if ($prefill !== '') {
            $item['url'] = substr(trim($prefill), 0, 2000);
        }
        $this->items[] = $item;
    }

    // -------------------------------------------------------------------------
    // Item management
    // -------------------------------------------------------------------------

    public function addItem(string $currency = ''): void
    {
        $filledCount = count(array_filter($this->items, fn($i) => trim($i['url']) !== ''));

        if ($filledCount >= $this->maxProducts) {
            $this->dispatch('notify', type: 'error', message: __('order.max_products_reached', ['max' => $this->maxProducts]));
            return;
        }

        $this->items[] = $this->emptyItem($currency ?: $this->defaultCurrency);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        unset($this->itemFiles[$index]);

        $this->items     = array_values($this->items);
        $this->itemFiles = array_values($this->itemFiles);
    }

    // -------------------------------------------------------------------------
    // Order submission
    // -------------------------------------------------------------------------

    public function submitOrder(): void
    {
        if (! Auth::check()) {
            $this->showLoginModal = true;
            return;
        }

        $this->validate($this->validationRules());

        // Strip empty rows (no URL)
        $filledItems = array_values(array_filter($this->items, fn($i) => trim($i['url']) !== ''));

        if (empty($filledItems)) {
            $this->dispatch('notify', type: 'error', message: __('order.add_at_least_one_product'));
            return;
        }

        // Track original indices for file upload mapping
        $itemsWithOriginalIndex = [];
        foreach ($this->items as $originalIndex => $item) {
            if (trim($item['url']) !== '') {
                $itemsWithOriginalIndex[] = ['data' => $item, 'orig' => $originalIndex];
            }
        }

        $totalFiles = count(array_filter($this->itemFiles));
        if ($totalFiles > 10) {
            $this->dispatch('notify', type: 'error', message: __('order.max_files_exceeded'));
            return;
        }

        DB::transaction(function () use ($itemsWithOriginalIndex) {
            $rates  = $this->exchangeRates;
            $margin = $this->margin;

            $order = Order::create([
                'order_number'   => $this->generateOrderNumber(),
                'user_id'        => Auth::id(),
                'status'         => 'pending',
                'layout_option'  => Setting::get('order_new_layout', '1'),
                'notes'          => trim($this->orderNotes) ?: null,
                'subtotal'       => 0,
                'total_amount'   => 0,
                'currency'       => 'SAR',
                'can_edit_until' => now()->addMinutes((int) Setting::get('order_modification_minutes', 60)),
            ]);

            $subtotal = 0;

            foreach ($itemsWithOriginalIndex as $sortOrder => $entry) {
                $item       = $entry['data'];
                $origIndex  = $entry['orig'];

                $price = is_numeric($item['price']) ? (float) $item['price'] : null;
                $qty   = max(1, (int) ($item['qty'] ?: 1));
                $curr  = $item['currency'] ?? 'USD';
                $rate  = $rates[$curr] ?? 0;

                if ($price && $rate > 0) {
                    $subtotal += $price * $qty * $rate * (1 + $margin);
                }

                $orderItem = OrderItem::create([
                    'order_id'   => $order->id,
                    'url'        => $item['url'],
                    'is_url'     => (bool) filter_var($item['url'], FILTER_VALIDATE_URL),
                    'qty'        => $qty,
                    'color'      => $item['color'] ?: null,
                    'size'       => $item['size'] ?: null,
                    'notes'      => $item['notes'] ?: null,
                    'currency'   => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                // File upload for this item
                if (isset($this->itemFiles[$origIndex]) && $this->itemFiles[$origIndex]) {
                    $file     = $this->itemFiles[$origIndex];
                    $path     = $file->store("orders/{$order->id}", 'public');
                    $origName = $file->getClientOriginalName();
                    $mime     = $file->getMimeType();
                    $size     = $file->getSize();

                    $orderItem->update(['image_path' => $path]);

                    OrderFile::create([
                        'order_id'      => $order->id,
                        'user_id'       => Auth::id(),
                        'path'          => $path,
                        'original_name' => $origName,
                        'mime_type'     => $mime,
                        'size'          => $size,
                        'type'          => 'product_image',
                    ]);
                }
            }

            $order->update([
                'subtotal'     => round($subtotal, 2),
                'total_amount' => round($subtotal, 2),
            ]);

            OrderTimeline::create([
                'order_id'  => $order->id,
                'user_id'   => Auth::id(),
                'type'      => 'status_change',
                'status_to' => 'pending',
            ]);

            $this->dispatch('order-created', orderNumber: $order->order_number);
            $this->redirectRoute('orders.show', $order->id);
        });
    }

    // -------------------------------------------------------------------------
    // Guest login modal actions
    // -------------------------------------------------------------------------

    public function checkModalEmail(): void
    {
        $this->resetValidation('modalEmail');
        $this->modalError   = '';
        $this->modalSuccess = '';

        $this->validate(['modalEmail' => 'required|email'], [], ['modalEmail' => __('Email')]);

        $exists          = User::where('email', $this->modalEmail)->exists();
        $this->modalStep = $exists ? 'login' : 'register';
    }

    public function loginFromModal(): void
    {
        $this->modalError = '';

        $this->validate([
            'modalEmail'    => 'required|email',
            'modalPassword' => 'required',
        ], [], [
            'modalEmail'    => __('Email'),
            'modalPassword' => __('Password'),
        ]);

        if (Auth::attempt(['email' => $this->modalEmail, 'password' => $this->modalPassword], true)) {
            $this->showLoginModal = false;
            $this->modalPassword  = '';
            $this->submitOrder();
        } else {
            $this->modalError = __('auth.failed');
        }
    }

    public function registerFromModal(): void
    {
        $this->modalError = '';

        $this->validate([
            'modalName'     => 'required|string|max:255',
            'modalEmail'    => 'required|email|unique:users,email',
            'modalPassword' => 'required|min:8',
        ], [], [
            'modalName'     => __('Name'),
            'modalEmail'    => __('Email'),
            'modalPassword' => __('Password'),
        ]);

        $user = User::create([
            'name'     => $this->modalName,
            'email'    => $this->modalEmail,
            'phone'    => $this->modalPhone ?: null,
            'password' => bcrypt($this->modalPassword),
        ]);

        $user->assignRole('customer');
        Auth::login($user, true);

        $this->showLoginModal = false;
        $this->modalPassword  = '';
        $this->submitOrder();
    }

    public function setModalStep(string $step): void
    {
        $this->modalStep    = $step;
        $this->modalError   = '';
        $this->modalSuccess = '';
    }

    public function closeModal(): void
    {
        $this->showLoginModal = false;
        $this->modalStep      = 'email';
        $this->modalError     = '';
        $this->modalSuccess   = '';
        $this->modalPassword  = '';
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    #[Computed]
    public function filledCount(): int
    {
        return count(array_filter($this->items, fn($i) => trim($i['url']) !== ''));
    }

    #[Computed]
    public function canAddMore(): bool
    {
        return $this->filledCount() < $this->maxProducts;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function loadFieldConfig(): void
    {
        $stored = Setting::get('order_form_fields');
        $raw    = is_array($stored) ? $stored : $this->defaultFieldConfig();

        $all = collect($raw)
            ->where('enabled', true)
            ->sortBy('sort_order')
            ->values()
            ->toArray();

        $this->fieldConfig    = $all;
        $this->requiredFields = collect($all)->where('optional', false)->where('key', '!=', 'url')->values()->toArray();
        $this->optionalFields = collect($all)->where('optional', true)->values()->toArray();
    }

    private function defaultFieldConfig(): array
    {
        return [
            ['key' => 'url',      'label_ar' => 'رابط المنتج أو وصفه', 'label_en' => 'Product URL or Description', 'sort_order' => 1, 'optional' => false, 'enabled' => true],
            ['key' => 'qty',      'label_ar' => 'الكمية',               'label_en' => 'Qty',                        'sort_order' => 2, 'optional' => false, 'enabled' => true],
            ['key' => 'size',     'label_ar' => 'المقاس',               'label_en' => 'Size',                       'sort_order' => 3, 'optional' => false, 'enabled' => true],
            ['key' => 'color',    'label_ar' => 'اللون',                'label_en' => 'Color',                      'sort_order' => 4, 'optional' => false, 'enabled' => true],
            ['key' => 'price',    'label_ar' => 'السعر',                'label_en' => 'Price',                      'sort_order' => 5, 'optional' => false, 'enabled' => true],
            ['key' => 'currency', 'label_ar' => 'العملة',               'label_en' => 'Currency',                   'sort_order' => 6, 'optional' => false, 'enabled' => true],
            ['key' => 'notes',    'label_ar' => 'ملاحظات',              'label_en' => 'Notes',                      'sort_order' => 7, 'optional' => true,  'enabled' => true],
            ['key' => 'file',     'label_ar' => 'ملف/صورة',             'label_en' => 'File / Image',               'sort_order' => 8, 'optional' => true,  'enabled' => true],
        ];
    }

    private function emptyItem(string $currency = 'USD'): array
    {
        return [
            'url'      => '',
            'qty'      => '1',
            'color'    => '',
            'size'     => '',
            'price'    => '',
            'currency' => $currency,
            'notes'    => '',
        ];
    }

    private function generateOrderNumber(): string
    {
        $prefix = strtoupper(substr(config('app.name', 'WZ'), 0, 2));
        $year   = date('y');
        $count  = Order::whereYear('created_at', date('Y'))->count() + 1;
        $seq    = str_pad($count, 5, '0', STR_PAD_LEFT);

        return "{$prefix}{$year}{$seq}";
    }

    private function buildCurrencies(): array
    {
        return [
            'USD'   => ['label' => 'USD', 'symbol' => '$'],
            'EUR'   => ['label' => 'EUR', 'symbol' => '€'],
            'GBP'   => ['label' => 'GBP', 'symbol' => '£'],
            'CNY'   => ['label' => 'CNY', 'symbol' => '¥'],
            'JPY'   => ['label' => 'JPY', 'symbol' => '¥'],
            'KRW'   => ['label' => 'KRW', 'symbol' => '₩'],
            'TRY'   => ['label' => 'TRY', 'symbol' => '₺'],
            'SAR'   => ['label' => 'SAR', 'symbol' => 'ر.س'],
            'AED'   => ['label' => 'AED', 'symbol' => 'د.إ'],
            'OTHER' => ['label' => __('order.currency_other'), 'symbol' => '—'],
        ];
    }

    private function buildExchangeRates(): array
    {
        $stored = Setting::get('exchange_rates');

        if ($stored) {
            $parsed = is_array($stored) ? $stored : json_decode($stored, true);
            if (is_array($parsed) && count($parsed)) {
                return $parsed;
            }
        }

        return [
            'SAR'   => 1.0,
            'USD'   => 3.86,
            'EUR'   => 4.22,
            'GBP'   => 4.89,
            'CNY'   => 0.55,
            'JPY'   => 0.025,
            'KRW'   => 0.0027,
            'TRY'   => 0.11,
            'AED'   => 1.05,
            'OTHER' => 0,
        ];
    }

    private function validationRules(): array
    {
        $currencyList = implode(',', array_keys($this->currencies));

        return [
            'orderNotes'       => 'nullable|string|max:5000',
            'items'            => 'required|array|min:1',
            'items.*.url'      => 'nullable|string|max:2000',
            'items.*.qty'      => 'nullable|integer|min:1|max:9999',
            'items.*.color'    => 'nullable|string|max:100',
            'items.*.size'     => 'nullable|string|max:100',
            'items.*.price'    => 'nullable|numeric|min:0',
            'items.*.currency' => "nullable|string|in:{$currencyList}",
            'items.*.notes'    => 'nullable|string|max:1000',
            'itemFiles'        => 'nullable|array',
            'itemFiles.*'      => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp,pdf,xlsx,xls|max:2048',
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.new-order');
    }
}
