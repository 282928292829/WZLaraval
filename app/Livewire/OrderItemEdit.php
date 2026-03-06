<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Setting;
use App\Services\CommissionCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrderItemEdit extends Component
{
    public Order $order;

    public bool $canEditItems = false;

    public bool $orderEditEnabled = true;

    public ?string $clickEditRemaining = null;

    public bool $isOwner = false;

    public bool $isStaff = false;

    public bool $editing = false;

    public array $items = [];

    public string $orderNotes = '';

    public array $currencies = [];

    public array $exchangeRates = [];

    public string $defaultCurrency = 'USD';

    public int $maxProducts = 30;

    public bool $customerCanAddFiles = false;

    public int $maxFilesPerItemAfterSubmit = 5;

    public function mount(
        Order $order,
        bool $canEditItems,
        bool $orderEditEnabled,
        ?string $clickEditRemaining,
        bool $isOwner,
        bool $isStaff
    ): void {
        $this->order = $order->loadMissing([
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'files' => fn ($q) => $q->orderBy('created_at'),
        ]);
        $this->canEditItems = $canEditItems;
        $this->orderEditEnabled = $orderEditEnabled;
        $this->clickEditRemaining = $clickEditRemaining;
        $this->isOwner = $isOwner;
        $this->isStaff = $isStaff;
        $this->defaultCurrency = (string) Setting::get('default_currency', 'USD');
        $this->currencies = order_form_currencies();
        $this->exchangeRates = $this->buildExchangeRates();
        $this->maxProducts = (int) Setting::get('max_products_per_order', 30);
        $this->maxFilesPerItemAfterSubmit = max(1, (int) Setting::get('max_files_per_item_after_submit', 5));
        $this->customerCanAddFiles = (bool) Setting::get('customer_can_add_files_after_submit', false);
    }

    public function startEdit(): void
    {
        if (! $this->canEditItems || ! $this->isOwner) {
            $this->dispatch('order-toast', type: 'error', message: __('orders.edit_forbidden'));

            return;
        }

        $order = $this->order->fresh(['items']);

        if ($order->is_paid) {
            $this->dispatch('order-toast', type: 'error', message: __('orders.edit_already_paid'));

            return;
        }

        $clickWindowMinutes = (int) Setting::get('order_edit_click_window_minutes', 10);
        $resubmitWindowMinutes = (int) Setting::get('order_edit_resubmit_window_minutes', 10);
        $clickEditDeadline = $order->created_at->copy()->addMinutes($clickWindowMinutes);

        if (now()->gte($clickEditDeadline)) {
            $this->dispatch('order-toast', type: 'error', message: __('orders.edit_click_window_expired'));

            return;
        }

        // Start resubmit window if not already started
        if ($order->can_edit_until === null || now()->gte($order->can_edit_until)) {
            $order->update(['can_edit_until' => now()->addMinutes($resubmitWindowMinutes)]);
        }
        $this->order = $order->fresh();

        $this->orderNotes = (string) ($order->notes ?? '');
        $this->items = $order->items->map(fn (OrderItem $item) => [
            'id' => (string) $item->id,
            'url' => (string) ($item->url ?? ''),
            'qty' => (string) max(1, (int) $item->qty),
            'color' => (string) ($item->color ?? ''),
            'size' => (string) ($item->size ?? ''),
            'price' => $item->unit_price !== null ? (string) $item->unit_price : '',
            'currency' => (string) ($item->currency ?? $this->defaultCurrency),
            'notes' => (string) ($item->notes ?? ''),
        ])->values()->toArray();

        if (empty($this->items)) {
            $this->items = [$this->emptyItem()];
        }

        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->items = [];
        $this->orderNotes = '';
    }

    public function addProduct(): void
    {
        if (count($this->items) >= $this->maxProducts) {
            $this->dispatch('order-toast', type: 'error',
                message: __('order_form.max_products', ['max' => $this->maxProducts]));

            return;
        }

        $lastCurrency = $this->defaultCurrency;
        if (! empty($this->items)) {
            $lastCurrency = end($this->items)['currency'] ?? $this->defaultCurrency;
        }
        $this->items[] = $this->emptyItem($lastCurrency);
    }

    public function removeProduct(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }
        array_splice($this->items, $index, 1);
    }

    public function save(): void
    {
        $order = $this->order->fresh();

        if (! $order || $order->user_id !== Auth::id()) {
            $this->dispatch('order-toast', type: 'error', message: __('orders.edit_forbidden'));

            return;
        }

        if ($order->is_paid) {
            $this->dispatch('order-toast', type: 'error', message: __('orders.edit_already_paid'));

            return;
        }

        if ($order->can_edit_until === null || now()->gte($order->can_edit_until)) {
            $this->dispatch('order-toast', type: 'error', message: __('orders.edit_resubmit_window_expired'));

            return;
        }

        $itemsWithIndex = [];
        foreach ($this->items as $i => $item) {
            $hasContent = trim($item['url'] ?? '') !== ''
                || trim($item['color'] ?? '') !== ''
                || trim($item['size'] ?? '') !== ''
                || trim($item['notes'] ?? '') !== ''
                || (is_numeric($item['price'] ?? null) && (float) $item['price'] > 0);
            if ($hasContent) {
                $itemsWithIndex[] = ['data' => $item, 'index' => $i];
            }
        }

        if (empty($itemsWithIndex)) {
            $this->dispatch('order-toast', type: 'error', message: __('orders.edit_empty_items_rejected'));

            return;
        }

        $currencyList = implode(',', array_keys($this->currencies));
        $this->validate([
            'orderNotes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.url' => 'nullable|string|max:2000',
            'items.*.qty' => 'nullable|string|max:2000',
            'items.*.color' => 'nullable|string|max:2000',
            'items.*.size' => 'nullable|string|max:2000',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.currency' => "nullable|string|in:{$currencyList}",
            'items.*.notes' => 'nullable|string|max:2000',
        ], [], [
            'orderNotes' => __('order_form.general_notes'),
        ]);

        $rates = $this->exchangeRates;

        DB::transaction(function () use ($order, $itemsWithIndex, $rates) {
            $order->items()->delete();

            $rawSubtotal = 0;

            foreach ($itemsWithIndex as $sortOrder => $entry) {
                $item = $entry['data'];
                $price = is_numeric($item['price'] ?? null) ? (float) $item['price'] : null;
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $curr = $item['currency'] ?? 'USD';
                $rate = $rates[$curr] ?? 0;

                if ($price && $rate > 0) {
                    $rawSubtotal += $price * $qty * $rate;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'url' => $item['url'] ?? null,
                    'is_url' => safe_item_url($item['url'] ?? '') !== null,
                    'qty' => $qty,
                    'color' => ! empty(trim($item['color'] ?? '')) ? trim($item['color']) : null,
                    'size' => ! empty(trim($item['size'] ?? '')) ? trim($item['size']) : null,
                    'notes' => ! empty(trim($item['notes'] ?? '')) ? trim($item['notes']) : null,
                    'currency' => $curr,
                    'unit_price' => $price,
                    'sort_order' => $sortOrder,
                ]);
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
                'body' => __('orders.edit_saved_successfully', ['number' => $order->order_number]),
                'is_system' => true,
            ]);
        });

        session()->flash('success', __('orders.edit_saved_successfully', ['number' => $order->order_number]));

        $this->redirect(route('orders.show', $order));
    }

    private function emptyItem(string $currency = 'USD'): array
    {
        return [
            'id' => '',
            'url' => '',
            'qty' => '1',
            'color' => '',
            'size' => '',
            'price' => '',
            'currency' => $currency,
            'notes' => '',
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
        $resubmitWindowMinutes = (int) Setting::get('order_edit_resubmit_window_minutes', 10);
        $canEditUntil = $this->order->can_edit_until;
        $resubmitDeadline = $canEditUntil ? now()->diffForHumans($canEditUntil, true) : null;

        return view('livewire.order-item-edit', [
            'resubmitDeadline' => $resubmitDeadline,
        ]);
    }
}
