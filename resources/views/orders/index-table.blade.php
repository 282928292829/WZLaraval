@php $listRoute = $listRoute ?? route('orders.index'); $clearFiltersRoute = $clearFiltersRoute ?? route('orders.index'); @endphp
<x-app-layout :minimal-footer="true">
    <div class="max-w-5xl mx-auto px-4 py-4 sm:py-6 space-y-3">

        @if ($orderStats['total'] > 0)
        <div class="flex sm:grid sm:grid-cols-4 sm:justify-items-stretch gap-1.5 sm:gap-2 sm:max-w-4xl sm:mx-auto">
            <div class="flex-1 min-w-0 sm:w-full bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-6 sm:py-5 text-center">
                <div class="text-sm sm:text-3xl font-bold text-gray-800 leading-none">{{ $orderStats['total'] }}</div>
                <div class="text-[9px] sm:text-sm text-gray-500 mt-0.5 leading-tight">{{ __('account.orders_total') }}</div>
            </div>
            <div class="flex-1 min-w-0 sm:w-full bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-6 sm:py-5 text-center">
                <div class="text-sm sm:text-3xl font-bold text-primary-600 leading-none">{{ $orderStats['active'] }}</div>
                <div class="text-[9px] sm:text-sm text-gray-500 mt-0.5 leading-tight">{{ __('account.orders_active') }}</div>
            </div>
            <div class="flex-1 min-w-0 sm:w-full bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-6 sm:py-5 text-center">
                <div class="text-sm sm:text-3xl font-bold text-blue-600 leading-none">{{ $orderStats['shipped'] }}</div>
                <div class="text-[9px] sm:text-sm text-gray-500 mt-0.5 leading-tight">{{ __('account.orders_shipped') }}</div>
            </div>
            <div class="flex-1 min-w-0 sm:w-full bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-6 sm:py-5 text-center">
                <div class="text-sm sm:text-3xl font-bold text-gray-400 leading-none">{{ $orderStats['cancelled'] }}</div>
                <div class="text-[9px] sm:text-sm text-gray-500 mt-0.5 leading-tight">{{ __('account.orders_cancelled') }}</div>
            </div>
        </div>
        @endif

        <div class="flex gap-2">
            @if ($lastOrder)
            <a href="{{ route('orders.show', $lastOrder->id) }}" class="flex-1 flex items-center justify-center gap-1.5 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold py-2.5 sm:py-3 rounded-xl border border-gray-200 transition-colors">
                <span>{{ __('orders.last_order_label') }}</span>
                <svg class="w-4 h-4 text-gray-400 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            @else
            <a href="{{ $listRoute }}" class="flex-1 flex items-center justify-center gap-1.5 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold py-2.5 sm:py-3 rounded-xl border border-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                {{ __('account.quick_my_orders') }}
            </a>
            @endif
            <a href="{{ route('new-order') }}" class="flex-1 flex items-center justify-center gap-1.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2.5 sm:py-3 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('account.quick_new_order') }}
            </a>
        </div>

        <div x-data="{ open: {{ request()->hasAny(['search','status','sort','per_page']) ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-sm font-semibold text-gray-700">
                    {{ __('All Orders') }}
                    @if (request()->hasAny(['search', 'status']))
                        <span class="font-normal text-gray-500">· <a href="{{ $clearFiltersRoute }}" class="text-primary-500 hover:text-primary-600">{{ __('Clear filters') }}</a></span>
                    @endif
                </h2>
                <button type="button" @click="open = !open" class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded-lg transition-colors shrink-0">
                    <template x-if="open"><span>{{ __('▲ Hide') }}</span></template>
                    <template x-if="!open"><span>{{ __('▼ Filter') }}</span></template>
                </button>
            </div>

            <form method="GET" action="{{ $listRoute }}" x-show="open" x-cloak class="mt-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('Search') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Order number...') }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition"></div>
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('Status') }}</label>
                        <select name="status" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach
                        </select></div>
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('Sort') }}</label>
                        <select name="sort" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                            <option value="desc" @selected(request('sort', 'desc') === 'desc')>{{ __('Newest first') }}</option>
                            <option value="asc" @selected(request('sort') === 'asc')>{{ __('Oldest first') }}</option>
                        </select></div>
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ __('Per page') }}</label>
                        <select name="per_page" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                            <option value="10" @selected(request('per_page', '10') === '10')>10</option>
                            <option value="25" @selected(request('per_page') === '25')>25</option>
                            <option value="50" @selected(request('per_page') === '50')>50</option>
                        </select></div>
                </div>
                <div class="px-4 pb-4 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-lg transition-colors">{{ __('Apply') }}</button>
                    <a href="{{ $listRoute }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-semibold rounded-lg transition-colors">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>

        @if ($orders->isEmpty())
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm text-center py-16 px-6">
                <h2 class="text-base font-semibold text-gray-700">{{ request()->hasAny(['search','status']) ? __('No orders found') : __('No orders yet') }}</h2>
                <p class="mt-1.5 text-sm text-gray-400 max-w-xs mx-auto">{{ request()->hasAny(['search','status']) ? __('No orders match your filters. Try different criteria.') : __('Your orders will appear here once you place one.') }}</p>
                @unless (request()->hasAny(['search','status']))
                    <a href="{{ route('new-order') }}" class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Place your first order') }}
                    </a>
                @endunless
            </div>
        @else

            {{-- Table-like: compact rows, same layout as desktop (mobile shows table too) --}}
            <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[320px]">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-start text-[10px] font-semibold text-gray-600 uppercase">{{ __('Order #') }}</th>
                                <th class="px-3 py-2 text-start text-[10px] font-semibold text-gray-600 uppercase">{{ __('Date') }}</th>
                                <th class="px-3 py-2 text-start text-[10px] font-semibold text-gray-600 uppercase">{{ __('Status') }}</th>
                                <th class="px-3 py-2 text-start text-[10px] font-semibold text-gray-600 uppercase w-16">{{ __('Open') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($orders as $order)
                                @php
                                    $borderColor = match ($order->status) {
                                        'completed' => '#22c55e', 'cancelled' => '#ef4444', 'needs_payment' => '#06b6d4', 'on_hold' => '#f97316',
                                        'shipped' => '#10b981', 'delivered' => '#22c55e', 'purchasing' => '#8b5cf6', 'processing' => '#3b82f6', default => '#f59e0b',
                                    };
                                    $statusClasses = match ($order->status) {
                                        'pending' => 'bg-yellow-50 text-yellow-700 ring-yellow-200', 'needs_payment' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
                                        'processing' => 'bg-blue-50 text-blue-700 ring-blue-200', 'purchasing' => 'bg-purple-50 text-purple-700 ring-purple-200',
                                        'shipped' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'delivered' => 'bg-green-50 text-green-700 ring-green-200',
                                        'completed' => 'bg-green-50 text-green-700 ring-green-200', 'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
                                        'on_hold' => 'bg-orange-50 text-orange-700 ring-orange-200', default => 'bg-gray-50 text-gray-600 ring-gray-200',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition-colors cursor-pointer" onclick="location.href='{{ route('orders.show', $order->id) }}'"
                                    style="border-inline-start-width: 3px; border-inline-start-style: solid; border-inline-start-color: {{ $borderColor }};">
                                    <td class="px-3 py-2 font-bold text-primary-600 text-xs">{{ $order->order_number }}</td>
                                    <td class="px-3 py-2 text-gray-600 text-xs">{{ $order->created_at->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium ring-1 ring-inset {{ $statusClasses }}">{{ $order->statusLabel() }}</span>
                                    </td>
                                    <td class="px-3 py-2" onclick="event.stopPropagation()">
                                        <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center px-2 py-1 text-[10px] font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded">{{ __('Open') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($orders->hasPages())
                <div class="flex justify-center">{{ $orders->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
