@php
    $icon    = $icon ?? 'clock';
    $message = $message ?? __('orders.no_orders_in_group');

    $iconPath = match ($icon) {
        'check'   => 'M5 13l4 4L19 7',
        'archive' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
        default   => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    };
@endphp
<div class="flex flex-col items-center justify-center py-14 px-6 text-center">
    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
        </svg>
    </div>
    <p class="text-sm text-gray-400">{{ $message }}</p>
</div>
