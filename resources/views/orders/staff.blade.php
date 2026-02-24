<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('All Orders') }}</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ $orders->total() }} {{ __('orders.orders') }}
                    @if (request()->hasAny(['search','status','from','to']))
                        — <a href="{{ route('orders.index') }}"
                             class="text-primary-500 hover:text-primary-600 font-medium transition-colors">
                            {{ __('staff.clear_filters') }}
                        </a>
                    @endif
                </p>
            </div>
            @can('export-csv')
                <a href="{{ route('orders.index', array_merge(request()->only(['search','status','from','to']), ['export' => 'csv'])) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ __('Export CSV') }}
                </a>
            @endcan
        </div>
    </x-slot>

    {{-- ── Bulk actions + filters + table ──────────────────────────────────── --}}
    <div x-data="{
        selected: [],
        bulkStatus: '',
        filtersOpen: {{ request()->hasAny(['status','from','to']) ? 'true' : 'false' }},
        get allIds() {
            return [...document.querySelectorAll('.order-checkbox')].map(el => el.value);
        },
        selectAll: false,
        toggleAll() {
            if (this.selectAll) {
                this.selected = this.allIds;
            } else {
                this.selected = [];
            }
        },
        toggleRow(id) {
            const idx = this.selected.indexOf(String(id));
            if (idx === -1) {
                this.selected.push(String(id));
            } else {
                this.selected.splice(idx, 1);
            }
            this.selectAll = this.selected.length === this.allIds.length;
        }
    }">

        {{-- ── Bulk action bar (visible when rows selected) ──────────────── --}}
        <div x-show="selected.length > 0"
             x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="sticky top-0 z-30 bg-primary-600 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center gap-3 flex-wrap">
                <span class="text-sm font-semibold" x-text="selected.length + ' {{ __('staff.selected') }}'"></span>
                <div class="flex-1"></div>
                <span class="text-sm text-primary-200">{{ __('orders.bulk_change_status') }}:</span>
                <select x-model="bulkStatus"
                        class="text-sm bg-primary-700 text-white border border-primary-500 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-white focus:outline-none">
                    <option value="">— {{ __('orders.all_statuses') }} —</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button"
                        @click="if (bulkStatus && selected.length) { $refs.bulkForm.submit(); }"
                        :disabled="!bulkStatus"
                        class="px-4 py-1.5 bg-white text-primary-700 font-semibold text-sm rounded-lg hover:bg-primary-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    {{ __('orders.bulk_apply') }}
                </button>
                <button type="button"
                        @click="selected = []; selectAll = false;"
                        class="p-1.5 text-primary-200 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Hidden bulk update form --}}
        <form id="bulk-form" method="POST" action="{{ route('orders.bulk-update') }}" x-ref="bulkForm">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="order_ids[]" :value="id">
            </template>
            <input type="hidden" name="new_status" x-model="bulkStatus">
        </form>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-4">

            {{-- ── Session flash ──────────────────────────────────────────── --}}
            @if (session('success'))
                <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                    <svg class="w-4 h-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- ── Search + filter bar ─────────────────────────────────── --}}
            <form method="GET" action="{{ route('orders.index') }}" class="space-y-3">
                <div class="flex gap-2">
                    {{-- Search input --}}
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="search"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="{{ __('orders.search_placeholder') }}"
                               class="w-full ps-9 pe-4 py-2.5 text-sm bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-400 focus:border-primary-400 focus:outline-none transition-colors">
                    </div>

                    {{-- Status quick filter --}}
                    <select name="status"
                            onchange="this.form.submit()"
                            class="text-sm bg-white border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 focus:outline-none transition-colors">
                        <option value="">{{ __('orders.all_statuses') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    {{-- Toggle advanced filters --}}
                    <button type="button"
                            @click="filtersOpen = !filtersOpen"
                            :class="filtersOpen ? 'bg-primary-50 border-primary-300 text-primary-700' : 'bg-white border-gray-200 text-gray-600'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-sm border rounded-xl hover:border-primary-300 hover:text-primary-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        <span class="hidden sm:inline">{{ __('orders.filters') }}</span>
                    </button>

                    {{-- Search submit --}}
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors">
                        {{ __('staff.search') }}
                    </button>
                </div>

                {{-- Advanced filters (collapsible) --}}
                <div x-show="filtersOpen"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100">

                    {{-- From date --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('orders.from_date') }}</label>
                        <input type="date" name="from" value="{{ request('from') }}"
                               class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 focus:outline-none">
                    </div>

                    {{-- To date --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('orders.to_date') }}</label>
                        <input type="date" name="to" value="{{ request('to') }}"
                               class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 focus:outline-none">
                    </div>

                    {{-- Sort --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            {{ __('staff.sort') }}
                        </label>
                        <select name="sort"
                                class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 focus:outline-none">
                            <option value="desc" @selected($sort === 'desc')>{{ __('orders.newest_first') }}</option>
                            <option value="asc" @selected($sort === 'asc')>{{ __('orders.oldest_first') }}</option>
                        </select>
                    </div>

                    {{-- Per page --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('orders.per_page') }}</label>
                        <select name="per_page"
                                class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-400 focus:border-primary-400 focus:outline-none">
                            @foreach ([25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                            <option value="0" @selected($perPage === 0)>{{ __('orders.per_page_all') }}</option>
                        </select>
                    </div>
                </div>
            </form>

            {{-- ── Orders table (desktop) ──────────────────────────────────── --}}
            @if ($orders->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">{{ __('No orders yet') }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ request()->hasAny(['search','status','from','to'])
                            ? (__('staff.no_orders_match_your_filters'))
                            : (__('staff.no_orders_in_the_system')) }}
                    </p>
                    @if (request()->hasAny(['search','status','from','to']))
                        <a href="{{ route('orders.index') }}"
                           class="mt-4 text-sm text-primary-500 hover:text-primary-600 font-medium transition-colors">
                            {{ __('staff.clear_filters') }}
                        </a>
                    @endif
                </div>
            @else

                {{-- Desktop table --}}
                <div class="hidden md:block bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th class="ps-4 pe-2 py-3 w-10">
                                        <input type="checkbox"
                                               x-model="selectAll"
                                               @change="toggleAll()"
                                               class="rounded border-gray-300 text-primary-500 focus:ring-primary-400 cursor-pointer">
                                    </th>
                                    <th class="px-3 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('staff.order') }}
                                    </th>
                                    <th class="px-3 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('staff.customer') }}
                                    </th>
                                    <th class="px-3 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('staff.date') }}
                                    </th>
                                    <th class="px-3 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('staff.status') }}
                                    </th>
                                    <th class="px-3 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('staff.total') }}
                                    </th>
                                    <th class="px-3 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('staff.payment') }}
                                    </th>
                                    <th class="ps-3 pe-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($orders as $order)
                                    @php
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
                                    <tr class="hover:bg-gray-50/60 transition-colors cursor-pointer group"
                                        onclick="if(!event.target.closest('input,a,button')) window.location='{{ route('orders.show', $order->id) }}'">
                                        <td class="ps-4 pe-2 py-3" onclick="event.stopPropagation()">
                                            <input type="checkbox"
                                                   class="order-checkbox rounded border-gray-300 text-primary-500 focus:ring-primary-400 cursor-pointer"
                                                   value="{{ $order->id }}"
                                                   :checked="selected.includes('{{ $order->id }}')"
                                                   @change="toggleRow('{{ $order->id }}')">
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                                                    {{ $order->order_number }}
                                                </span>
                                                @if ($order->items_count)
                                                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">
                                                        {{ $order->items_count }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-gray-800 truncate max-w-[160px]">{{ $order->user?->name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 truncate max-w-[160px]">{{ $order->user?->email ?? '' }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-gray-500 whitespace-nowrap">
                                            <p>{{ $order->created_at->format('Y/m/d') }}</p>
                                            <p class="text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                                                {{ $order->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-gray-600 tabular-nums whitespace-nowrap">
                                            @if ($order->total_amount)
                                                <span class="font-medium">{{ number_format($order->total_amount, 2) }}</span>
                                                <span class="text-xs text-gray-400">{{ $order->currency }}</span>
                                            @elseif ($order->subtotal)
                                                <span class="text-gray-400">~{{ number_format($order->subtotal, 2) }}</span>
                                                <span class="text-xs text-gray-400">{{ $order->currency }}</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @if ($order->is_paid)
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                                    <svg class="w-3.5 h-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    {{ __('staff.paid') }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">{{ __('staff.unpaid') }}</span>
                                            @endif
                                        </td>
                                        <td class="ps-3 pe-4 py-3">
                                            <a href="{{ route('orders.show', $order->id) }}"
                                               class="text-xs font-medium text-primary-500 hover:text-primary-700 whitespace-nowrap transition-colors">
                                                {{ __('orders.view') }} →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile: card list --}}
                <div class="md:hidden bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                    @foreach ($orders as $order)
                        @php
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
                        <a href="{{ route('orders.show', $order->id) }}"
                           class="flex items-start gap-3 px-4 py-4 hover:bg-gray-50 transition-colors group">
                            {{-- Checkbox --}}
                            <div class="pt-0.5" onclick="event.preventDefault(); event.stopPropagation();">
                                <input type="checkbox"
                                       class="order-checkbox rounded border-gray-300 text-primary-500 focus:ring-primary-400 cursor-pointer"
                                       value="{{ $order->id }}"
                                       :checked="selected.includes('{{ $order->id }}')"
                                       @change.stop="toggleRow('{{ $order->id }}')">
                            </div>
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                                            {{ $order->order_number }}
                                        </span>
                                        @if ($order->items_count)
                                            <span class="inline-flex items-center justify-center min-w-[18px] h-4.5 px-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">
                                                {{ $order->items_count }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                                        {{ $order->statusLabel() }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                    <span class="font-medium truncate max-w-[140px]">{{ $order->user?->name ?? '—' }}</span>
                                    <span class="text-gray-300">·</span>
                                    <span>{{ $order->created_at->format('Y/m/d') }}</span>
                                    @if ($order->is_paid)
                                        <span class="text-gray-300">·</span>
                                        <span class="text-green-600 font-medium">{{ __('staff.paid') }}</span>
                                    @endif
                                </div>
                            </div>
                            {{-- Chevron --}}
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-400 transition-colors shrink-0 mt-0.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endforeach
                </div>

                {{-- ── Pagination ──────────────────────────────────────────── --}}
                @if ($orders->hasPages())
                    <div class="flex items-center justify-between gap-4 pt-1">
                        <p class="text-xs text-gray-400">
                            {{ __('Showing') }} {{ $orders->firstItem() }}–{{ $orders->lastItem() }} {{ __('of') }} {{ $orders->total() }}
                        </p>
                        <div class="flex items-center gap-1">
                            {{-- Previous --}}
                            @if ($orders->onFirstPage())
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-300 cursor-not-allowed">
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </span>
                            @else
                                <a href="{{ $orders->previousPageUrl() }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </a>
                            @endif

                            {{-- Page numbers --}}
                            @php
                                $window = 2;
                                $currentPage = $orders->currentPage();
                                $lastPage = $orders->lastPage();
                                $pages = collect(range(1, $lastPage))
                                    ->filter(fn($p) => $p === 1 || $p === $lastPage || abs($p - $currentPage) <= $window);
                            @endphp

                            @php $prev = null; @endphp
                            @foreach ($pages as $page)
                                @if ($prev !== null && $page - $prev > 1)
                                    <span class="inline-flex items-center justify-center w-8 h-8 text-xs text-gray-400">…</span>
                                @endif
                                <a href="{{ $orders->url($page) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm transition-colors
                                          {{ $page === $currentPage
                                              ? 'bg-primary-500 text-white font-semibold shadow-sm'
                                              : 'text-gray-600 hover:bg-gray-100' }}">
                                    {{ $page }}
                                </a>
                                @php $prev = $page; @endphp
                            @endforeach

                            {{-- Next --}}
                            @if ($orders->hasMorePages())
                                <a href="{{ $orders->nextPageUrl() }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            @else
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-300 cursor-not-allowed">
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

            @endif {{-- end orders not empty --}}

        </div>{{-- end max-w container --}}
    </div>{{-- end x-data --}}

</x-app-layout>
