<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OrderDesignController extends Controller
{
    public function design1(): View
    {
        return view('layouts.order', [
            'slot' => view('orders.design-1'),
            'title' => 'Order Form Design 1',
        ]);
    }

    public function design2(): View
    {
        return view('layouts.order', [
            'slot' => view('orders.design-2'),
            'title' => 'Order Form Design 2',
        ]);
    }

    public function design3(): View
    {
        $currencies = order_form_currencies();
        return view('layouts.order', [
            'slot' => view('orders.design-3', ['currencies' => $currencies]),
            'title' => 'Order Form Design 3',
        ]);
    }
}
