<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.guest')]
#[Title('طلب جديد')]
class Caldue1 extends Component
{
    use WithFileUploads;

    // -------------------------------------------------------------------------
    // Order state — items are managed fully in Alpine JS / localStorage.
    // The PHP component only holds settings & handles submission + modal auth.
    // -------------------------------------------------------------------------

    public string $orderNotes = '';

    /** Serialised JSON sent from Alpine on submit */
    public string $productsJson = '[]';

    // File uploads keyed by item index (0-based), max 1 per item, 10 per order
    public array $itemFiles = [];

    // -------------------------------------------------------------------------
    // Settings loaded from DB on mount — passed to Alpine via @js()
    // -------------------------------------------------------------------------
    public int    $maxProducts         = 30;
    public string $defaultCurrency     = 'USD';
    public float  $commissionThreshold = 500.0;
    public float  $commissionPct       = 0.08;
    public float  $commissionFlat      = 50.0;
    public array  $currencies          = [];
    public array  $exchangeRates       = [];

    // -------------------------------------------------------------------------
    // Guest auth modal
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
        $this->maxProducts         = (int)   Setting::get('max_products_per_order', 30);
        $this->defaultCurrency     = (string) Setting::get('default_currency', 'USD');
        $this->commissionThreshold = (float)  Setting::get('commission_threshold', 500);
        $this->commissionPct       = (float)  Setting::get('commission_pct', 0.08);
        $this->commissionFlat      = (float)  Setting::get('commission_flat', 50);
        $this->currencies          = $this->buildCurrencies();
        $this->exchangeRates       = $this->buildExchangeRates();
    }

    // -------------------------------------------------------------------------
    // File upload helper (called from Alpine via $wire.upload)
    // -------------------------------------------------------------------------

    public function updatedItemFiles(mixed $value, string $key): void
    {
        // $key is the array index, e.g. "0", "1"
    }

    // -------------------------------------------------------------------------
    // Order submission
    // -------------------------------------------------------------------------

    public function submitOrder(): void
    {
        if (! Auth::check()) {
            $this->showLoginModal = true;
            $this->dispatch('save-draft-before-modal');
            return;
        }

        $user = Auth::user();

        // Rate limiting (per hour)
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);
        $limit   = $isStaff ? 50 : 10;
        $cacheKey = "order_rate_{$user->id}_" . now()->format('YmdH');
        $count = Cache::get($cacheKey, 0);

        if ($count >= $limit) {
            $this->dispatch('caldue1-notify', type: 'error',
                message: __('order.max_products_reached', ['max' => $limit]));
            return;
        }

        // Decode product rows sent from Alpine
        $rawItems = [];
        if ($this->productsJson && $this->productsJson !== '[]') {
            $rawItems = json_decode($this->productsJson, true) ?? [];
        }

        // Drop empty rows (no URL and no price)
        $filledItems = array_values(array_filter($rawItems, function ($item) {
            return trim($item['url'] ?? '') !== '' || trim($item['price'] ?? '') !== '';
        }));

        if (empty($filledItems)) {
            $this->dispatch('caldue1-notify', type: 'error',
                message: __('order.add_at_least_one_product'));
            return;
        }

        if (count($filledItems) > $this->maxProducts) {
            $this->dispatch('caldue1-notify', type: 'error',
                message: __('order.max_products_reached', ['max' => $this->maxProducts]));
            return;
        }

        $totalFiles = count(array_filter($this->itemFiles));
        if ($totalFiles > 10) {
            $this->dispatch('caldue1-notify', type: 'error',
                message: __('order.max_files_exceeded'));
            return;
        }

        $this->validate($this->validationRules());

        $createdOrder = null;

        DB::transaction(function () use ($filledItems, $rawItems, &$createdOrder) {
            $rates     = $this->exchangeRates;
            $threshold = $this->commissionThreshold;
            $pct       = $this->commissionPct;
            $flat      = $this->commissionFlat;

            $defaultAddress = Auth::user()
                ->addresses()
                ->where('is_default', true)
                ->first();

            $addressSnapshot = $defaultAddress
                ? $defaultAddress->only(['id', 'label', 'recipient_name', 'phone', 'country', 'city', 'address'])
                : null;

            $order = Order::create([
                'order_number'              => $this->generateOrderNumber(),
                'user_id'                   => Auth::id(),
                'status'                    => 'pending',
                'notes'                     => trim($this->orderNotes) ?: null,
                'shipping_address_id'       => $defaultAddress?->id,
                'shipping_address_snapshot' => $addressSnapshot,
                'subtotal'                  => 0,
                'total_amount'              => 0,
                'currency'                  => 'SAR',
                'can_edit_until'            => now()->addMinutes(
                    (int) Setting::get('order_edit_window_minutes', 10)
                ),
            ]);

            $rawSubtotal = 0;

            foreach ($filledItems as $sortOrder => $item) {
                $price = isset($item['price']) && is_numeric($item['price'])
                    ? (float) $item['price']
                    : null;
                $qty  = max(1, (int) ($item['qty'] ?: 1));
                $curr = $item['currency'] ?? 'USD';
                $rate = $rates[$curr] ?? 0;

                if ($price && $rate > 0) {
                    $rawSubtotal += $price * $qty * $rate;
                }

                $orderItem = OrderItem::create([
                    'order_id'   => $order->id,
                    'url'        => $item['url'] ?? '',
                    'is_url'     => (bool) filter_var($item['url'] ?? '', FILTER_VALIDATE_URL),
                    'qty'        => $qty,
                    'color'      => $item['color'] ?: null,
                    'size'       => $item['size'] ?: null,
                    'notes'      => $item['notes'] ?: null,
                    'currency'   => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                // Map file by original row index (Alpine sends originalIndex in the JSON)
                $origIdx = $item['_idx'] ?? $sortOrder;
                if (isset($this->itemFiles[$origIdx]) && $this->itemFiles[$origIdx]) {
                    $file     = $this->itemFiles[$origIdx];
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

            $commission = 0;
            if ($rawSubtotal > 0) {
                $commission = $rawSubtotal >= $threshold
                    ? $rawSubtotal * $pct
                    : $flat;
            }

            $order->update([
                'subtotal'     => round($rawSubtotal, 2),
                'total_amount' => round($rawSubtotal + $commission, 2),
            ]);

            OrderTimeline::create([
                'order_id'  => $order->id,
                'user_id'   => Auth::id(),
                'type'      => 'status_change',
                'status_to' => 'pending',
            ]);

            $createdOrder = $order;
        });

        if ($createdOrder) {
            // Increment rate-limit counter
            Cache::put($cacheKey, $count + 1, 3600);

            UserActivityLog::fromRequest(request(), [
                'user_id'      => Auth::id(),
                'subject_type' => Order::class,
                'subject_id'   => $createdOrder->id,
                'event'        => 'order_created',
                'properties'   => [
                    'order_number' => $createdOrder->order_number,
                    'total_amount' => $createdOrder->total_amount,
                ],
            ]);

            $this->dispatch('clear-draft');
            $this->redirectRoute('orders.show', $createdOrder->id);
        }
    }

    // -------------------------------------------------------------------------
    // Guest modal actions
    // -------------------------------------------------------------------------

    public function checkModalEmail(): void
    {
        $this->resetValidation('modalEmail');
        $this->modalError   = '';
        $this->modalSuccess = '';
        $this->validate(['modalEmail' => 'required|email'], [], ['modalEmail' => __('Email')]);
        $this->modalStep = User::where('email', $this->modalEmail)->exists() ? 'login' : 'register';
    }

    public function loginFromModal(): void
    {
        $this->modalError = '';
        $this->validate([
            'modalEmail'    => 'required|email',
            'modalPassword' => 'required',
        ], [], ['modalEmail' => __('Email'), 'modalPassword' => __('Password')]);

        if (Auth::attempt(['email' => $this->modalEmail, 'password' => $this->modalPassword], true)) {
            $this->showLoginModal = false;
            $this->modalPassword  = '';
            $this->modalSuccess   = __('order.form_data_saved');
            // Submit order after login
            $this->submitOrder();
        } else {
            $this->modalError = __('auth.failed');
        }
    }

    public function registerFromModal(): void
    {
        $this->modalError = '';
        $this->validate([
            'modalEmail'    => 'required|email|unique:users,email',
            'modalPassword' => 'required|min:6',
        ], [], [
            'modalEmail'    => __('Email'),
            'modalPassword' => __('Password'),
        ]);

        $user = User::create([
            'name'     => $this->modalName ?: explode('@', $this->modalEmail)[0],
            'email'    => $this->modalEmail,
            'phone'    => $this->modalPhone ?: null,
            'password' => bcrypt($this->modalPassword),
        ]);

        $user->assignRole('customer');
        Auth::login($user, true);

        $this->showLoginModal = false;
        $this->modalPassword  = '';
        $this->modalSuccess   = __('order.form_data_saved');
        // Submit order after registration
        $this->submitOrder();
    }

    public function sendResetLink(): void
    {
        $this->modalError   = '';
        $this->modalSuccess = '';
        $this->validate(['modalEmail' => 'required|email'], [], ['modalEmail' => __('Email')]);

        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $this->modalEmail]);
        $this->modalSuccess = __('passwords.sent');
        $this->modalStep    = 'login';
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
    // Helpers
    // -------------------------------------------------------------------------

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
        $stored = Setting::get('currencies');
        if (is_array($stored) && count($stored) > 0) {
            // Stored as array of {code, label} or just codes
            $result = [];
            foreach ($stored as $item) {
                if (is_array($item) && isset($item['code'])) {
                    $result[$item['code']] = [
                        'label'  => $item['label'] ?? $item['code'],
                        'symbol' => $item['symbol'] ?? $item['code'],
                    ];
                } elseif (is_string($item)) {
                    $result[$item] = ['label' => $item, 'symbol' => $item];
                }
            }
            if (! empty($result)) {
                $result['OTHER'] = ['label' => __('order.currency_other'), 'symbol' => '—'];
                return $result;
            }
        }

        return [
            'USD'   => ['label' => 'USD – دولار',    'symbol' => '$'],
            'EUR'   => ['label' => 'EUR – يورو',     'symbol' => '€'],
            'GBP'   => ['label' => 'GBP – إسترليني', 'symbol' => '£'],
            'CNY'   => ['label' => 'CNY – يوان',     'symbol' => '¥'],
            'JPY'   => ['label' => 'JPY – ين',       'symbol' => '¥'],
            'KRW'   => ['label' => 'KRW – وون',      'symbol' => '₩'],
            'TRY'   => ['label' => 'TRY – ليرة',     'symbol' => '₺'],
            'SAR'   => ['label' => 'SAR – ريال',     'symbol' => 'ر.س'],
            'AED'   => ['label' => 'AED – درهم',     'symbol' => 'د.إ'],
            'OTHER' => ['label' => __('order.currency_other'), 'symbol' => '—'],
        ];
    }

    private function buildExchangeRates(): array
    {
        $fallback = [
            'SAR' => 1.0, 'USD' => 3.86, 'EUR' => 4.22, 'GBP' => 4.89,
            'CNY' => 0.55, 'JPY' => 0.025, 'KRW' => 0.0027,
            'TRY' => 0.11, 'AED' => 1.05, 'OTHER' => 0,
        ];

        $stored = Setting::get('exchange_rates');
        if (! $stored) {
            return $fallback;
        }

        $parsed = is_array($stored) ? $stored : json_decode($stored, true);
        if (! is_array($parsed)) {
            return $fallback;
        }

        // Format: {"rates": {"USD": {"final": 3.86}, ...}}
        $ratesNode = $parsed['rates'] ?? null;
        if (is_array($ratesNode)) {
            $flat = [];
            foreach ($ratesNode as $code => $data) {
                $flat[$code] = is_array($data) ? (float) ($data['final'] ?? 0) : (float) $data;
            }
            $flat['OTHER'] = 0;
            return $flat;
        }

        // Flat map legacy
        if (isset($parsed['USD']) && ! is_array($parsed['USD'])) {
            return array_map('floatval', $parsed) + ['OTHER' => 0];
        }

        return $fallback;
    }

    private function validationRules(): array
    {
        $maxMb = (int) Setting::get('max_file_size_mb', 2);
        return [
            'orderNotes' => 'nullable|string|max:5000',
            'itemFiles'  => 'nullable|array',
            'itemFiles.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp,pdf,xlsx,xls|max:' . ($maxMb * 1024),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.caldue1');
    }
}
