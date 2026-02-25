<x-app-layout :minimal-footer="true">
    <div class="max-w-3xl mx-auto px-4 py-4 sm:py-6 space-y-3">

        {{-- Order stats (compact, one row on mobile) --}}
        @if ($orderStats['total'] > 0)
        <div class="flex sm:grid sm:grid-cols-4 gap-1.5 sm:gap-2">
            <div class="flex-1 min-w-0 sm:flex-none bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-3 sm:py-2 text-center">
                <div class="text-sm sm:text-xl font-bold text-gray-800 leading-none">{{ $orderStats['total'] }}</div>
                <div class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5 leading-tight truncate">{{ __('account.orders_total') }}</div>
            </div>
            <div class="flex-1 min-w-0 sm:flex-none bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-3 sm:py-2 text-center">
                <div class="text-sm sm:text-xl font-bold text-primary-600 leading-none">{{ $orderStats['active'] }}</div>
                <div class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5 leading-tight truncate">{{ __('account.orders_active') }}</div>
            </div>
            <div class="flex-1 min-w-0 sm:flex-none bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-3 sm:py-2 text-center">
                <div class="text-sm sm:text-xl font-bold text-blue-600 leading-none">{{ $orderStats['shipped'] }}</div>
                <div class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5 leading-tight truncate">{{ __('account.orders_shipped') }}</div>
            </div>
            <div class="flex-1 min-w-0 sm:flex-none bg-white rounded-lg border border-gray-100 shadow-sm px-2 py-1.5 sm:px-3 sm:py-2 text-center">
                <div class="text-sm sm:text-xl font-bold text-gray-400 leading-none">{{ $orderStats['cancelled'] }}</div>
                <div class="text-[9px] sm:text-[10px] text-gray-500 mt-0.5 leading-tight truncate">{{ __('account.orders_cancelled') }}</div>
            </div>
        </div>
        @endif

        {{-- Quick actions: New Order + (Last order OR My Orders) --}}
        <div class="flex gap-2">
            <a href="{{ route('new-order') }}"
               class="flex-1 flex items-center justify-center gap-1.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2.5 sm:py-3 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('account.quick_new_order') }}
            </a>
            @if ($lastOrder)
            <a href="{{ route('orders.show', $lastOrder->id) }}"
               class="flex-1 flex items-center justify-center gap-1.5 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold py-2.5 sm:py-3 rounded-xl border border-gray-200 transition-colors">
                <span>{{ __('orders.last_order_label') }}</span>
                <svg class="w-4 h-4 text-gray-400 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @else
            <a href="{{ route('orders.index') }}"
               class="flex-1 flex items-center justify-center gap-1.5 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold py-2.5 sm:py-3 rounded-xl border border-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                {{ __('account.quick_my_orders') }}
            </a>
            @endif
        </div>

        {{-- ── All orders ─────────────────────────────────────────────────── --}}
        <h2 class="text-sm font-semibold text-gray-700">
            {{ __('All Orders') }}
            @if (request()->hasAny(['search', 'status']))
                <span class="font-normal text-gray-500">· <a href="{{ route('orders.index') }}" class="text-primary-500 hover:text-primary-600">{{ __('Clear filters') }}</a></span>
            @endif
        </h2>

        {{-- ── Filters card ──────────────────────────────────────────────── --}}
        <div x-data="{ open: {{ request()->hasAny(['search','status','sort','per_page']) ? 'true' : 'false' }} }"
             class="bg-white rounded-xl shadow-sm border border-gray-100">

            <div class="flex items-center justify-between px-4 py-3">
                <span class="text-sm font-semibold text-gray-700">
                    {{ __('Filters') }}
                </span>
                <button type="button" @click="open = !open"
                        class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded-lg transition-colors">
                    <template x-if="open"><span>{{ __('▲ Hide') }}</span></template>
                    <template x-if="!open"><span>{{ __('▼ Filter') }}</span></template>
                </button>
            </div>

            <form method="GET" action="{{ route('orders.index') }}"
                  x-show="open" x-cloak
                  class="border-t border-gray-50">
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                    {{-- Search --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            {{ __('Search') }}
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('Order number...') }}"
                               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            {{ __('Status') }}
                        </label>
                        <select name="status"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sort --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            {{ __('Sort') }}
                        </label>
                        <select name="sort"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                            <option value="desc" @selected(request('sort', 'desc') === 'desc')>
                                {{ __('Newest first') }}
                            </option>
                            <option value="asc" @selected(request('sort') === 'asc')>
                                {{ __('Oldest first') }}
                            </option>
                        </select>
                    </div>

                    {{-- Per page --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            {{ __('Per page') }}
                        </label>
                        <select name="per_page"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-400 focus:outline-none transition">
                            <option value="10"  @selected(request('per_page', '10') === '10')>10</option>
                            <option value="25"  @selected(request('per_page') === '25')>25</option>
                            <option value="50"  @selected(request('per_page') === '50')>50</option>
                        </select>
                    </div>

                </div>
                <div class="px-4 pb-4 flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-lg transition-colors">
                        {{ __('Apply') }}
                    </button>
                    <a href="{{ route('orders.index') }}"
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-semibold rounded-lg transition-colors">
                        {{ __('Reset') }}
                    </a>
                </div>
            </form>
        </div>

        {{-- ── Orders list ───────────────────────────────────────────────── --}}
        @if ($orders->isEmpty())
            {{-- Empty state --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm text-center py-16 px-6">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/>
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-gray-700">
                    {{ request()->hasAny(['search','status']) ? __('No orders found') : __('No orders yet') }}
                </h2>
                <p class="mt-1.5 text-sm text-gray-400 max-w-xs mx-auto leading-relaxed">
                    @if (request()->hasAny(['search','status']))
                        {{ __('No orders match your filters. Try different criteria.') }}
                    @else
                        {{ __('Your orders will appear here once you place one.') }}
                    @endif
                </p>
                @unless (request()->hasAny(['search','status']))
                    <a href="{{ route('new-order') }}"
                       class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Place your first order') }}
                    </a>
                @endunless
            </div>

        @else

            {{-- ── Mobile cards (< md) ───────────────────────────────────── --}}
            <div class="md:hidden space-y-3">
                @foreach ($orders as $order)
                    @php
                        $borderColor = match ($order->status) {
                            'completed'     => '#22c55e',
                            'cancelled'     => '#ef4444',
                            'needs_payment' => '#06b6d4',
                            'on_hold'       => '#f97316',
                            'shipped'       => '#10b981',
                            'delivered'     => '#22c55e',
                            'purchasing'    => '#8b5cf6',
                            'processing'    => '#3b82f6',
                            default         => '#f59e0b',
                        };
                        $statusClasses = match ($order->status) {
                            'pending'       => 'bg-yellow-50 text-yellow-700 ring-yellow-200',
                            'needs_payment' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
                            'processing'    => 'bg-blue-50 text-blue-700 ring-blue-200',
                            'purchasing'    => 'bg-purple-50 text-purple-700 ring-purple-200',
                            'shipped'       => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'delivered'     => 'bg-green-50 text-green-700 ring-green-200',
                            'completed'     => 'bg-green-50 text-green-700 ring-green-200',
                            'cancelled'     => 'bg-red-50 text-red-700 ring-red-200',
                            'on_hold'       => 'bg-orange-50 text-orange-700 ring-orange-200',
                            default         => 'bg-gray-50 text-gray-600 ring-gray-200',
                        };
                    @endphp
                    <a href="{{ route('orders.show', $order->id) }}"
                       class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all overflow-hidden"
                       style="border-inline-start-width: 4px; border-inline-start-style: solid; border-inline-start-color: {{ $borderColor }};">
                        <div class="p-4 space-y-3">

                            {{-- Row 1: Order # + Date --}}
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wide block">
                                        {{ __('Order #') }}
                                    </span>
                                    <span class="text-sm font-bold text-primary-600">{{ $order->order_number }}</span>
                                </div>
                                <div class="text-end">
                                    <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wide block">
                                        {{ __('Date') }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-600">{{ $order->created_at->format('Y-m-d') }}</span>
                                </div>
                            </div>

                            {{-- Row 2: Status --}}
                            <div class="flex items-center pt-2 border-t border-gray-50">
                                <div>
                                    <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wide block mb-1">
                                        {{ __('Status') }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </div>
                            </div>

                            {{-- Open button --}}
                            <div class="pt-1">
                                <span class="block w-full text-center py-2 text-sm font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded-lg transition-colors">
                                    {{ __('Open') }}
                                </span>
                            </div>

                        </div>
                    </a>
                @endforeach
            </div>

            {{-- ── Desktop table (>= md) ─────────────────────────────────── --}}
            <div class="hidden md:block bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 uppercase tracking-wide">
                                {{ __('Order #') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 uppercase tracking-wide">
                                {{ __('Date') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 uppercase tracking-wide">
                                {{ __('Status') }}
                            </th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 uppercase tracking-wide w-24">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($orders as $order)
                            @php
                                $borderColor = match ($order->status) {
                                    'completed'     => '#22c55e',
                                    'cancelled'     => '#ef4444',
                                    'needs_payment' => '#06b6d4',
                                    'on_hold'       => '#f97316',
                                    'shipped'       => '#10b981',
                                    'delivered'     => '#22c55e',
                                    'purchasing'    => '#8b5cf6',
                                    'processing'    => '#3b82f6',
                                    default         => '#f59e0b',
                                };
                                $statusClasses = match ($order->status) {
                                    'pending'       => 'bg-yellow-50 text-yellow-700 ring-yellow-200',
                                    'needs_payment' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
                                    'processing'    => 'bg-blue-50 text-blue-700 ring-blue-200',
                                    'purchasing'    => 'bg-purple-50 text-purple-700 ring-purple-200',
                                    'shipped'       => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    'delivered'     => 'bg-green-50 text-green-700 ring-green-200',
                                    'completed'     => 'bg-green-50 text-green-700 ring-green-200',
                                    'cancelled'     => 'bg-red-50 text-red-700 ring-red-200',
                                    'on_hold'       => 'bg-orange-50 text-orange-700 ring-orange-200',
                                    default         => 'bg-gray-50 text-gray-600 ring-gray-200',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/60 transition-colors cursor-pointer"
                                onclick="location.href='{{ route('orders.show', $order->id) }}'"
                                style="border-inline-start-width: 3px; border-inline-start-style: solid; border-inline-start-color: {{ $borderColor }};">
                                <td class="px-4 py-3 font-bold text-primary-600">{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $order->created_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3" onclick="event.stopPropagation()">
                                    <a href="{{ route('orders.show', $order->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 border border-primary-200 rounded-lg transition-colors">
                                        {{ __('Open') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── Pagination ─────────────────────────────────────────────── --}}
            @if ($orders->hasPages())
                <div class="flex justify-center">
                    {{ $orders->links() }}
                </div>
            @endif

        @endif

    </div>

</x-app-layout>
