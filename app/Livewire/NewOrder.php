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
class NewOrder extends Component
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

        if ($duplicate_from && Auth::check()) {
            $this->prefillFromDuplicate($duplicate_from);
        } elseif ($product_url !== '') {
            $this->productUrl = $product_url;
            $firstItem = $this->emptyItem($this->defaultCurrency);
            $firstItem['url'] = $product_url;
            $this->items = [$firstItem];
        }
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

        $viewName = match ($layout) {
            'cards' => 'livewire.new-order-cards',
            'table' => 'livewire.new-order-table',
            'hybrid' => 'livewire.new-order-hybrid',
            'wizard' => 'livewire.new-order-wizard',
            'cart-inline' => 'livewire.new-order-cart-inline',
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
            ->with('maxFileSizeMb', $maxFileSizeMb)
            ->with('cartSummary', $this->getCartSummary());

        if ($this->editingOrderId) {
            return $view->title(__('orders.edit_order_title', ['number' => $this->editingOrderNumber]));
        }

        return $view->title(__('New Order'));
    }
}
