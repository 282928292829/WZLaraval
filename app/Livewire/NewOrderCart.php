<?php

namespace App\Livewire;

use App\DTOs\OrderSubmissionData;
use App\Livewire\Concerns\HandlesOrderItemFiles;
use App\Livewire\Concerns\HasNewOrderBase;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CommissionCalculator;
use App\Services\OrderSubmissionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.order-focused')]
class NewOrderCart extends Component
{
    use HandlesOrderItemFiles;
    use HasNewOrderBase;
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

    public string $activeLayout = '';

    public ?int $duplicateFrom = null;

    public ?int $editingOrderId = null;

    public string $editingOrderNumber = '';

    public string $productUrl = '';

    public array $currentItem = [];

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

        $editId = $edit ?? (int) request()->query('edit', 0);
        if ($editId && Auth::check()) {
            $order = Order::find($editId);
            if ($order) {
                $this->redirect(route('orders.show', $order));
            }
        }

        $this->currentItem = $this->emptyItem($this->defaultCurrency);

        if ($duplicate_from && Auth::check()) {
            $this->prefillFromDuplicate($duplicate_from);
            if (! empty($this->items)) {
                $last = $this->items[array_key_last($this->items)];
                $this->currentItem = $this->emptyItem($last['currency'] ?? $this->defaultCurrency);
            }
        } elseif ($product_url !== '') {
            $this->productUrl = $product_url;
            $this->currentItem['url'] = $product_url;
        }
    }

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

    public function loadGuestDraftFromStorage(array $items, string $notes = ''): void
    {
        if (Auth::check()) {
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

    public function updatedItems(mixed $value = null, mixed $key = null): void
    {
        if (Auth::check()) {
            return;
        }
        $this->dispatch('save-cart-draft', items: $this->items, notes: $this->orderNotes);
    }

    public function updatedOrderNotes(): void
    {
        if (Auth::check()) {
            return;
        }
        $this->dispatch('save-cart-draft', items: $this->items, notes: $this->orderNotes);
    }

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

    public function render(): \Illuminate\View\View
    {
        $maxFileSizeMb = (int) Setting::get('max_file_size_mb', 2);
        $layout = $this->activeLayout ?: $this->resolveLayout();

        $viewName = $layout === 'cart-next' ? 'livewire.new-order-cart-next' : 'livewire.new-order-cart';

        $view = view($viewName)
            ->with('orderNewLayout', $layout)
            ->with('orderFormFields', $this->getOrderFormFields())
            ->with('showAddTestItems', (bool) Setting::get('order_form_show_add_test_items', false))
            ->with('showResetAll', (bool) Setting::get('order_form_show_reset_all', true))
            ->with('requireTerms', (bool) Setting::get('order_form_require_terms', true))
            ->with('commissionSettings', CommissionCalculator::getSettings())
            ->with('allowedMimeTypes', allowed_upload_mime_types())
            ->with('maxFileSizeBytes', $maxFileSizeMb * 1024 * 1024)
            ->with('maxFileSizeMb', $maxFileSizeMb)
            ->with('cartSummary', $this->getCartSummary());

        if ($layout === 'cart-next') {
            $view = $view->with('termsHtml', $this->renderTermsTemplate());
        }

        if ($this->editingOrderId) {
            return $view->title(__('orders.edit_order_title', ['number' => $this->editingOrderNumber]));
        }

        return $view->title(__('New Order'));
    }
}
