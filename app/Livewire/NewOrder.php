<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\AdCampaign;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\CommissionCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.order')]
class NewOrder extends Component
{
    use WithFileUploads;

    public array $items = [];

    public string $orderNotes = '';

    public array $itemFiles = [];

    public int $maxProducts = 30;

    public string $defaultCurrency = 'USD';

    public array $currencies = [];

    public array $exchangeRates = [];

    /** Set by ?duplicate_from={id} — triggers pre-fill in mount() */
    public ?int $duplicateFrom = null;

    /** Set by ?edit={id} — editing existing order; triggers pre-fill and update flow */
    public ?int $editingOrderId = null;

    /** Order number when editing (for display) */
    public string $editingOrderNumber = '';

    /** Set by ?product_url=... — pre-fills the first item's URL field */
    public string $productUrl = '';

    /** Triggers full-page success screen after order creation */
    public bool $showSuccessScreen = false;

    public ?int $createdOrderId = null;

    public string $createdOrderNumber = '';

    // Guest login modal
    public bool $showLoginModal = false;

    /** 'submit' | 'attach' — controls modal copy and post-login action */
    public string $loginModalReason = 'submit';

    public string $modalStep = 'email';

    public string $modalEmail = '';

    public string $modalPassword = '';

    public string $modalName = '';

    public string $modalPhone = '';

    public string $modalError = '';

    public string $modalSuccess = '';

    public function mount(?int $duplicate_from = null, ?int $edit = null, string $product_url = ''): void
    {
        $this->maxProducts = (int) Setting::get('max_products_per_order', 30);
        $this->defaultCurrency = (string) Setting::get('default_currency', 'USD');
        $this->currencies = $this->buildCurrencies();
        $this->exchangeRates = $this->buildExchangeRates();

        // Edit mode: ?edit={id} — must be logged in, load order, start resubmit window
        $editId = $edit ?? (int) request()->query('edit', 0);
        if ($editId && Auth::check()) {
            $this->prefillFromEdit($editId);

            return;
        }

        if ($duplicate_from && Auth::check()) {
            $this->prefillFromDuplicate($duplicate_from);
        } elseif ($product_url !== '') {
            $this->productUrl = $product_url;
            // Pre-fill the first item with the provided URL
            $firstItem = $this->emptyItem($this->defaultCurrency);
            $firstItem['url'] = $product_url;
            $this->items = [$firstItem];
        }
    }

    /**
     * Pre-fill from order being edited. Validates: owner, unpaid, within click window.
     * Sets can_edit_until on order to start the resubmit window.
     */
    private function prefillFromEdit(int $orderId): void
    {
        $user = Auth::user();
        $orderEditEnabled = (bool) Setting::get('order_edit_enabled', true);
        $clickWindowMinutes = (int) Setting::get('order_edit_click_window_minutes', 10);
        $resubmitWindowMinutes = (int) Setting::get('order_edit_resubmit_window_minutes', 10);

        if (! $orderEditEnabled) {
            $this->redirectToOrderWithError($orderId, __('orders.edit_disabled'));

            return;
        }

        $order = Order::with('items')->find($orderId);

        if (! $order || $order->user_id !== $user->id) {
            $this->redirectToOrderWithError($orderId, __('orders.edit_forbidden'));

            return;
        }

        if ($order->is_paid) {
            $this->redirectToOrderWithError($orderId, __('orders.edit_already_paid'));

            return;
        }

        $clickEditDeadline = $order->created_at->copy()->addMinutes($clickWindowMinutes);
        if (now()->gte($clickEditDeadline)) {
            $this->redirectToOrderWithError($orderId, __('orders.edit_click_window_expired'));

            return;
        }

        // Start resubmit window: set can_edit_until = now + resubmit_window_minutes
        $order->update(['can_edit_until' => now()->addMinutes($resubmitWindowMinutes)]);

        $this->editingOrderId = $order->id;
        $this->editingOrderNumber = $order->order_number;
        $this->orderNotes = (string) ($order->notes ?? '');

        $this->items = $order->items->map(fn ($item) => [
            'url' => (string) ($item->url ?? ''),
            'qty' => (string) max(1, (int) $item->qty),
            'color' => (string) ($item->color ?? ''),
            'size' => (string) ($item->size ?? ''),
            'price' => $item->unit_price !== null ? (string) $item->unit_price : '',
            'currency' => (string) ($item->currency ?? $this->defaultCurrency),
            'notes' => (string) ($item->notes ?? ''),
        ])->values()->toArray();

        if (empty($this->items)) {
            $this->items = [$this->emptyItem($this->defaultCurrency)];
        }
    }

    private function redirectToOrderWithError(int $orderId, string $message): void
    {
        session()->flash('error', $message);
        $this->redirect(route('orders.show', $orderId));
    }

    /**
     * Pre-fill the form from an existing order the user owns (or staff can access).
     */
    private function prefillFromDuplicate(int $orderId): void
    {
        $user = Auth::user();
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        $order = Order::with('items')
            ->where('id', $orderId)
            ->when(! $isStaff, fn ($q) => $q->where('user_id', $user->id))
            ->first();

        if (! $order) {
            return;
        }

        $this->duplicateFrom = $orderId;
        $this->orderNotes = (string) ($order->notes ?? '');

        $this->items = $order->items->map(fn ($item) => [
            'url' => (string) ($item->url ?? ''),
            'qty' => (string) max(1, (int) $item->qty),
            'color' => (string) ($item->color ?? ''),
            'size' => (string) ($item->size ?? ''),
            'price' => '',
            'currency' => (string) ($item->currency ?? $this->defaultCurrency),
            'notes' => (string) ($item->notes ?? ''),
        ])->values()->toArray();
    }

    public function addItem(string $currency = ''): void
    {
        if (count($this->items) >= $this->maxProducts) {
            $this->dispatch('notify', type: 'error', message: __('opus46.max_products', ['max' => $this->maxProducts]));

            return;
        }
        $this->items[] = $this->emptyItem($currency ?: $this->defaultCurrency);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->itemFiles[$index]);
        $this->items = array_values($this->items);
        $this->itemFiles = array_values($this->itemFiles);
    }

    public function shiftFileIndex(int $removedIndex): void
    {
        $shifted = [];
        foreach ($this->itemFiles as $idx => $file) {
            if ($idx === $removedIndex) {
                continue;
            }
            $shifted[$idx > $removedIndex ? $idx - 1 : $idx] = $file;
        }
        $this->itemFiles = $shifted;
    }

    // -------------------------------------------------------------------------
    // Order submission — real database writes
    // -------------------------------------------------------------------------

    public function submitOrder(): void
    {
        if (! Auth::check()) {
            $this->loginModalReason = 'submit';
            $this->showLoginModal = true;

            return;
        }

        $user = Auth::user();
        $isStaff = $user->hasAnyRole(['editor', 'admin', 'superadmin']);

        // Hourly rate limit
        $hourlyLimit = $isStaff
            ? (int) Setting::get('orders_per_hour_admin', 50)
            : (int) Setting::get('orders_per_hour_customer', 10);

        if ($hourlyLimit > 0) {
            $hourlyCount = Order::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($hourlyCount >= $hourlyLimit) {
                $this->dispatch('notify', type: 'error',
                    message: __('order.rate_limit_exceeded', ['max' => $hourlyLimit]));

                return;
            }
        }

        if (! $isStaff) {
            $maxPerDay = (int) Setting::get('max_orders_per_day', 5);
            if ($maxPerDay > 0) {
                $todayCount = Order::where('user_id', $user->id)
                    ->whereDate('created_at', today())
                    ->count();

                if ($todayCount >= $maxPerDay) {
                    $this->dispatch('notify', type: 'error',
                        message: __('order.daily_limit_reached', ['max' => $maxPerDay]));

                    return;
                }
            }
        }

        $this->validate($this->validationRules());

        $itemsWithOriginalIndex = [];
        foreach ($this->items as $originalIndex => $item) {
            $hasContent = trim($item['url'] ?? '') !== ''
                || trim($item['color'] ?? '') !== ''
                || trim($item['size'] ?? '') !== ''
                || trim($item['notes'] ?? '') !== ''
                || (is_numeric($item['price'] ?? null) && (float) $item['price'] > 0);
            if ($hasContent) {
                $itemsWithOriginalIndex[] = ['data' => $item, 'orig' => $originalIndex];
            }
        }

        $totalFiles = count(array_filter($this->itemFiles));
        if ($totalFiles > 10) {
            $this->dispatch('notify', type: 'error', message: __('order.max_files_exceeded'));

            return;
        }

        // Edit flow: update existing order
        if ($this->editingOrderId) {
            $this->submitOrderEdit($itemsWithOriginalIndex);

            return;
        }

        $createdOrder = null;

        DB::transaction(function () use ($itemsWithOriginalIndex, &$createdOrder) {
            $rates = $this->exchangeRates;

            $defaultAddress = Auth::user()
                ->addresses()
                ->where('is_default', true)
                ->first();

            $addressSnapshot = $defaultAddress
                ? $defaultAddress->only([
                    'id', 'label', 'recipient_name', 'phone',
                    'country', 'city', 'address',
                ])
                : null;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => Auth::id(),
                'status' => 'pending',
                'layout_option' => Setting::get('order_new_layout', '1'),
                'notes' => trim($this->orderNotes) ?: null,
                'shipping_address_id' => $defaultAddress?->id,
                'shipping_address_snapshot' => $addressSnapshot,
                'subtotal' => 0,
                'total_amount' => 0,
                'currency' => 'SAR',
                'can_edit_until' => null,
            ]);

            $rawSubtotal = 0;

            foreach ($itemsWithOriginalIndex as $sortOrder => $entry) {
                $item = $entry['data'];
                $origIndex = $entry['orig'];

                $price = is_numeric($item['price']) ? (float) $item['price'] : null;
                $qty = max(1, (int) ($item['qty'] ?: 1));
                $curr = $item['currency'] ?? 'USD';
                $rate = $rates[$curr] ?? 0;

                if ($price && $rate > 0) {
                    $rawSubtotal += $price * $qty * $rate;
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'url' => $item['url'],
                    'is_url' => (bool) filter_var($item['url'], FILTER_VALIDATE_URL),
                    'qty' => $qty,
                    'color' => $item['color'] ?: null,
                    'size' => $item['size'] ?: null,
                    'notes' => $item['notes'] ?: null,
                    'currency' => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                if (isset($this->itemFiles[$origIndex]) && $this->itemFiles[$origIndex]) {
                    $file = $this->itemFiles[$origIndex];
                    $path = $file->store("orders/{$order->id}", 'public');
                    $origName = $file->getClientOriginalName();
                    $mime = $file->getMimeType();
                    $size = $file->getSize();

                    $orderItem->update(['image_path' => $path]);

                    OrderFile::create([
                        'order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'path' => $path,
                        'original_name' => $origName,
                        'mime_type' => $mime,
                        'size' => $size,
                        'type' => 'product_image',
                    ]);
                }
            }

            $commission = CommissionCalculator::calculate($rawSubtotal);

            $order->update([
                'subtotal' => round($rawSubtotal, 2),
                'total_amount' => round($rawSubtotal + $commission, 2),
            ]);

            OrderTimeline::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'type' => 'status_change',
                'status_to' => 'pending',
            ]);

            $createdOrder = $order;
        });

        if ($createdOrder) {
            $this->insertSystemComment($createdOrder);

            if (app()->environment('local')) {
                $this->insertDevComments($createdOrder);
            }

            Activity::create([
                'type' => 'new_order',
                'subject_type' => Order::class,
                'subject_id' => $createdOrder->id,
                'causer_id' => Auth::id(),
                'data' => [
                    'order_number' => $createdOrder->order_number,
                    'note' => null,
                ],
                'created_at' => now(),
            ]);

            // Increment campaign order count if the user was attributed to a campaign
            $campaignId = Auth::user()?->ad_campaign_id;
            if ($campaignId) {
                AdCampaign::where('id', $campaignId)->increment('order_count');
            }

            UserActivityLog::fromRequest(request(), [
                'user_id' => Auth::id(),
                'subject_type' => Order::class,
                'subject_id' => $createdOrder->id,
                'event' => 'order_created',
                'properties' => [
                    'order_number' => $createdOrder->order_number,
                    'total_amount' => $createdOrder->total_amount,
                ],
            ]);

            $this->createdOrderId = $createdOrder->id;
            $this->createdOrderNumber = $createdOrder->order_number;

            // Count total orders for this user (to decide success screen vs toast)
            $totalOrders = Order::where('user_id', Auth::id())->count();

            if ($totalOrders <= 1000) {
                // Full success screen for first 1000 orders
                $this->showSuccessScreen = true;
            } else {
                // Toast + immediate redirect for experienced users
                session()->flash('success', __('order.created_successfully', [
                    'number' => $createdOrder->order_number,
                ]));
                $this->redirectRoute('orders.show', $createdOrder->id);
            }
        }
    }

    /**
     * Update existing order (edit flow). Validates resubmit window, rejects empty items.
     */
    private function submitOrderEdit(array $itemsWithOriginalIndex): void
    {
        $order = Order::with('items')->find($this->editingOrderId);

        if (! $order || $order->user_id !== Auth::id()) {
            $this->dispatch('notify', type: 'error', message: __('orders.edit_forbidden'));

            return;
        }

        if ($order->is_paid) {
            $this->dispatch('notify', type: 'error', message: __('orders.edit_already_paid'));

            return;
        }

        if ($order->can_edit_until === null || now()->gte($order->can_edit_until)) {
            $this->dispatch('notify', type: 'error', message: __('orders.edit_resubmit_window_expired'));

            return;
        }

        if (empty($itemsWithOriginalIndex)) {
            $this->dispatch('notify', type: 'error', message: __('orders.edit_empty_items_rejected'));

            return;
        }

        $rates = $this->exchangeRates;

        DB::transaction(function () use ($order, $itemsWithOriginalIndex, $rates) {
            $order->items()->delete();

            $rawSubtotal = 0;

            foreach ($itemsWithOriginalIndex as $sortOrder => $entry) {
                $item = $entry['data'];
                $origIndex = $entry['orig'];

                $price = is_numeric($item['price']) ? (float) $item['price'] : null;
                $qty = max(1, (int) ($item['qty'] ?: 1));
                $curr = $item['currency'] ?? 'USD';
                $rate = $rates[$curr] ?? 0;

                if ($price && $rate > 0) {
                    $rawSubtotal += $price * $qty * $rate;
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'url' => $item['url'],
                    'is_url' => (bool) filter_var($item['url'], FILTER_VALIDATE_URL),
                    'qty' => $qty,
                    'color' => $item['color'] ?: null,
                    'size' => $item['size'] ?: null,
                    'notes' => $item['notes'] ?: null,
                    'currency' => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                if (isset($this->itemFiles[$origIndex]) && $this->itemFiles[$origIndex]) {
                    $file = $this->itemFiles[$origIndex];
                    $path = $file->store("orders/{$order->id}", 'public');
                    $origName = $file->getClientOriginalName();
                    $mime = $file->getMimeType();
                    $size = $file->getSize();

                    $orderItem->update(['image_path' => $path]);

                    OrderFile::create([
                        'order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'path' => $path,
                        'original_name' => $origName,
                        'mime_type' => $mime,
                        'size' => $size,
                        'type' => 'product_image',
                    ]);
                }
            }

            $commission = CommissionCalculator::calculate($rawSubtotal);

            $order->update([
                'notes' => trim($this->orderNotes) ?: null,
                'subtotal' => round($rawSubtotal, 2),
                'total_amount' => round($rawSubtotal + $commission, 2),
                'can_edit_until' => null,
            ]);

            OrderTimeline::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'type' => 'note',
                'body' => __('orders.timeline_items_edited'),
            ]);

            OrderComment::create([
                'order_id' => $order->id,
                'user_id' => null,
                'body' => __('orders.edit_system_comment'),
                'is_system' => true,
            ]);
        });

        session()->flash('success', __('orders.edit_saved_successfully', ['number' => $order->order_number]));
        $this->redirect(route('orders.show', $order->id));
    }

    /**
     * Insert the automatic system comment immediately after order creation,
     * mirroring WordPress behaviour: if prices were provided, show the
     * calculated breakdown; otherwise tell the customer we'll calculate later.
     */
    private function insertSystemComment(Order $order): void
    {
        $hasPrices = $order->subtotal > 0;

        $siteName = Setting::get('site_name') ?: config('app.name');
        $whatsapp = Setting::get('whatsapp', '');
        $whatsappDisplay = $whatsapp ?: '-';

        if ($hasPrices) {
            $commission = $order->total_amount - $order->subtotal;

            $body = __('orders.auto_comment_with_price', [
                'subtotal' => number_format($order->subtotal, 0, '.', ','),
                'commission' => number_format($commission, 0, '.', ','),
                'total' => number_format($order->total_amount, 0, '.', ','),
                'site_name' => $siteName,
                'whatsapp' => $whatsappDisplay,
            ]);
        } else {
            $body = __('orders.auto_comment_no_price', [
                'whatsapp' => $whatsappDisplay,
            ]);
        }

        OrderComment::create([
            'order_id' => $order->id,
            'user_id' => null,
            'body' => $body,
            'is_system' => true,
        ]);
    }

    /**
     * Dev only: add 20 back-and-forth comments with images on some (every 4th).
     * Used for packing-order testing.
     */
    private function insertDevComments(Order $order): void
    {
        $customer = $order->user;
        $staff = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['editor', 'admin', 'superadmin']))
            ->first() ?? $customer;

        $messages = [
            'Hi, I just placed this order. Please confirm you received it.',
            'Order received! We will start processing within 24 hours.',
            'Can you check if these items are in stock?',
            'All items are in stock. We will proceed with the purchase.',
            'I need this by next week if possible.',
            'We will do our best to meet your deadline.',
            'Please use the exact colors I specified.',
            'Noted on the colors. We will match exactly.',
            'Is there any discount available for bulk order?',
            'Let me check with the team and get back to you.',
            'I added one more item - the blue one. Thanks!',
            'Got it! The blue item has been added.',
            'When will you start processing?',
            'Processing has started. You will get updates soon.',
            'I sent the payment via bank transfer. Reference: TXN123.',
            'Payment received. Thank you! Order is now confirmed.',
            'Can you combine shipping with my previous order?',
            'We can combine shipping. I will merge the orders.',
            'Please pack carefully - these are fragile.',
            'We use premium packaging for fragile items.',
        ];

        $withImage = [4, 8, 12, 16, 20];
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        for ($i = 0; $i < 20; $i++) {
            $isCustomer = ($i % 2) === 0;
            $author = $isCustomer ? $customer : $staff;
            $body = $messages[$i % count($messages)];

            $comment = OrderComment::create([
                'order_id' => $order->id,
                'user_id' => $author->id,
                'body' => $body,
                'is_internal' => false,
            ]);

            if (in_array($i + 1, $withImage, true) && $png !== false) {
                $path = "orders/{$order->id}/comment-{$comment->id}-".uniqid().'.png';
                Storage::disk('public')->put($path, $png);
                OrderFile::create([
                    'order_id' => $order->id,
                    'user_id' => $author->id,
                    'comment_id' => $comment->id,
                    'path' => $path,
                    'original_name' => 'attachment.png',
                    'mime_type' => 'image/png',
                    'size' => 100,
                    'type' => 'comment',
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Guest login modal actions
    // -------------------------------------------------------------------------

    public function checkModalEmail(): void
    {
        $this->resetValidation('modalEmail');
        $this->modalError = '';
        $this->modalSuccess = '';

        $this->validate(['modalEmail' => 'required|email'], [], ['modalEmail' => __('Email')]);

        $exists = User::where('email', $this->modalEmail)->exists();
        $this->modalStep = $exists ? 'login' : 'register';
    }

    public function loginFromModal(): void
    {
        $this->modalError = '';

        $this->validate([
            'modalEmail' => 'required|email',
            'modalPassword' => 'required',
        ], [], [
            'modalEmail' => __('Email'),
            'modalPassword' => __('Password'),
        ]);

        if (Auth::attempt(['email' => $this->modalEmail, 'password' => $this->modalPassword], true)) {
            $this->showLoginModal = false;
            $this->modalPassword = '';
            if ($this->loginModalReason === 'attach') {
                $this->loginModalReason = 'submit';

                return;
            }
            $this->submitOrder();
        } else {
            $this->modalError = __('auth.failed');
        }
    }

    public function registerFromModal(): void
    {
        $this->modalError = '';

        $this->validate([
            'modalEmail' => 'required|email|unique:users,email',
            'modalPassword' => 'required|min:4',
        ], [], [
            'modalEmail' => __('Email'),
            'modalPassword' => __('Password'),
        ]);

        $name = strstr($this->modalEmail, '@', true) ?: 'Customer';
        $name = trim($name) !== '' ? $name : 'Customer';

        $user = User::create([
            'name' => $name,
            'email' => $this->modalEmail,
            'phone' => null,
            'password' => bcrypt($this->modalPassword),
        ]);

        $user->assignRole('customer');
        Auth::login($user, true);

        $this->showLoginModal = false;
        $this->modalPassword = '';
        if ($this->loginModalReason === 'attach') {
            $this->loginModalReason = 'submit';

            return;
        }
        $this->submitOrder();
    }

    public function sendModalResetLink(): void
    {
        $this->modalError = '';
        $this->modalSuccess = '';

        $this->validate(['modalEmail' => 'required|email'], [], ['modalEmail' => __('Email')]);

        $status = Password::sendResetLink(['email' => $this->modalEmail]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->modalSuccess = __('opus46.reset_link_sent');
        } else {
            $this->modalError = __('passwords.user');
        }
    }

    public function setModalStep(string $step): void
    {
        $this->modalStep = $step;
        $this->modalError = '';
        $this->modalSuccess = '';
    }

    public function openLoginModalForAttach(): void
    {
        $this->loginModalReason = 'attach';
        $this->showLoginModal = true;
        $this->modalStep = 'email';
        $this->modalError = '';
        $this->modalSuccess = '';
    }

    public function closeModal(): void
    {
        $this->showLoginModal = false;
        $this->loginModalReason = 'submit';
        $this->modalStep = 'email';
        $this->modalEmail = '';
        $this->modalPassword = '';
        $this->modalError = '';
        $this->modalSuccess = '';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function emptyItem(string $currency = 'USD'): array
    {
        return [
            'url' => '',
            'qty' => '1',
            'color' => '',
            'size' => '',
            'price' => '',
            'currency' => $currency,
            'notes' => '',
        ];
    }

    private function generateOrderNumber(): string
    {
        $query = Order::query()->lockForUpdate();

        if (DB::getDriverName() === 'mysql') {
            $max = (int) $query
                ->whereRaw("order_number REGEXP '^[0-9]+$'")
                ->max(DB::raw('CAST(order_number AS UNSIGNED)'));
        } else {
            // SQLite-compatible fallback (used in tests)
            $max = (int) $query
                ->whereRaw("order_number GLOB '[0-9]*'")
                ->max(DB::raw('CAST(order_number AS INTEGER)'));
        }

        return (string) ($max + 1);
    }

    private function validationRules(): array
    {
        $currencyList = implode(',', array_keys($this->currencies));

        return [
            'orderNotes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.url' => 'nullable|string|max:2000',
            'items.*.qty' => 'nullable|integer|min:1|max:9999',
            'items.*.color' => 'nullable|string|max:100',
            'items.*.size' => 'nullable|string|max:100',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.currency' => "nullable|string|in:{$currencyList}",
            'items.*.notes' => 'nullable|string|max:1000',
            'itemFiles' => 'nullable|array',
            'itemFiles.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp,pdf,xlsx,xls|max:'.(Setting::get('max_file_size_mb', 2) * 1024),
        ];
    }

    private function buildCurrencies(): array
    {
        return [
            'USD' => ['label' => __('opus46.cur_usd'), 'symbol' => '$'],
            'EUR' => ['label' => __('opus46.cur_eur'), 'symbol' => '€'],
            'GBP' => ['label' => __('opus46.cur_gbp'), 'symbol' => '£'],
            'CNY' => ['label' => __('opus46.cur_cny'), 'symbol' => '¥'],
            'JPY' => ['label' => __('opus46.cur_jpy'), 'symbol' => '¥'],
            'KRW' => ['label' => __('opus46.cur_krw'), 'symbol' => '₩'],
            'TRY' => ['label' => __('opus46.cur_try'), 'symbol' => '₺'],
            'SAR' => ['label' => __('opus46.cur_sar'), 'symbol' => 'ر.س'],
            'OTHER' => ['label' => __('opus46.cur_other'), 'symbol' => '—'],
        ];
    }

    private function buildExchangeRates(): array
    {
        $defaults = [
            'SAR' => 1.0, 'USD' => 3.86, 'EUR' => 4.22, 'GBP' => 4.89,
            'CNY' => 0.55, 'JPY' => 0.025, 'KRW' => 0.0027, 'TRY' => 0.11,
            'AED' => 1.05,
        ];

        $stored = Setting::get('exchange_rates');
        if (! $stored || ! is_array($stored)) {
            return $defaults + ['OTHER' => 0];
        }

        $ratesNode = $stored['rates'] ?? null;
        if (is_array($ratesNode)) {
            $flat = [];
            foreach ($ratesNode as $code => $data) {
                $flat[$code] = is_array($data) ? (float) ($data['final'] ?? 0) : (float) $data;
            }
            $flat['OTHER'] = $flat['OTHER'] ?? 0;

            return array_filter($flat, fn ($v) => $v >= 0) + ['OTHER' => 0];
        }

        if (isset($stored['USD']) && ! is_array($stored['USD'])) {
            return array_map('floatval', $stored) + ['OTHER' => 0];
        }

        return $defaults + ['OTHER' => 0];
    }

    public function render()
    {
        $view = view('livewire.new-order')
            ->with('commissionSettings', CommissionCalculator::getSettings());

        if ($this->editingOrderId) {
            return $view->title(__('orders.edit_order_title', ['number' => $this->editingOrderNumber]));
        }

        return $view->title(__('New Order'));
    }
}
