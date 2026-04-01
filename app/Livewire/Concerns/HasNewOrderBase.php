<?php

namespace App\Livewire\Concerns;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

trait HasNewOrderBase
{
    /**
     * Resolve the active layout key.
     * New-layout direct routes (/new-order-cards, etc.) take priority over the admin setting.
     * Falls back to the admin setting for /new-order.
     *
     * @return string One of: 'cards'|'table'|'hybrid'|'wizard'|'cart'|'cart-inline'|'cart-next'
     */
    protected function resolveLayout(): string
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
    protected function prefillFromEdit(int $orderId): void
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

    protected function redirectToOrderWithError(int $orderId, string $message): void
    {
        session()->flash('error', $message);
        $order = Order::find($orderId);
        $this->redirect($order ? route('orders.show', $order) : route('orders.index'));
    }

    /**
     * Pre-fill the form from an existing order the user owns (or staff can access).
     */
    protected function prefillFromDuplicate(int $orderId): void
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

    protected function emptyItem(string $currency = 'USD'): array
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

    protected function validationRules(): array
    {
        $currencyList = implode(',', array_keys($this->currencies));

        return [
            'orderNotes' => 'nullable|string|max:5000',
            'items' => 'nullable|array',
            'items.*.url' => 'nullable|string|max:4096',
            'items.*.qty' => 'nullable|integer|min:1|max:65535',
            'items.*.color' => 'nullable|string|max:500',
            'items.*.size' => 'nullable|string|max:500',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.currency' => "nullable|string|in:{$currencyList}",
            'items.*.notes' => 'nullable|string|max:2000',
            'itemFiles' => 'nullable|array',
            'itemFiles.*' => 'nullable|array',
            'itemFiles.*.*' => 'nullable|file|mimes:'.allowed_upload_mimes().'|max:'.(Setting::get('max_file_size_mb', 2) * 1024),
        ];
    }

    protected function buildExchangeRates(): array
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
     * Get order form fields from settings: filter enabled, sort by sort_order, add locale label.
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
     */
    protected function renderTermsTemplate(): string
    {
        $template = __('order_form.terms_template_default');

        $termsLink = '<a href="'.e(url('/terms-and-conditions')).'" target="_blank" rel="noopener" class="text-primary-600 hover:underline">'.e(__('order_form.terms_and_conditions')).'</a>';
        $privacyLink = '<a href="'.e(url('/privacy-policy')).'" target="_blank" rel="noopener" class="text-primary-600 hover:underline">'.e(__('order_form.privacy_policy')).'</a>';

        $html = e($template);
        $html = str_replace(['{terms}', '{privacy}'], [$termsLink, $privacyLink], $html);

        return $html;
    }
}
