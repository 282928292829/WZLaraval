<?php

namespace App\Http\Controllers;

use App\Livewire\NewOrder;
use App\Livewire\NewOrderCart;
use App\Models\Setting;
use Livewire\Livewire;

class NewOrderController
{
    /**
     * Render the new order page. Layout is chosen by admin setting.
     * Cart/cart-next use NewOrderCart; other layouts use NewOrder.
     */
    public function __invoke()
    {
        $layout = (string) Setting::get('order_new_layout', config('order.default_layout'));
        $componentClass = in_array($layout, ['cart', 'cart-next'], true) ? NewOrderCart::class : NewOrder::class;

        $html = Livewire::mount($componentClass, [
            'duplicate_from' => request()->query('duplicate_from') ? (int) request()->query('duplicate_from') : null,
            'edit' => request()->query('edit') ? (int) request()->query('edit') : null,
            'product_url' => (string) request()->query('product_url', ''),
        ]);

        return response($html)->header('Content-Type', 'text/html');
    }
}
