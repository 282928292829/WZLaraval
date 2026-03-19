<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewOrderController
{
    /**
     * Redirect to the layout-specific Livewire route chosen by admin setting.
     *
     * Each layout-specific route (e.g. /new-order-hybrid) uses a Livewire component
     * as its direct route action, which is the only way Livewire 3 can correctly
     * render a full-page component (with layout, title, @push stacks, etc.).
     * A 302 redirect is used so the setting change takes effect immediately without
     * any browser or proxy caching.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $layout = (string) Setting::get('order_new_layout', config('order.default_layout', 'hybrid'));

        $routeMap = [
            'cards'       => 'new-order-cards',
            'table'       => 'new-order-table',
            'hybrid'      => 'new-order-hybrid',
            'wizard'      => 'new-order-wizard',
            'cart'        => 'new-order-cart',
            'cart-inline' => 'new-order-cart-inline',
            'cart-next'   => 'new-order-cart-next',
        ];

        $targetRoute = $routeMap[$layout] ?? 'new-order-hybrid';

        return redirect()->route($targetRoute, $request->query());
    }
}
