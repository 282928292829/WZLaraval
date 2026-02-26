{{-- Design 4: Stacked labels per row (Order #, Date, Status, Open) --}}
<x-app-layout :minimal-footer="true">
    <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-2 text-xs">
            <span class="text-gray-500">{{ __('orders.all_orders') }} — Design 4</span>
            <div class="flex gap-2">
                <a href="{{ route('orders.index') }}" class="text-gray-500 hover:text-gray-700">Main</a>
                <a href="{{ route('orders.design-1') }}" class="text-primary-600">1</a>
                <a href="{{ route('orders.design-2') }}" class="text-primary-600">2</a>
                <a href="{{ route('orders.design-3') }}" class="text-primary-600">3</a>
                <a href="{{ route('orders.design-5') }}" class="text-primary-600">5</a>
            </div>
        </div>

        @if ($orderStats['total'] > 0)
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xl font-bold text-gray-800">{{ $orderStats['total'] }}</div>
                <div class="text-[10px] text-gray-500">{{ __('account.orders_total') }}</div>
            </div>
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xl font-bold text-primary-600">{{ $orderStats['active'] }}</div>
                <div class="text-[10px] text-gray-500">{{ __('account.orders_active') }}</div>
            </div>
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xl font-bold text-blue-600">{{ $orderStats['shipped'] }}</div>
                <div class="text-[10px] text-gray-500">{{ __('account.orders_shipped') }}</div>
            </div>
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xl font-bold text-gray-400">{{ $orderStats['cancelled'] }}</div>
                <div class="text-[10px] text-gray-500">{{ __('account.orders_cancelled') }}</div>
            </div>
        </div>
        @endif

        <div class="flex gap-2">
            <a href="{{ route('new-order') }}" class="flex-1 py-2.5 text-center text-sm font-medium text-white bg-primary-600 rounded-lg">{{ __('account.quick_new_order') }}</a>
            @if ($lastOrder)<a href="{{ route('orders.show', $lastOrder->id) }}" class="flex-1 py-2.5 text-center text-sm font-medium text-gray-700 bg-gray-100 rounded-lg">{{ __('orders.last_order_label') }}</a>@endif
        </div>

        <div x-data="{ open: {{ request()->hasAny(['search','status','sort','per_page']) ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="text-sm text-primary-600 font-medium">{{ __('orders.filter_show') }} / {{ __('orders.filter_hide') }}</button>
            <form method="GET" action="{{ $formAction }}" x-show="open" x-cloak class="mt-3 p-4 bg-gray-50 rounded-lg space-y-3">
                <div class="flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('orders.search_placeholder') }}" class="px-3 py-2 text-sm border rounded-lg">
                    <select name="status" class="px-3 py-2 text-sm border rounded-lg"><option value="">{{ __('orders.all_statuses') }}</option>@foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach</select>
                    <select name="sort" class="px-3 py-2 text-sm border rounded-lg"><option value="desc" @selected(request('sort','desc')==='desc')>{{ __('orders.sort_newest') }}</option><option value="asc" @selected(request('sort')==='asc')>{{ __('orders.sort_oldest') }}</option></select>
                    <select name="per_page" class="px-3 py-2 text-sm border rounded-lg"><option value="10" @selected(request('per_page','10')==='10')>10</option><option value="25" @selected(request('per_page')==='25')>25</option><option value="50" @selected(request('per_page')==='50')>50</option></select>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg">{{ __('orders.filter_apply') }}</button>
                    <a href="{{ $clearFiltersUrl }}" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg">{{ __('orders.filter_reset') }}</a>
                </div>
            </form>
        </div>

        @if ($orders->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>{{ request()->hasAny(['search','status']) ? __('orders.no_orders_found') : __('orders.no_orders_yet') }}</p>
            @unless (request()->hasAny(['search','status']))<a href="{{ route('new-order') }}" class="mt-4 inline-block px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg">{{ __('orders.place_first_order') }}</a>@endunless
        </div>
        @else
        <div class="space-y-4">
            @foreach ($orders as $order)
            @php
                $sc = match ($order->status) { 'pending'=>'bg-amber-100 text-amber-800','needs_payment'=>'bg-cyan-100 text-cyan-800','processing'=>'bg-blue-100 text-blue-800','purchasing'=>'bg-purple-100 text-purple-800','shipped'=>'bg-emerald-100 text-emerald-800','delivered','completed'=>'bg-green-100 text-green-800','cancelled'=>'bg-red-100 text-red-800','on_hold'=>'bg-orange-100 text-orange-800', default=>'bg-gray-100 text-gray-700' };
            @endphp
            <a href="{{ route('orders.show', $order->id) }}" class="block p-5 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow transition-all">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <span class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('orders.col_number') }}</span>
                        <span class="text-base font-bold text-primary-600">{{ $order->order_number }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('orders.col_date') }}</span>
                        <span class="text-base text-gray-700">{{ $order->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('orders.col_status') }}</span>
                        <span class="inline-block px-2.5 py-1 text-xs font-medium rounded-full {{ $sc }}">{{ $order->statusLabel() }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('orders.col_actions') }}</span>
                        <span class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 text-sm font-semibold text-primary-600 bg-primary-50 border border-primary-200 rounded-lg">{{ __('orders.action_open') }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @if ($orders->hasPages())<div class="pt-4">{{ $orders->links() }}</div>@endif
        @endif

    </div>
</x-app-layout>
