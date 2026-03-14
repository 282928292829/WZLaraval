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
use App\Services\ImageConversionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.order-focused')]
class NewOrder extends Component
{
    use WithFileUploads;

    public array $items = [];

    public string $orderNotes = '';

    public array $itemFiles = [];

    public int $maxProducts = 30;

    public int $maxImagesPerItem = 3;

    public int $maxImagesPerOrder = 10;

    public string $defaultCurrency = 'USD';

    public array $currencies = [];

    public array $exchangeRates = [];

    /**
     * Resolved layout key for this request: 'cards'|'table'|'hybrid'|'wizard'|'cart'.
     */
    public string $activeLayout = '';

    /** Set by ?duplicate_from={id} — triggers pre-fill in mount() */
    public ?int $duplicateFrom = null;

    /** Set by ?edit={id} — editing existing order; triggers pre-fill and update flow */
    public ?int $editingOrderId = null;

    /** Order number when editing (for display) */
    public string $editingOrderNumber = '';

    /** Set by ?product_url=... — pre-fills the first item's URL field */
    public string $productUrl = '';

    /** Cart layout (Option 2): current form fields before adding to cart */
    public array $currentItem = [];

    /** Cart layout: single file for the current item being added */
    public $currentItemFile = null;

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
        $this->maxImagesPerItem = max(1, (int) Setting::get('max_images_per_item', 3));
        $this->maxImagesPerOrder = max(1, (int) Setting::get('max_images_per_order', 10));
        $this->defaultCurrency = (string) Setting::get('default_currency', 'USD');
        $this->currencies = order_form_currencies();
        $this->exchangeRates = $this->buildExchangeRates();

        $this->activeLayout = $this->resolveLayout();

        // Edit mode: ?edit={id} — redirect to order page for inline edit (System 1)
        $editId = $edit ?? (int) request()->query('edit', 0);
        if ($editId && Auth::check()) {
            $order = Order::find($editId);
            if ($order) {
                $this->redirect(route('orders.show', $order));
            }
        }

        if ($this->activeLayout === 'cart') {
            $this->currentItem = $this->emptyItem($this->defaultCurrency);
        }

        if ($duplicate_from && Auth::check()) {
            $this->prefillFromDuplicate($duplicate_from);
            if ($this->activeLayout === 'cart' && ! empty($this->items)) {
                $last = $this->items[array_key_last($this->items)];
                $this->currentItem = $this->emptyItem($last['currency'] ?? $this->defaultCurrency);
            }
        } elseif ($product_url !== '') {
            $this->productUrl = $product_url;
            if ($this->activeLayout === 'cart') {
                $this->currentItem['url'] = $product_url;
            } else {
                $firstItem = $this->emptyItem($this->defaultCurrency);
                $firstItem['url'] = $product_url;
                $this->items = [$firstItem];
            }
        }
    }

    /**
     * Resolve the active layout key.
     * New-layout direct routes (/new-order-cards, etc.) take priority over the admin setting.
     * Falls back to the admin setting for /new-order.
     *
     * @return string One of: 'cards'|'table'|'hybrid'|'wizard'|'cart'
     */
    private function resolveLayout(): string
    {
        $pathMap = [
            'new-order-cards' => 'cards',
            'new-order-table' => 'table',
            'new-order-table-nosticky' => 'table-nosticky',
            'new-order-table-sticky' => 'table-sticky',
            'new-order-hybrid' => 'hybrid',
            'new-order-wizard' => 'wizard',
            'new-order-cart' => 'cart',
        ];

        $path = trim(request()->path(), '/');
        if (isset($pathMap[$path])) {
            return $pathMap[$path];
        }

        return (string) Setting::get('order_new_layout', config('order.default_layout'));
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
        $order = Order::find($orderId);
        $this->redirect($order ? route('orders.show', $order) : route('orders.index'));
    }

    /**
     * Pre-fill the form from an existing order the user owns (or staff can access).
     */
    private function prefillFromDuplicate(int $orderId): void
    {
        $user = Auth::user();
        $isStaff = $user->isStaffOrAbove();

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
            $this->dispatch('notify', type: 'error', message: __('order_form.max_products', ['max' => $this->maxProducts]));

            return;
        }
        $this->items[] = $this->emptyItem($currency ?: $this->defaultCurrency);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->itemFiles[$index]);
        $this->items = array_values($this->items);
        $this->itemFiles = array_values($this->itemFiles);

        if (count($this->items) === 0) {
            $this->dispatch('cart-emptied');
        }
    }

    /**
     * Cart layout (Option 2): Add current form to cart. Empty URL and empty fields are allowed.
     */
    public function addToCart(): void
    {
        if (count($this->items) >= $this->maxProducts) {
            $this->dispatch('notify', type: 'error', message: __('order_form.max_products', ['max' => $this->maxProducts]));

            return;
        }

        $price = trim($this->currentItem['price'] ?? '');
        if ($price !== '' && is_numeric($price) && (float) $price < 0) {
            $this->dispatch('notify', type: 'error', message: __('order_form.cart_invalid_price'));

            return;
        }

        $files = $this->currentItemFile ? [$this->currentItemFile] : [];
        $fileCount = count($files);
        $normalized = $this->normalizeItemFiles();
        $totalFiles = array_sum(array_map('count', $normalized)) + $fileCount;

        if ($fileCount > $this->maxImagesPerItem) {
            $this->dispatch('notify', type: 'error',
                message: __('order_form.max_per_item_exceeded', ['max' => $this->maxImagesPerItem]));

            return;
        }

        if ($totalFiles > $this->maxImagesPerOrder) {
            $this->dispatch('notify', type: 'error',
                message: __('order.max_images_per_order_blocked', ['max' => $this->maxImagesPerOrder]));

            return;
        }

        $item = [
            'url' => trim($this->currentItem['url'] ?? ''),
            'qty' => trim($this->currentItem['qty'] ?? '') !== '' ? (string) max(1, (int) $this->currentItem['qty']) : '1',
            'color' => trim($this->currentItem['color'] ?? ''),
            'size' => trim($this->currentItem['size'] ?? ''),
            'price' => $price,
            'currency' => trim($this->currentItem['currency'] ?? '') ?: $this->defaultCurrency,
            'notes' => trim($this->currentItem['notes'] ?? ''),
        ];

        $newIndex = count($this->items);
        $this->items[] = $item;
        $this->itemFiles[$newIndex] = $files;

        $lastCurrency = $item['currency'];
        $this->currentItem = $this->emptyItem($lastCurrency);
        $this->currentItemFile = null;

        $this->dispatch('notify', type: 'success', message: __('order_form.cart_item_added'));
    }

    /**
     * Cart layout: Edit item — move it back to the add-product form.
     */
    public function editCartItem(int $index): void
    {
        $item = $this->items[$index] ?? null;
        if (! $item) {
            return;
        }

        $this->currentItem = [
            'url' => $item['url'] ?? '',
            'qty' => $item['qty'] ?? '1',
            'color' => $item['color'] ?? '',
            'size' => $item['size'] ?? '',
            'price' => $item['price'] ?? '',
            'currency' => $item['currency'] ?? $this->defaultCurrency,
            'notes' => $item['notes'] ?? '',
        ];

        $existingFiles = $this->normalizeItemFiles()[$index] ?? [];
        $this->currentItemFile = $existingFiles[0] ?? null;

        $this->removeItem($index);
        $this->dispatch('notify', type: 'success', message: __('order_form.cart_item_moved_to_form'));
    }

    /**
     * Cart layout: Add 5 test items (admin-enabled).
     */
    public function addFiveTestItems(): void
    {
        if (count($this->items) >= $this->maxProducts) {
            $this->dispatch('notify', type: 'error',
                message: __('order_form.max_products', ['max' => $this->maxProducts]));

            return;
        }

        $urls = [
            'https://www.amazon.com/dp/B0BSHF7LLL',
            'https://www.ebay.com/itm/'.(string) random_int(100000000, 999999999),
            'https://www.walmart.com/ip/'.(string) random_int(100000, 999999),
            'https://www.target.com/p/product-'.(string) random_int(100, 999),
            'https://www.aliexpress.com/item/'.(string) random_int(1000000000, 9999999999).'.html',
        ];
        $sizes = [
            __('order_form.test_size_s', [], 'ar'),
            __('order_form.test_size_m', [], 'ar'),
            __('order_form.test_size_l', [], 'ar'),
            __('order_form.test_size_xl', [], 'ar'),
            __('order_form.test_size_us8', [], 'ar'),
            __('order_form.test_size_us10', [], 'ar'),
            __('order_form.test_size_one', [], 'ar'),
        ];
        $currencies = ['USD', 'EUR', 'GBP'];
        $colors = [
            __('order_form.test_color_1', [], 'ar'),
            __('order_form.test_color_2', [], 'ar'),
            __('order_form.test_color_3', [], 'ar'),
            __('order_form.test_color_4', [], 'ar'),
            __('order_form.test_color_5', [], 'ar'),
        ];
        $notes = [
            __('order_form.test_note_1', [], 'ar'),
            __('order_form.test_note_2', [], 'ar'),
            __('order_form.test_note_3', [], 'ar'),
            __('order_form.test_note_4', [], 'ar'),
            __('order_form.test_note_5', [], 'ar'),
        ];

        $toAdd = 5;
        $lastCur = count($this->items) > 0 ? ($this->items[array_key_last($this->items)]['currency'] ?? $this->defaultCurrency) : $this->defaultCurrency;
        for ($i = 0; $i < $toAdd && count($this->items) < $this->maxProducts; $i++) {
            $cur = $currencies[$i % 3] ?? $lastCur;
            $this->items[] = [
                'url' => $urls[$i],
                'qty' => (string) random_int(1, 2),
                'color' => $colors[$i % 5],
                'size' => $sizes[array_rand($sizes)],
                'price' => (string) round(random_int(1500, 8000) / 100, 2),
                'currency' => $cur,
                'notes' => $notes[$i % 5],
            ];
            $lastCur = $cur;
        }
        $this->dispatch('notify', type: 'success', message: __('order.dev_5_items_added'));
    }

    /**
     * Cart layout: Clear all items and notes (admin-enabled).
     */
    public function clearAllItems(): void
    {
        $this->items = [];
        $this->orderNotes = '';
        $this->itemFiles = [];
        $this->currentItem = $this->emptyItem($this->defaultCurrency);
        $this->currentItemFile = null;
        $this->dispatch('notify', type: 'success', message: __('order_form.cleared'));
    }

    /**
     * Cart layout (Option 2): Load guest draft from localStorage. Called from frontend when draft exists and items are empty.
     */
    public function loadGuestDraftFromStorage(array $items, string $notes = ''): void
    {
        if (Auth::check()) {
            return;
        }
        if ($this->activeLayout !== 'cart') {
            return;
        }
        if (count($this->items) > 0) {
            return;
        }
        $valid = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $valid[] = [
                'url' => (string) ($item['url'] ?? ''),
                'qty' => trim((string) ($item['qty'] ?? '1')) !== '' ? (string) max(1, (int) ($item['qty'] ?? 1)) : '1',
                'color' => (string) ($item['color'] ?? ''),
                'size' => (string) ($item['size'] ?? ''),
                'price' => (string) ($item['price'] ?? ''),
                'currency' => (string) ($item['currency'] ?? $this->defaultCurrency) ?: $this->defaultCurrency,
                'notes' => (string) ($item['notes'] ?? ''),
            ];
        }
        if (count($valid) > 0) {
            $this->items = $valid;
            $this->itemFiles = array_fill(0, count($valid), []);
            $this->orderNotes = mb_substr($notes, 0, 5000);
        }
    }

    /**
     * Cart layout: Dispatch event so frontend can save draft to localStorage (guests only).
     *
     * @param  mixed  $value  For array properties: the value of the updated element
     * @param  mixed  $key  For array properties: the key of the updated element (null when whole array replaced)
     */
    public function updatedItems(mixed $value = null, mixed $key = null): void
    {
        if (Auth::check()) {
            return;
        }
        if ($this->activeLayout !== 'cart') {
            return;
        }
        $this->dispatch('save-cart-draft', items: $this->items, notes: $this->orderNotes);
    }

    public function updatedOrderNotes(): void
    {
        if (Auth::check()) {
            return;
        }
        if ($this->activeLayout !== 'cart') {
            return;
        }
        $this->dispatch('save-cart-draft', items: $this->items, notes: $this->orderNotes);
    }

    public function removeItemFile(int $itemIndex, int $fileIndex): void
    {
        $files = $this->itemFiles[$itemIndex] ?? null;
        if ($files === null) {
            return;
        }
        $arr = is_array($files) ? array_values($files) : ($files ? [$files] : []);
        array_splice($arr, $fileIndex, 1);
        $this->itemFiles[$itemIndex] = $arr;
    }

    public function shiftFileIndex(int $removedIndex): void
    {
        $shifted = [];
        foreach ($this->itemFiles as $idx => $files) {
            if ($idx === $removedIndex) {
                continue;
            }
            $newIdx = $idx > $removedIndex ? $idx - 1 : $idx;
            $shifted[$newIdx] = is_array($files) ? array_values($files) : ($files ? [$files] : []);
        }
        $this->itemFiles = $shifted;
    }

    /**
     * Normalize itemFiles to array-of-arrays. Ensures each item has [file, ...].
     */
    private function normalizeItemFiles(): array
    {
        $normalized = [];
        foreach ($this->itemFiles as $idx => $files) {
            if (is_array($files)) {
                $normalized[$idx] = array_values(array_filter($files));
            } elseif ($files) {
                $normalized[$idx] = [$files];
            } else {
                $normalized[$idx] = [];
            }
        }

        return $normalized;
    }

    /**
     * Count total files and check per-item / per-order limits.
     *
     * @return array{valid: bool, per_item_violation: ?int, total: int}
     */
    private function checkFileLimits(): array
    {
        $normalized = $this->normalizeItemFiles();
        $total = 0;
        $perItemViolation = null;

        foreach ($normalized as $idx => $files) {
            $count = count($files);
            $total += $count;
            if ($count > $this->maxImagesPerItem) {
                $perItemViolation = $idx;
            }
        }

        $valid = $total <= $this->maxImagesPerOrder && $perItemViolation === null;

        return ['valid' => $valid, 'per_item_violation' => $perItemViolation, 'total' => $total];
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
        $isStaff = $user->isStaffOrAbove();

        // Per-hour limit
        $hourlyLimit = $isStaff
            ? (int) Setting::get('orders_per_hour_admin', 50)
            : (int) Setting::get('orders_per_hour_customer', 30);

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

        // Per-day limit
        $dayLimit = $isStaff
            ? (int) Setting::get('orders_per_day_staff', 100)
            : (int) Setting::get('orders_per_day_customer', 200);

        if ($dayLimit > 0) {
            $todayCount = Order::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($todayCount >= $dayLimit) {
                $this->dispatch('notify', type: 'error',
                    message: __('order.daily_limit_reached', ['max' => $dayLimit]));

                return;
            }
        }

        // Per-month limit
        $monthLimit = $isStaff
            ? (int) Setting::get('orders_per_month_admin', 1000)
            : (int) Setting::get('orders_per_month_customer', 500);

        if ($monthLimit > 0) {
            $monthCount = Order::where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            if ($monthCount >= $monthLimit) {
                $this->dispatch('notify', type: 'error',
                    message: __('order.monthly_limit_reached', ['max' => $monthLimit]));

                return;
            }
        }

        $this->validate($this->validationRules());

        $itemsWithOriginalIndex = [];
        foreach ($this->items ?? [] as $originalIndex => $item) {
            $itemsWithOriginalIndex[] = ['data' => $item, 'orig' => $originalIndex];
        }

        $limits = $this->checkFileLimits();
        if (! $limits['valid']) {
            if ($limits['per_item_violation'] !== null) {
                $this->dispatch('notify', type: 'error',
                    message: __('order_form.max_per_item_exceeded', ['max' => $this->maxImagesPerItem]));
            } else {
                $this->dispatch('notify', type: 'error',
                    message: __('order.max_images_per_order_blocked', [
                        'max' => $this->maxImagesPerOrder,
                    ]));
            }

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
                'layout_option' => Setting::get('order_new_layout', config('order.default_layout')),
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
                    'is_url' => safe_item_url($item['url'] ?? '') !== null,
                    'qty' => $qty,
                    'color' => $item['color'] ?: null,
                    'size' => $item['size'] ?: null,
                    'notes' => $item['notes'] ?: null,
                    'currency' => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                $files = $this->normalizeItemFiles()[$origIndex] ?? [];
                $firstPath = null;
                foreach ($files as $file) {
                    if (! $file) {
                        continue;
                    }
                    $stored = app(ImageConversionService::class)->storeForDisplay($file, "orders/{$order->id}", 'public');
                    if ($firstPath === null) {
                        $firstPath = $stored['path'];
                        $orderItem->update(['image_path' => $stored['path']]);
                    }
                    OrderFile::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'user_id' => Auth::id(),
                        'path' => $stored['path'],
                        'original_name' => $stored['original_name'],
                        'mime_type' => $stored['mime_type'],
                        'size' => $stored['size'],
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

            // Redirect: success page (first N orders) or direct to order page
            $enabled = (bool) Setting::get('order_success_screen_enabled', true);
            $threshold = max(0, (int) Setting::get('order_success_screen_threshold', 10));
            $totalOrders = Order::where('user_id', Auth::id())->count();

            $showSuccessPage = $enabled && $totalOrders <= $threshold;

            if ($showSuccessPage) {
                $this->redirectRoute('orders.success', $createdOrder);
            } else {
                session()->flash('order_created', true);
                session()->flash('success', __('order.created_successfully', [
                    'number' => $createdOrder->order_number,
                ]));
                $this->redirectRoute('orders.show', $createdOrder);
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

        $limits = $this->checkFileLimits();
        if (! $limits['valid']) {
            if ($limits['per_item_violation'] !== null) {
                $this->dispatch('notify', type: 'error',
                    message: __('order_form.max_per_item_exceeded', ['max' => $this->maxImagesPerItem]));
            } else {
                $this->dispatch('notify', type: 'error',
                    message: __('order.max_images_per_order_blocked', ['max' => $this->maxImagesPerOrder]));
            }

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
                    'is_url' => safe_item_url($item['url'] ?? '') !== null,
                    'qty' => $qty,
                    'color' => $item['color'] ?: null,
                    'size' => $item['size'] ?: null,
                    'notes' => $item['notes'] ?: null,
                    'currency' => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);

                $files = $this->normalizeItemFiles()[$origIndex] ?? [];
                $firstPath = null;
                foreach ($files as $file) {
                    if (! $file) {
                        continue;
                    }
                    $stored = app(ImageConversionService::class)->storeForDisplay($file, "orders/{$order->id}", 'public');
                    if ($firstPath === null) {
                        $firstPath = $stored['path'];
                        $orderItem->update(['image_path' => $stored['path']]);
                    }
                    OrderFile::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'user_id' => Auth::id(),
                        'path' => $stored['path'],
                        'original_name' => $stored['original_name'],
                        'mime_type' => $stored['mime_type'],
                        'size' => $stored['size'],
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
        $this->redirect(route('orders.show', $order));
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
        $companyName = Setting::get('payment_company_name') ?: $siteName;
        $baseUrl = rtrim(config('app.url'), '/');

        $replacements = [
            'subtotal' => number_format($order->subtotal, 0, '.', ','),
            'commission' => number_format(max(0, $order->total_amount - $order->subtotal), 0, '.', ','),
            'total' => number_format($order->total_amount, 0, '.', ','),
            'site_name' => $siteName,
            'whatsapp' => $whatsappDisplay,
            'company_name' => $companyName,
            'payment_url' => $baseUrl.'/payment-methods',
            'terms_url' => $baseUrl.'/terms-and-conditions',
            'faq_url' => $baseUrl.'/faq',
            'shipping_url' => $baseUrl.'/shipping-calculator',
        ];

        if ($hasPrices) {
            $template = Setting::get('auto_comment_with_price', '');
            $body = $template !== ''
                ? str_replace(array_map(fn ($k) => ':'.$k, array_keys($replacements)), array_values($replacements), $template)
                : __('orders.auto_comment_with_price', $replacements);
        } else {
            $template = Setting::get('auto_comment_no_price', '');
            $body = $template !== ''
                ? str_replace(array_map(fn ($k) => ':'.$k, array_keys($replacements)), array_values($replacements), $template)
                : __('orders.auto_comment_no_price', ['whatsapp' => $whatsappDisplay]);
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
        $staff = User::staff()->first() ?? $customer;

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
            $this->modalSuccess = __('order_form.reset_link_sent');
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
            'items' => 'nullable|array',
            'items.*.url' => 'nullable|string|max:2000',
            'items.*.qty' => 'nullable|string|max:2000',
            'items.*.color' => 'nullable|string|max:2000',
            'items.*.size' => 'nullable|string|max:2000',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.currency' => "nullable|string|in:{$currencyList}",
            'items.*.notes' => 'nullable|string|max:2000',
            'itemFiles' => 'nullable|array',
            'itemFiles.*' => 'nullable|array',
            'itemFiles.*.*' => 'nullable|file|mimes:'.allowed_upload_mimes().'|max:'.(Setting::get('max_file_size_mb', 2) * 1024),
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

    /**
     * Compute cart summary (subtotal, commission, total in SAR) for layout 2.
     *
     * @return array{subtotal: float, commission: float, total: float, filledCount: int}
     */
    public function getCartSummary(): array
    {
        $rawSubtotal = 0;
        $filledCount = 0;
        foreach ($this->items as $item) {
            $url = trim($item['url'] ?? '');
            if ($url !== '') {
                $filledCount++;
            }
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $price = is_numeric($item['price'] ?? null) ? (float) $item['price'] : 0;
            $curr = $item['currency'] ?? 'USD';
            $rate = $this->exchangeRates[$curr] ?? 0;
            if ($price > 0 && $rate > 0) {
                $rawSubtotal += $price * $qty * $rate;
            }
        }
        $commission = CommissionCalculator::calculate($rawSubtotal);
        $total = round($rawSubtotal + $commission, 2);
        $subtotal = round($rawSubtotal, 2);

        return [
            'subtotal' => $subtotal,
            'commission' => $commission,
            'total' => $total,
            'filledCount' => $filledCount,
        ];
    }

    /**
     * Get order form fields from settings: filter enabled, sort by sort_order, add locale label.
     * Fallback to default fields with translation keys when config is empty.
     *
     * @return array<int, array{key: string, label: string, optional: bool, enabled: bool, sort_order: int}>
     */
    protected function getOrderFormFields(): array
    {
        $raw = Setting::get('order_form_fields', []);
        if (! is_array($raw) || empty($raw)) {
            $defaultKeys = ['url', 'qty', 'size', 'color', 'price', 'currency', 'notes', 'file'];
            $defaultOptional = ['notes' => true, 'file' => true];
            $defaultLabels = [
                'url' => __('order_form.th_url'),
                'qty' => __('order_form.th_qty'),
                'size' => __('order_form.th_size'),
                'color' => __('order_form.th_color'),
                'price' => __('order_form.th_price_per_unit'),
                'currency' => __('order_form.th_currency'),
                'notes' => __('order_form.th_notes'),
                'file' => __('order_form.th_files'),
            ];
            $order = 0;

            return collect($defaultKeys)->map(function (string $key) use ($defaultLabels, $defaultOptional, &$order) {
                return [
                    'key' => $key,
                    'label' => $defaultLabels[$key] ?? $key,
                    'optional' => $defaultOptional[$key] ?? false,
                    'enabled' => true,
                    'sort_order' => ++$order,
                ];
            })->values()->all();
        }

        $locale = app()->getLocale();
        $isAr = $locale === 'ar';

        return collect($raw)
            ->filter(fn (array $f) => ($f['enabled'] ?? true) === true)
            ->sortBy('sort_order')
            ->values()
            ->map(function (array $f) use ($isAr) {
                $label = $isAr ? ($f['label_ar'] ?? $f['label_en'] ?? '') : ($f['label_en'] ?? $f['label_ar'] ?? '');
                $key = $f['key'] ?? '';

                return [
                    'key' => $key,
                    'label' => $label ?: __('order_form.th_'.str_replace('-', '_', $key)),
                    'optional' => (bool) ($f['optional'] ?? false),
                    'enabled' => true,
                    'sort_order' => (int) ($f['sort_order'] ?? 99),
                ];
            })
            ->all();
    }

    public function render(): \Illuminate\View\View
    {
        $maxFileSizeMb = (int) Setting::get('max_file_size_mb', 2);
        $layout = $this->activeLayout ?: $this->resolveLayout();

        $viewName = match ($layout) {
            'cards' => 'livewire.new-order-cards',
            'table' => 'livewire.new-order-table',
            'table-nosticky' => 'livewire.new-order-table-nosticky',
            'table-sticky' => 'livewire.new-order-table-sticky',
            'hybrid' => 'livewire.new-order-hybrid',
            'wizard' => 'livewire.new-order-wizard',
            'cart' => 'livewire.new-order-cart',
            default => 'livewire.new-order',
        };

        $view = view($viewName)
            ->with('orderNewLayout', $layout)
            ->with('orderFormFields', $this->getOrderFormFields())
            ->with('showAddTestItems', (bool) Setting::get('order_form_show_add_test_items', false))
            ->with('showResetAll', (bool) Setting::get('order_form_show_reset_all', true))
            ->with('commissionSettings', CommissionCalculator::getSettings())
            ->with('allowedMimeTypes', allowed_upload_mime_types())
            ->with('maxFileSizeBytes', $maxFileSizeMb * 1024 * 1024)
            ->with('maxFileSizeMb', $maxFileSizeMb);

        if ($layout === 'cart') {
            $view = $view->with('cartSummary', $this->getCartSummary());
        }

        if ($this->editingOrderId) {
            return $view->title(__('orders.edit_order_title', ['number' => $this->editingOrderNumber]));
        }

        return $view->title(__('New Order'));
    }
}
