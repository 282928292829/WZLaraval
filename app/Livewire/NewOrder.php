<?php

namespace App\Livewire;

use App\DTOs\OrderSubmissionData;
use App\Livewire\Concerns\HandlesOrderItemFiles;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\CommissionCalculator;
use App\Services\OrderSubmissionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.order-focused')]
class NewOrder extends Component
{
    use HandlesOrderItemFiles;
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

    /** Cart layout: files for the current item being added (supports multiple when max_images_per_item > 1) */
    public $currentItemFiles = [];

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

        if (in_array($this->activeLayout, ['cart', 'cart-next'], true)) {
            $this->currentItem = $this->emptyItem($this->defaultCurrency);
        }

        if ($duplicate_from && Auth::check()) {
            $this->prefillFromDuplicate($duplicate_from);
            if (in_array($this->activeLayout, ['cart', 'cart-next'], true) && ! empty($this->items)) {
                $last = $this->items[array_key_last($this->items)];
                $this->currentItem = $this->emptyItem($last['currency'] ?? $this->defaultCurrency);
            }
        } elseif ($product_url !== '') {
            $this->productUrl = $product_url;
            if (in_array($this->activeLayout, ['cart', 'cart-next'], true)) {
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
            'new-order-hybrid' => 'hybrid',
            'new-order-wizard' => 'wizard',
            'new-order-cart' => 'cart',
            'new-order-cart-inline' => 'cart-inline',
            'new-order-cart-next' => 'cart-next',
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

    /**
     * Cart-next: No-op called when Save is clicked in the inline edit panel.
     * Forces a Livewire round-trip so wire:model.blur updates are committed.
     */
    public function syncItemEdits(): void
    {
        // No-op — Livewire round-trip ensures pending wire:model.blur updates are flushed
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

        $raw = $this->currentItemFiles;
        $files = is_array($raw) ? array_values(array_filter($raw)) : ($raw ? [$raw] : []);
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
        $this->currentItemFiles = [];

        $this->dispatch('notify', type: 'success', message: __('order_form.cart_item_added'));
        $this->dispatch('cart-item-added');
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
        $this->currentItemFiles = array_values($existingFiles);

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
        $this->currentItemFiles = [];
        $this->dispatch('cart-emptied');
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
        if (! in_array($this->activeLayout, ['cart', 'cart-next'], true)) {
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
        if (! in_array($this->activeLayout, ['cart', 'cart-next'], true)) {
            return;
        }
        $this->dispatch('save-cart-draft', items: $this->items, notes: $this->orderNotes);
    }

    public function updatedOrderNotes(): void
    {
        if (Auth::check()) {
            return;
        }
        if (! in_array($this->activeLayout, ['cart', 'cart-next'], true)) {
            return;
        }
        $this->dispatch('save-cart-draft', items: $this->items, notes: $this->orderNotes);
    }

    // -------------------------------------------------------------------------
    // Order submission — real database writes
    // -------------------------------------------------------------------------

    public function submitOrder(): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-login-modal', reason: 'submit');

            return;
        }

        $user = Auth::user();
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

        $normalizedFiles = $this->normalizeItemFiles();
        $data = new OrderSubmissionData(
            userId: $user->id,
            isStaff: $user->isStaffOrAbove(),
            items: $itemsWithOriginalIndex,
            orderNotes: $this->orderNotes,
            normalizedFiles: $normalizedFiles,
            exchangeRates: $this->exchangeRates,
            maxImagesPerItem: $this->maxImagesPerItem,
            maxImagesPerOrder: $this->maxImagesPerOrder,
            editingOrderId: $this->editingOrderId,
            duplicateFrom: $this->duplicateFrom,
            productUrl: $this->productUrl,
            activeLayout: $this->activeLayout,
            request: request(),
        );

        $result = app(OrderSubmissionService::class)->submit($data);

        if (! $result->success) {
            if ($result->errorType === 'notify') {
                $this->dispatch('notify', type: 'error', message: $result->errorMessage);
            } else {
                session()->flash('error', $result->errorMessage);
                $this->redirect($result->redirectUrl ?? route('orders.index'));
            }

            return;
        }

        foreach ($result->sessionFlashes as $key => $value) {
            session()->flash($key, $value);
        }
        $this->redirect($result->redirectUrl);
    }

    // -------------------------------------------------------------------------
    // Guest login modal actions
    // -------------------------------------------------------------------------

    public function openLoginModalForAttach(): void
    {
        $this->dispatch('open-login-modal', reason: 'attach');
    }

    #[On('user-logged-in')]
    public function handleUserLoggedIn(string $reason = 'submit'): void
    {
        if ($reason === 'submit') {
            $this->submitOrder();
        }
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

    /**
     * Render the terms checkbox label. Always uses translation for 100% bilingual support.
     * Placeholders: {terms} → link to terms, {privacy} → link to privacy policy.
     */
    private function renderTermsTemplate(): string
    {
        $template = __('order_form.terms_template_default');

        $termsLink = '<a href="'.e(url('/terms-and-conditions')).'" target="_blank" rel="noopener" class="text-primary-600 hover:underline">'.e(__('order_form.terms_and_conditions')).'</a>';
        $privacyLink = '<a href="'.e(url('/privacy-policy')).'" target="_blank" rel="noopener" class="text-primary-600 hover:underline">'.e(__('order_form.privacy_policy')).'</a>';

        $html = e($template);
        $html = str_replace(['{terms}', '{privacy}'], [$termsLink, $privacyLink], $html);

        return $html;
    }

    public function render(): \Illuminate\View\View
    {
        $maxFileSizeMb = (int) Setting::get('max_file_size_mb', 2);
        $layout = $this->activeLayout ?: $this->resolveLayout();

        $viewName = match ($layout) {
            'cards' => 'livewire.new-order-cards',
            'table' => 'livewire.new-order-table',
            'hybrid' => 'livewire.new-order-hybrid',
            'wizard' => 'livewire.new-order-wizard',
            'cart' => 'livewire.new-order-cart',
            'cart-inline' => 'livewire.new-order-cart-inline',
            'cart-next' => 'livewire.new-order-cart-next',
            default => 'livewire.new-order-hybrid',
        };

        $view = view($viewName)
            ->with('orderNewLayout', $layout)
            ->with('orderFormFields', $this->getOrderFormFields())
            ->with('showAddTestItems', (bool) Setting::get('order_form_show_add_test_items', false))
            ->with('showResetAll', (bool) Setting::get('order_form_show_reset_all', true))
            ->with('requireTerms', (bool) Setting::get('order_form_require_terms', true))
            ->with('commissionSettings', CommissionCalculator::getSettings())
            ->with('allowedMimeTypes', allowed_upload_mime_types())
            ->with('maxFileSizeBytes', $maxFileSizeMb * 1024 * 1024)
            ->with('maxFileSizeMb', $maxFileSizeMb);

        if (in_array($layout, ['cart', 'cart-inline', 'cart-next'], true)) {
            $view = $view->with('cartSummary', $this->getCartSummary());
        }

        if ($layout === 'cart-next') {
            $view = $view->with('termsHtml', $this->renderTermsTemplate());
        }

        if ($this->editingOrderId) {
            return $view->title(__('orders.edit_order_title', ['number' => $this->editingOrderNumber]));
        }

        return $view->title(__('New Order'));
    }
}
