{{-- Design 3: Table-style with labels (Order #, Date, Status, Open) --}}
<x-app-layout :minimal-footer="true">
    <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-2 text-xs">
            <span class="text-gray-500">{{ __('orders.all_orders') }} — Design 3</span>
            <div class="flex gap-2">
                <a href="{{ route('orders.index') }}" class="text-gray-500 hover:text-gray-700">Main</a>
                <a href="{{ route('orders.design-1') }}" class="text-primary-600">1</a>
                <a href="{{ route('orders.design-2') }}" class="text-primary-600">2</a>
                <a href="{{ route('orders.design-4') }}" class="text-primary-600">4</a>
                <a href="{{ route('orders.design-5') }}" class="text-primary-600">5</a>
            </div>
        </div>

        @if ($orderStats['total'] > 0)
        <div class="flex gap-3 overflow-x-auto pb-1">
            <span class="shrink-0 text-sm"><strong>{{ $orderStats['total'] }}</strong> {{ __('account.orders_total') }}</span>
            <span class="shrink-0 text-sm"><strong class="text-primary-600">{{ $orderStats['active'] }}</strong> {{ __('account.orders_active') }}</span>
            <span class="shrink-0 text-sm"><strong class="text-blue-600">{{ $orderStats['shipped'] }}</strong> {{ __('account.orders_shipped') }}</span>
            <span class="shrink-0 text-sm"><strong class="text-gray-400">{{ $orderStats['cancelled'] }}</strong> {{ __('account.orders_cancelled') }}</span>
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
        <div class="space-y-3">
            @foreach ($orders as $order)
            @php
                $sc = match ($order->status) { 'pending'=>'bg-amber-100 text-amber-800','needs_payment'=>'bg-cyan-100 text-cyan-800','processing'=>'bg-blue-100 text-blue-800','purchasing'=>'bg-purple-100 text-purple-800','shipped'=>'bg-emerald-100 text-emerald-800','delivered','completed'=>'bg-green-100 text-green-800','cancelled'=>'bg-red-100 text-red-800','on_hold'=>'bg-orange-100 text-orange-800', default=>'bg-gray-100 text-gray-700' };
            @endphp
            <a href="{{ route('orders.show', $order->id) }}" class="block p-4 bg-white rounded-lg border border-gray-100 hover:border-primary-200 transition-colors">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-center">
                    <div>
                        <span class="block text-[10px] font-medium text-gray-400 uppercase">{{ __('orders.col_number') }}</span>
                        <span class="font-semibold text-gray-900">{{ $order->order_number }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-medium text-gray-400 uppercase">{{ __('orders.col_date') }}</span>
                        <span class="text-gray-700">{{ $order->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-medium text-gray-400 uppercase">{{ __('orders.col_status') }}</span>
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded {{ $sc }}">{{ $order->statusLabel() }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-medium text-gray-400 uppercase">{{ __('orders.col_actions') }}</span>
                        <span class="inline-block px-3 py-1.5 text-xs font-semibold text-primary-600 bg-primary-50 border border-primary-200 rounded-lg">{{ __('orders.action_open') }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @if ($orders->hasPages())<div class="pt-4">{{ $orders->links() }}</div>@endif
        @endif

    </div>
</x-app-layout>
