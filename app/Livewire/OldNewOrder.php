<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;

/**
 * Standalone reference layout — uses exact old new-order blade from Mar 7, 2026 (8abf26c).
 * Layout: Option 1 (table on desktop, cards on mobile). Uses layouts.order (with footer).
 * Route: /old-new-order. For AI reference when building the 5 new layouts.
 * Delete this file, its route, and old-new-order.blade.php when no longer needed.
 */
#[Layout('layouts.order')]
class OldNewOrder extends NewOrder
{
    public function mount(?int $duplicate_from = null, ?int $edit = null, string $product_url = ''): void
    {
        parent::mount($duplicate_from, $edit, $product_url);
        $this->activeLayout = '1';
    }

    public function render(): \Illuminate\View\View
    {
        $parent = parent::render();
        return view('livewire.old-new-order', $parent->getData());
    }
}
