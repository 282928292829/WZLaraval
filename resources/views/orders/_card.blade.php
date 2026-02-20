@php
    $highlight = $highlight ?? false;
    $muted     = $muted ?? false;
    $desktop   = $desktop ?? false;

    $statusClasses = match ($order->status) {
        'pending'       => 'bg-yellow-50 text-yellow-700 ring-yellow-200',
        'needs_payment' => 'bg-red-50 text-red-700 ring-red-200',
        'processing'    => 'bg-blue-50 text-blue-700 ring-blue-200',
        'purchasing'    => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'shipped'       => 'bg-purple-50 text-purple-700 ring-purple-200',
        'delivered'     => 'bg-teal-50 text-teal-700 ring-teal-200',
        'completed'     => 'bg-green-50 text-green-700 ring-green-200',
        'cancelled'     => 'bg-gray-100 text-gray-500 ring-gray-200',
        'on_hold'       => 'bg-orange-50 text-orange-700 ring-orange-200',
        default         => 'bg-gray-100 text-gray-500 ring-gray-200',
    };
@endphp

@if ($desktop)
    {{-- ── Desktop Kanban card ─────────────────────────────────────────── --}}
    <a href="{{ route('orders.show', $order->id) }}"
       class="block bg-white rounded-2xl border shadow-sm hover:shadow-md transition-all group
              {{ $highlight ? 'border-red-200 hover:border-red-300' : 'border-gray-100 hover:border-gray-200' }}
              {{ $muted ? 'opacity-75 hover:opacity-100' : '' }}">
        <div class="p-4 space-y-3">
            {{-- Order number + date --}}
            <div class="flex items-start justify-between gap-2">
                <span class="text-sm font-semibold text-gray-900 group-hover:text-primary-600 transition-colors truncate">
                    #{{ $order->order_number }}
                </span>
                <span class="shrink-0 text-xs text-gray-400 tabular-nums">
                    {{ $order->created_at->format('M j') }}
                </span>
            </div>

            {{-- Status badge --}}
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                {{ $order->statusLabel() }}
            </span>

            {{-- Items count + action note --}}
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">
                    {{ $order->items_count }}
                    {{ app()->getLocale() === 'ar' ? 'منتج' : Str::plural('item', $order->items_count) }}
                </span>
                @if ($highlight)
                    <span class="text-xs font-medium text-red-500">
                        {{ __('orders.action_needed') }}
                    </span>
                @endif
            </div>

            {{-- Needs-payment prompt --}}
            @if ($order->status === 'needs_payment')
                <div class="rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-xs text-red-700 font-medium">
                    {{ __('orders.needs_payment_note') }}
                </div>
            @elseif ($order->status === 'on_hold')
                <div class="rounded-xl bg-orange-50 border border-orange-100 px-3 py-2 text-xs text-orange-700 font-medium">
                    {{ __('orders.on_hold_note') }}
                </div>
            @endif
        </div>
    </a>

@else
    {{-- ── Mobile list card ────────────────────────────────────────────── --}}
    <a href="{{ route('orders.show', $order->id) }}"
       class="flex items-center gap-3 px-4 py-3.5 bg-white hover:bg-gray-50 transition-colors group
              {{ $highlight ? 'border-s-2 border-red-400' : '' }}">

        {{-- Left: status dot --}}
        <span class="shrink-0 w-2.5 h-2.5 rounded-full
            {{ match($order->status) {
                'completed'     => 'bg-green-400',
                'cancelled'     => 'bg-gray-300',
                'needs_payment' => 'bg-red-400',
                'on_hold'       => 'bg-orange-400',
                'shipped'       => 'bg-purple-400',
                'delivered'     => 'bg-teal-400',
                'purchasing'    => 'bg-indigo-400',
                'processing'    => 'bg-blue-400',
                default         => 'bg-yellow-400',
            } }}"></span>

        {{-- Middle: order info --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-gray-800 truncate">#{{ $order->order_number }}</p>
                @if ($highlight)
                    <span class="shrink-0 text-[10px] font-bold text-red-500 uppercase tracking-wide">
                        {{ __('orders.action_needed') }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-2 mt-0.5">
                <span class="text-xs text-gray-400">{{ $order->created_at->diffForHumans() }}</span>
                <span class="text-gray-300">·</span>
                <span class="text-xs text-gray-400">
                    {{ $order->items_count }}
                    {{ app()->getLocale() === 'ar' ? 'منتج' : Str::plural('item', $order->items_count) }}
                </span>
            </div>
        </div>

        {{-- Right: status badge + chevron --}}
        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                {{ $order->statusLabel() }}
            </span>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-400 transition-colors rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </a>
@endif
