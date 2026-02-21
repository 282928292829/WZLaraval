@php
    // Build the gallery array for the lightbox (all image URLs on this page)
    $lightboxImages = [];
    foreach ($order->items as $item) {
        if ($item->image_path) {
            $lightboxImages[] = Storage::disk('public')->url($item->image_path);
        }
    }
    foreach ($order->files->whereNull('comment_id') as $file) {
        if ($file->isImage()) {
            $lightboxImages[] = $file->url();
        }
    }
    $allVisibleComments = $order->comments->filter(fn ($c) => $c->isVisibleTo(auth()->user()));
    foreach ($allVisibleComments as $c) {
        $cf = $order->files->where('comment_id', $c->id)->first();
        if ($cf && $cf->isImage()) {
            $lightboxImages[] = $cf->url();
        }
    }
@endphp

@push('scripts')
<script>window.orderLightboxImages = @json($lightboxImages);</script>
@endpush

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

    $statusDot = match ($order->status) {
        'pending'       => 'bg-yellow-400',
        'needs_payment' => 'bg-red-400',
        'processing'    => 'bg-blue-400',
        'purchasing'    => 'bg-indigo-400',
        'shipped'       => 'bg-purple-400',
        'delivered'     => 'bg-teal-400',
        'completed'     => 'bg-green-400',
        'cancelled'     => 'bg-gray-400',
        'on_hold'       => 'bg-orange-400',
        default         => 'bg-gray-400',
    };

    $timelineIcon = fn ($type) => match ($type) {
        'status_change' => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
        'comment'       => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
        'note'          => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        'file_upload'   => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>',
        'payment'       => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
        'merge'         => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>',
        default         => '<span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>',
    };

    $visibleComments = $order->comments->filter(
        fn ($c) => $c->isVisibleTo(auth()->user())
    );
@endphp

<x-app-layout>

{{-- ── Page header ──────────────────────────────────────────────────────────── --}}
<div class="max-w-4xl mx-auto px-4 py-4 space-y-5">

    {{-- Flash --}}
    @if (session('success'))
        <div class="flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ── Order header card ─────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="space-y-1">
                {{-- Back + number --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('orders.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-4 h-4 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <span class="text-lg font-bold text-gray-900">
                        {{ __('orders.order_number', ['number' => $order->order_number]) }}
                    </span>
                    <button
                        type="button"
                        x-data
                        @click="navigator.clipboard.writeText('{{ $order->order_number }}').then(() => { $el.innerHTML = '<svg class=\'w-4 h-4 shrink-0 text-green-500\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/></svg>'; setTimeout(() => $el.innerHTML = '<svg class=\'w-4 h-4 shrink-0\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'/></svg>', 1500) })"
                        class="text-gray-400 hover:text-primary-500 transition-colors text-sm"
                        title="{{ __('orders.copy_number') }}"
                    ><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
                </div>
                {{-- Date + customer (staff only) --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                    <span>{{ $order->created_at->format('Y/m/d H:i') }}</span>
                    @if ($isStaff)
                        <span class="text-gray-300">|</span>
                        <span>{{ $order->user->name }}</span>
                        <span class="text-gray-300">|</span>
                        <span>{{ $order->user->email }}</span>
                    @endif
                </div>
            </div>

            {{-- Status badge + payment badge --}}
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                    {{ $order->statusLabel() }}
                </span>
                @if ($order->is_paid)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 ring-1 ring-green-200">
                        ✓ {{ __('orders.paid') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-500 ring-1 ring-gray-200">
                        {{ __('orders.unpaid') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Action needed banner --}}
        @if ($isOwner && in_array($order->status, ['needs_payment', 'on_hold']))
            <div class="mt-3 flex items-center gap-2 px-3 py-2 rounded-xl
                {{ $order->status === 'needs_payment' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-orange-50 border border-orange-200 text-orange-700' }}
                text-sm font-medium">
                @if ($order->status === 'needs_payment')
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    {{ __('orders.needs_payment_note') }}
                @else
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('orders.on_hold_note') }}
                @endif
            </div>
        @endif

        {{-- Merged notice --}}
        @if ($order->merged_into)
            <div class="mt-3 flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-gray-600 text-sm">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                {{ __('orders.merged_into') }}
                <a href="{{ route('orders.show', $order->merged_into) }}" class="font-semibold text-primary-600 hover:underline">
                    {{ __('orders.view_merged_order') }}
                </a>
            </div>
        @endif
    </div>

    {{-- ── Shipping Address ────────────────────────────────────────────────── --}}
    @php
        $editableStatuses    = ['pending', 'needs_payment', 'on_hold'];
        $canChangeAddress    = in_array($order->status, $editableStatuses)
                               && ($isOwner || $isStaff);
        $orderUserAddresses  = $canChangeAddress
                               ? $order->user->addresses()->orderByDesc('is_default')->get()
                               : collect();
        $snap                = $order->shipping_address_snapshot;
    @endphp

    @if ($snap || $canChangeAddress)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="space-y-0.5">
                    <h3 class="text-sm font-semibold text-gray-700">{{ __('orders.shipping_address') }}</h3>
                    @if ($snap)
                        <p class="text-sm text-gray-600">
                            {{ $snap['recipient_name'] ?? '' }}
                            @if (!empty($snap['phone']))
                                · <span class="tabular-nums">{{ $snap['phone'] }}</span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500">
                            {{ implode('، ', array_filter([
                                $snap['address'] ?? null,
                                $snap['city']    ?? null,
                                $snap['country'] ?? null,
                            ])) }}
                        </p>
                        @if (!empty($snap['label']))
                            <span class="inline-block text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500 mt-1">
                                {{ $snap['label'] }}
                            </span>
                        @endif
                    @else
                        <p class="text-sm text-amber-600">{{ __('orders.no_shipping_address') }}</p>
                    @endif
                </div>

                @if ($canChangeAddress && $orderUserAddresses->isNotEmpty())
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open"
                            class="flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:text-primary-700 border border-primary-200 rounded-xl px-3 py-1.5 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ __('orders.change_address') }}
                        </button>

                        <div x-show="open" @click.outside="open = false" x-collapse
                            class="absolute end-0 mt-1 w-72 bg-white border border-gray-100 rounded-2xl shadow-lg z-20 overflow-hidden">
                            <p class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                {{ __('orders.select_address') }}
                            </p>
                            <div class="divide-y divide-gray-50 max-h-60 overflow-y-auto">
                                @foreach ($orderUserAddresses as $addr)
                                    <form action="{{ route('orders.shipping-address.update', $order->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="shipping_address_id" value="{{ $addr->id }}">
                                        <button type="submit"
                                            class="w-full text-start px-3 py-3 text-sm hover:bg-primary-50 transition-colors
                                                {{ $order->shipping_address_id === $addr->id ? 'bg-primary-50' : '' }}">
                                            <span class="font-medium text-gray-800">{{ $addr->label ?: $addr->city }}</span>
                                            @if ($addr->is_default)
                                                <span class="ms-1 text-xs text-primary-600">{{ __('orders.default') }}</span>
                                            @endif
                                            <span class="block text-xs text-gray-400 mt-0.5 truncate">
                                                {{ implode('، ', array_filter([$addr->address, $addr->city, $addr->country])) }}
                                            </span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Status Timeline ────────────────────────────────────────────────── --}}
    @if ($order->timeline->count())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full text-sm font-semibold text-gray-700">
                <span>{{ __('orders.timeline') }}</span>
                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open" x-collapse class="mt-4">
                <ol class="relative border-s border-gray-200 space-y-4 ps-5">
                    @foreach ($order->timeline->sortByDesc('created_at') as $entry)
                        <li class="relative">
                            <span class="absolute -start-2 flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 text-primary-600 ring-4 ring-white">
                                {!! $timelineIcon($entry->type) !!}
                            </span>
                            <div class="text-xs text-gray-400 mb-0.5">
                                {{ $entry->created_at?->format('Y/m/d H:i') }}
                                @if ($entry->user)
                                    — {{ $entry->user->name }}
                                @endif
                            </div>
                            @if ($entry->type === 'status_change')
                                <p class="text-sm text-gray-700">
                                    {{ __('orders.status_changed_from') }}
                                    <span class="font-medium">{{ $entry->status_from ? __(ucfirst(str_replace('_', ' ', $entry->status_from))) : '—' }}</span>
                                    {{ __('orders.to') }}
                                    <span class="font-medium text-primary-600">{{ $entry->status_to ? __(ucfirst(str_replace('_', ' ', $entry->status_to))) : '—' }}</span>
                                </p>
                            @elseif ($entry->body)
                                <p class="text-sm text-gray-700">{{ $entry->body }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    @endif

    {{-- ── Order Items ────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">
                {{ __('orders.items') }}
                <span class="ms-1 text-xs font-normal text-gray-400">({{ $order->items->count() }})</span>
            </h2>
            @if ($isStaff && auth()->user()->can('edit-prices'))
                <button type="button" x-data @click="$dispatch('open-edit-prices')"
                    class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                    {{ __('orders.edit_prices') }}
                </button>
            @endif
        </div>

        {{-- Items table / cards --}}
        <div class="divide-y divide-gray-50">
            @forelse ($order->items as $i => $item)
                <div class="px-4 py-3 space-y-1.5">
                    <div class="flex items-start gap-3">
                        {{-- Thumbnail --}}
                        @if ($item->image_path)
                            @php $itemImgUrl = Storage::disk('public')->url($item->image_path); @endphp
                            <button type="button"
                                @click="$dispatch('open-lightbox', { src: '{{ $itemImgUrl }}', gallery: window.orderLightboxImages })"
                                class="shrink-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400">
                                <img src="{{ $itemImgUrl }}"
                                     alt=""
                                     class="w-12 h-12 rounded-lg object-cover border border-gray-100 cursor-zoom-in hover:opacity-90 transition-opacity">
                            </button>
                        @else
                            <div class="w-12 h-12 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center shrink-0">
                                <span class="text-gray-300 text-lg">{{ $i + 1 }}</span>
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            {{-- URL / description --}}
                            <div class="text-sm text-gray-900 break-all leading-snug">
                                @if ($item->is_url)
                                    <a href="{{ $item->url }}" target="_blank" rel="noopener"
                                        class="text-primary-600 hover:underline">{{ Str::limit($item->url, 80) }}</a>
                                @else
                                    {{ $item->url }}
                                @endif
                            </div>

                            {{-- Attributes row --}}
                            <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-1 text-xs text-gray-500">
                                <span>{{ __('orders.qty') }}: <strong class="text-gray-700">{{ $item->qty }}</strong></span>
                                @if ($item->color)
                                    <span>{{ __('orders.color') }}: <strong class="text-gray-700">{{ $item->color }}</strong></span>
                                @endif
                                @if ($item->size)
                                    <span>{{ __('orders.size') }}: <strong class="text-gray-700">{{ $item->size }}</strong></span>
                                @endif
                                @if ($item->currency && $item->unit_price)
                                    <span>{{ __('orders.price') }}: <strong class="text-gray-700">{{ number_format($item->unit_price, 2) }} {{ $item->currency }}</strong></span>
                                @endif
                                @if ($isStaff && $item->final_price)
                                    <span class="text-primary-600 font-medium">{{ __('orders.final') }}: {{ number_format($item->final_price, 2) }} SAR</span>
                                @endif
                            </div>

                            @if ($item->notes)
                                <p class="mt-1 text-xs text-gray-400 italic">{{ $item->notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">{{ __('orders.no_items') }}</div>
            @endforelse
        </div>

        {{-- Totals --}}
        @if ($order->subtotal || $order->total_amount)
            <div class="border-t border-gray-100 px-4 py-3 space-y-1 text-sm">
                @if ($order->subtotal)
                    <div class="flex items-center justify-between text-gray-500">
                        <span>{{ __('orders.subtotal') }}</span>
                        <span>{{ number_format($order->subtotal, 2) }} {{ $order->currency }}</span>
                    </div>
                @endif
                @if ($order->total_amount)
                    <div class="flex items-center justify-between font-semibold text-gray-800">
                        <span>{{ __('orders.total') }}</span>
                        <span>{{ number_format($order->total_amount, 2) }} {{ $order->currency }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Customer: edit items within window --}}
        @if ($canEditItems)
            <div class="border-t border-amber-100 bg-amber-50 px-4 py-2.5 flex items-center gap-2 text-xs text-amber-700">
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                {{ __('orders.can_edit_until', ['time' => $order->can_edit_until->diffForHumans()]) }}
                <a href="{{ route('new-order') }}?edit={{ $order->id }}" class="ms-auto font-medium text-amber-800 hover:underline">
                    {{ __('orders.edit_items') }} →
                </a>
            </div>
        @endif
    </div>

    {{-- ── Staff: edit prices panel ───────────────────────────────────────── --}}
    @can('edit-prices')
        <div x-data="{ open: false }" @open-edit-prices.window="open = true">
            <div x-show="open" x-collapse>
                <div class="bg-white rounded-2xl border border-primary-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-700">{{ __('orders.edit_prices') }}</h2>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
                    </div>
                    <form action="{{ route('orders.prices.update', $order->id) }}" method="POST" class="px-4 py-4 space-y-3">
                        @csrf @method('POST')
                        @foreach ($order->items as $i => $item)
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                                <div>
                                    <label class="text-gray-500 mb-0.5 block">{{ __('orders.item') }} #{{ $i + 1 }} {{ __('orders.unit_price') }}</label>
                                    <input type="number" step="0.01" min="0"
                                        name="items[{{ $i }}][unit_price]"
                                        value="{{ $item->unit_price }}"
                                        placeholder="0.00"
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                </div>
                                <div>
                                    <label class="text-gray-500 mb-0.5 block">{{ __('orders.commission') }}</label>
                                    <input type="number" step="0.01" min="0"
                                        name="items[{{ $i }}][commission]"
                                        value="{{ $item->commission }}"
                                        placeholder="0.00"
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                </div>
                                <div>
                                    <label class="text-gray-500 mb-0.5 block">{{ __('orders.shipping') }}</label>
                                    <input type="number" step="0.01" min="0"
                                        name="items[{{ $i }}][shipping]"
                                        value="{{ $item->shipping }}"
                                        placeholder="0.00"
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                </div>
                                <div>
                                    <label class="text-gray-500 mb-0.5 block">{{ __('orders.final_price') }}</label>
                                    <input type="number" step="0.01" min="0"
                                        name="items[{{ $i }}][final_price]"
                                        value="{{ $item->final_price }}"
                                        placeholder="0.00"
                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                </div>
                            </div>
                        @endforeach
                        <div class="flex gap-2 pt-1">
                            <button type="submit"
                                class="flex-1 sm:flex-none sm:w-40 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 px-4 rounded-xl transition-colors">
                                {{ __('orders.save_prices') }}
                            </button>
                            <button type="button" @click="open = false"
                                class="flex-1 sm:flex-none sm:w-32 border border-gray-200 text-gray-600 text-sm py-2 px-4 rounded-xl hover:bg-gray-50 transition-colors">
                                {{ __('orders.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{-- ── Staff: quick actions ──────────────────────────────────────────── --}}
    @can('update-order-status')
        @php
            $showMarkPaid    = (bool) \App\Models\Setting::get('qa_mark_paid', true);
            $showMarkShipped = (bool) \App\Models\Setting::get('qa_mark_shipped', true);
            $showRequestInfo = (bool) \App\Models\Setting::get('qa_request_info', true);
            $showCancel      = (bool) \App\Models\Setting::get('qa_cancel_order', true);
            $hasAnyQA        = $showMarkPaid || $showMarkShipped || $showRequestInfo || $showCancel;
        @endphp

        @if ($hasAnyQA)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('orders.quick_actions') }}</h3>
                <div class="flex flex-wrap gap-2">

                    {{-- Mark Paid --}}
                    @if ($showMarkPaid)
                        <form action="{{ route('orders.mark-paid', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                @if ($order->is_paid) disabled title="{{ __('orders.already_paid') }}" @endif
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                    {{ $order->is_paid
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                {{ __('orders.mark_paid') }}
                            </button>
                        </form>
                    @endif

                    {{-- Mark Shipped --}}
                    @if ($showMarkShipped)
                        <form action="{{ route('orders.status.update', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="shipped">
                            <button type="submit"
                                @if ($order->status === 'shipped') disabled @endif
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                    {{ $order->status === 'shipped'
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12h12l1-12"/>
                                </svg>
                                {{ __('orders.mark_shipped') }}
                            </button>
                        </form>
                    @endif

                    {{-- Request Info (→ on_hold) --}}
                    @if ($showRequestInfo)
                        <form action="{{ route('orders.status.update', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="on_hold">
                            <button type="submit"
                                @if ($order->status === 'on_hold') disabled @endif
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                    {{ $order->status === 'on_hold'
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ __('orders.request_info') }}
                            </button>
                        </form>
                    @endif

                    {{-- Cancel Order --}}
                    @if ($showCancel)
                        <form action="{{ route('orders.status.update', $order->id) }}" method="POST"
                            x-data
                            @submit.prevent="if (confirm('{{ addslashes(__('orders.confirm_cancel')) }}')) $el.submit()">
                            @csrf
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit"
                                @if ($order->status === 'cancelled') disabled @endif
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                    {{ $order->status === 'cancelled'
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                {{ __('orders.quick_cancel') }}
                            </button>
                        </form>
                    @endif

                </div>
            </div>
        @endif
    @endcan

    {{-- ── Staff: status change ──────────────────────────────────────────── --}}
    @can('update-order-status')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full text-sm font-semibold text-gray-700">
                <span>{{ __('orders.change_status') }}</span>
                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse class="mt-3">
                <form action="{{ route('orders.status.update', $order->id) }}" method="POST"
                    class="flex items-center gap-2 flex-wrap">
                    @csrf
                    <select name="status"
                        class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                        @foreach (App\Models\Order::getStatuses() as $key => $label)
                            <option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="shrink-0 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 px-4 rounded-xl transition-colors">
                        {{ __('orders.update') }}
                    </button>
                </form>
            </div>
        </div>
    @endcan

    {{-- ── Files ─────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">
                {{ __('orders.files') }}
                @if ($order->files->count())
                    <span class="ms-1 text-xs font-normal text-gray-400">({{ $order->files->count() }})</span>
                @endif
            </h2>
        </div>

        @if ($order->files->count())
            <div class="divide-y divide-gray-50">
                @foreach ($order->files->whereNull('comment_id') as $file)
                    <div class="flex items-center gap-3 px-4 py-3">
                        @if ($file->isImage())
                            <button type="button"
                                @click="$dispatch('open-lightbox', { src: '{{ $file->url() }}', gallery: window.orderLightboxImages })"
                                class="shrink-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400">
                                <img src="{{ $file->url() }}" alt="" class="w-10 h-10 object-cover rounded-lg border border-gray-100 cursor-zoom-in hover:opacity-90 transition-opacity">
                            </button>
                        @else
                            <div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <a href="{{ $file->url() }}" target="_blank" class="text-sm text-gray-800 hover:text-primary-600 truncate block">
                                {{ $file->original_name }}
                            </a>
                            <p class="text-xs text-gray-400">{{ $file->humanSize() }} · {{ $file->created_at->format('Y/m/d') }}</p>
                        </div>
                        <a href="{{ $file->url() }}" target="_blank" download
                            class="shrink-0 text-xs text-gray-400 hover:text-primary-500 border border-gray-200 rounded-lg px-2 py-1 transition-colors">
                            ↓
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="px-4 py-6 text-center text-sm text-gray-400">{{ __('orders.no_files') }}</p>
        @endif

        {{-- Upload file (staff only) --}}
        @can('reply-to-comments')
            <div class="border-t border-gray-100 px-4 py-3" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="text-xs font-medium text-primary-600 hover:text-primary-700">
                    + {{ __('orders.upload_file') }}
                </button>
                <div x-show="open" x-collapse class="mt-2">
                    <form action="{{ route('orders.files.store', $order->id) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 flex-wrap">
                        @csrf
                        <input type="file" name="file" required
                            class="flex-1 text-xs border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-400">
                        <button type="submit"
                            class="shrink-0 bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold py-2 px-3 rounded-xl transition-colors">
                            {{ __('orders.upload') }}
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    </div>

    {{-- ── Comments & conversation ────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" id="comments">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">
                {{ __('orders.comments') }}
                @if ($visibleComments->count())
                    <span class="ms-1 text-xs font-normal text-gray-400">({{ $visibleComments->count() }})</span>
                @endif
            </h2>
        </div>

        {{-- Comment list --}}
        <div class="divide-y divide-gray-50">
            @forelse ($visibleComments as $comment)
                @php
                    $isMine   = $comment->user_id === auth()->id();
                    $isStaffComment = optional($comment->user)->hasAnyRole(['editor', 'admin', 'superadmin']);
                    $customerReads = $comment->reads->filter(fn ($r) => optional($r->user)->hasRole('customer'));
                    $staffReads    = $comment->reads->filter(fn ($r) => optional($r->user)->hasAnyRole(['editor', 'admin', 'superadmin']));
                @endphp

                <div class="px-4 py-4 space-y-2
                    {{ $comment->is_internal ? 'bg-amber-50/50' : '' }}
                    {{ $comment->deleted_at ? 'opacity-60' : '' }}"
                    id="comment-{{ $comment->id }}">

                    {{-- Author row --}}
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- Avatar initials --}}
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0
                                {{ $isStaffComment ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ Str::upper(Str::substr(optional($comment->user)->name ?? '?', 0, 1)) }}
                            </span>
                            <span class="text-sm font-medium text-gray-800">{{ optional($comment->user)->name ?? __('orders.deleted_user') }}</span>
                            @if ($comment->is_internal)
                                <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">
                                    {{ __('orders.internal_note') }}
                                </span>
                            @endif
                            @if ($comment->is_edited)
                                <span class="text-xs text-gray-400 italic">{{ __('orders.edited') }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 tabular-nums">{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                    </div>

                    {{-- Body --}}
                    @if ($comment->deleted_at)
                        <p class="text-sm text-gray-400 italic">{{ __('orders.comment_deleted_placeholder') }}</p>
                    @else
                        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap break-words">{{ $comment->body }}</div>

                        {{-- Attached file --}}
                        @php $commentFile = $order->files->where('comment_id', $comment->id)->first(); @endphp
                        @if ($commentFile)
                            <div class="flex items-center gap-2 mt-1">
                                @if ($commentFile->isImage())
                                    <button type="button"
                                        @click="$dispatch('open-lightbox', { src: '{{ $commentFile->url() }}', gallery: window.orderLightboxImages })"
                                        class="rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400">
                                        <img src="{{ $commentFile->url() }}" alt="" class="h-16 rounded-lg border border-gray-100 object-cover cursor-zoom-in hover:opacity-90 transition-opacity">
                                    </button>
                                @else
                                    <a href="{{ $commentFile->url() }}" target="_blank"
                                        class="flex items-center gap-1.5 text-xs text-primary-600 hover:underline border border-gray-200 rounded-lg px-2 py-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        {{ $commentFile->original_name }} ({{ $commentFile->humanSize() }})
                                    </a>
                                @endif
                            </div>
                        @endif

                        {{-- Read receipts (staff only) --}}
                        @if ($isStaff && auth()->user()->can('view-comment-reads'))
                            <div class="flex flex-wrap gap-1 mt-1">
                                @if ($customerReads->count())
                                    <span class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded bg-green-50 text-green-700">
                                        ✓✓ {{ __('orders.read_by_customer') }}
                                    </span>
                                @endif
                                @if ($staffReads->count())
                                    <span x-data="{ open: false }" class="relative inline-block">
                                        <button @click="open = !open"
                                            class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 hover:bg-gray-200">
                                            {{ $staffReads->count() }} {{ __('orders.team_members') }}
                                        </button>
                                        <div x-show="open" @click.outside="open = false"
                                            class="absolute z-10 mt-1 p-2 bg-white border border-gray-200 rounded-xl shadow-lg text-xs min-w-40 space-y-1">
                                            @foreach ($staffReads as $read)
                                                <div class="text-gray-600">
                                                    <span class="font-medium">{{ optional($read->user)->name }}</span>
                                                    <span class="text-gray-400 ms-1">{{ $read->read_at->format('H:i') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- Actions row --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                            {{-- Edit --}}
                            @if ($comment->canBeEditedBy(auth()->user()))
                                <button type="button"
                                    x-data
                                    @click="$dispatch('edit-comment', { id: {{ $comment->id }}, body: {{ json_encode($comment->body) }} })"
                                    class="text-xs text-gray-400 hover:text-primary-500 transition-colors">
                                    {{ __('orders.edit') }}
                                </button>
                            @endif

                            {{-- Edit history (staff) --}}
                            @if ($isStaff && $comment->edits->count())
                                <button type="button"
                                    x-data
                                    @click="$dispatch('view-history', { id: {{ $comment->id }}, edits: {{ $comment->edits->map(fn ($e) => ['old_body' => $e->old_body, 'editor' => optional($e->editor)->name, 'at' => optional($e->created_at)?->format('Y/m/d H:i')]) }} })"
                                    class="text-xs text-gray-400 hover:text-indigo-500 transition-colors">
                                    {{ __('orders.edit_history') }} ({{ $comment->edits->count() }})
                                </button>
                            @endif

                            {{-- Send notification (staff, non-internal) --}}
                            @if ($isStaff && ! $comment->is_internal)
                                @can('send-comment-notification')
                                    <form action="{{ route('orders.comments.notify', [$order->id, $comment->id]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs text-gray-400 hover:text-blue-500 transition-colors">
                                            {{ __('orders.send_notification') }}
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            {{-- Delete --}}
                            @if ($comment->canBeDeletedBy(auth()->user()))
                                <form action="{{ route('orders.comments.destroy', [$order->id, $comment->id]) }}" method="POST"
                                    x-data
                                    @submit.prevent="if (confirm('{{ __('orders.confirm_delete_comment') }}')) $el.submit()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                                        {{ __('orders.delete') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-4 py-10 text-center text-sm text-gray-400">
                    {{ __('orders.no_comments') }}
                </div>
            @endforelse
        </div>

        {{-- ── Add comment ──────────────────────────────────────────────── --}}
        <div class="border-t border-gray-100 px-4 py-4">
            <form action="{{ route('orders.comments.store', $order->id) }}" method="POST"
                enctype="multipart/form-data" class="space-y-3"
                x-data="{ body: '{{ old('body') }}' }">
                @csrf

                {{-- Quick-reply templates (staff only) --}}
                @if ($isStaff)
                    @php $commentTemplates = \App\Models\CommentTemplate::active()->limit(20)->get(); @endphp
                    @if ($commentTemplates->isNotEmpty())
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open"
                                class="flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-700 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>
                                {{ __('orders.quick_reply') }}
                                <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-collapse
                                class="mt-1 border border-gray-100 rounded-xl bg-white shadow-sm divide-y divide-gray-50 max-h-52 overflow-y-auto">
                                @foreach ($commentTemplates as $tpl)
                                    <button type="button"
                                        @click="body = {{ Js::from($tpl->content) }}; $root.querySelector('[name=template_id]').value = {{ $tpl->id }}; open = false"
                                        class="w-full text-start px-3 py-2 text-xs text-gray-700 hover:bg-primary-50 transition-colors">
                                        <span class="font-medium text-gray-800">{{ $tpl->title }}</span>
                                        <span class="block text-gray-400 truncate mt-0.5">{{ Str::limit($tpl->content, 60) }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                <input type="hidden" name="template_id" value="">
                <textarea
                    name="body"
                    rows="3"
                    x-model="body"
                    placeholder="{{ __('orders.write_comment') }}"
                    required
                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-400 resize-none leading-relaxed"></textarea>

                <div class="flex items-center gap-3 flex-wrap" x-data="{ fileName: '' }">
                    {{-- File attach --}}
                    <label class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span x-text="fileName || '{{ __('orders.attach_file') }}'"></span>
                        <input type="file" name="file" class="sr-only" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                            @change="fileName = $event.target.files[0]?.name ?? ''">
                    </label>
                    <template x-if="fileName">
                        <button type="button" @click="fileName = ''; $root.querySelector('input[type=file]').value = ''"
                            class="text-xs text-red-400 hover:text-red-600">✕</button>
                    </template>

                    {{-- Internal note toggle (staff only) --}}
                    @if ($isStaff)
                        @can('add-internal-note')
                            <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer select-none">
                                <input type="checkbox" name="is_internal" value="1"
                                    class="rounded border-gray-300 text-primary-500 focus:ring-primary-400">
                                {{ __('orders.internal_note') }}
                            </label>
                        @endcan
                    @endif

                    {{-- Submit --}}
                    <button type="submit"
                        class="ms-auto bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 px-5 rounded-xl transition-colors">
                        {{ __('orders.send') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Staff: generate invoice ───────────────────────────────────────── --}}
    @can('generate-pdf-invoice')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="flex items-center justify-between w-full text-sm font-semibold text-gray-700">
                <span>{{ __('orders.generate_invoice') }}</span>
                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse class="mt-4">
                <form action="{{ route('orders.invoice.generate', $order->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('orders.invoice_type') }}</label>
                            <select name="invoice_type"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                <option value="detailed">{{ __('orders.invoice_detailed') }}</option>
                                <option value="simple">{{ __('orders.invoice_simple') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('orders.invoice_custom_amount') }}</label>
                            <input type="number" step="0.01" min="0" name="custom_amount" placeholder="0.00"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders.invoice_notes') }}</label>
                        <textarea name="custom_notes" rows="2"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400 resize-none"></textarea>
                    </div>
                    <button type="submit"
                        class="w-full sm:w-auto bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 px-5 rounded-xl transition-colors">
                        {{ __('orders.generate_and_post') }}
                    </button>
                </form>
            </div>
        </div>
    @endcan

    {{-- ── Staff: send email ────────────────────────────────────────────── --}}
    @if ($isStaff)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4"
             x-data="{ sending: false, done: false, error: '' }">
            <button type="button" @click="sending = true; error = ''; done = false;
                fetch('{{ route('orders.send-email', $order->id) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                }).then(r => r.json()).then(d => {
                    sending = false;
                    if (d.success) { done = true; } else { error = d.message; }
                }).catch(() => { sending = false; error = '{{ __('Network error. Please try again.') }}'; })"
                class="flex items-center justify-between w-full text-sm font-semibold text-gray-700"
                :disabled="sending">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ __('orders.send_confirmation_email') }}
                </span>
                <span x-show="sending" class="text-xs text-gray-400 animate-pulse">{{ __('Sending…') }}</span>
            </button>
            <p x-show="done" x-cloak class="text-xs text-green-600 mt-2">{{ __('orders.email_queued') }}</p>
            <p x-show="error" x-cloak class="text-xs text-red-600 mt-2" x-text="error"></p>
        </div>
    @endif

    {{-- ── Staff: merge orders ───────────────────────────────────────────── --}}
    @can('merge-orders')
        @if ($recentOrders->count())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="flex items-center justify-between w-full text-sm font-semibold text-gray-700">
                    <span>{{ __('orders.merge_orders') }}</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="mt-4">
                    <p class="text-xs text-gray-500 mb-3">{{ __('orders.merge_description') }}</p>
                    <form action="{{ route('orders.merge', $order->id) }}" method="POST"
                        x-data
                        @submit.prevent="if (confirm('{{ __('orders.confirm_merge') }}')) $el.submit()">
                        @csrf
                        <div class="space-y-2 max-h-52 overflow-y-auto mb-3 border border-gray-100 rounded-xl divide-y divide-gray-50">
                            @foreach ($recentOrders as $ro)
                                <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors">
                                    <input type="radio" name="merge_with" value="{{ $ro->id }}" required
                                        class="text-primary-500 focus:ring-primary-400">
                                    <div>
                                        <span class="text-sm font-medium text-gray-800">#{{ $ro->order_number }}</span>
                                        <span class="ms-2 text-xs text-gray-400">{{ $ro->created_at->format('Y/m/d') }}</span>
                                        <span class="ms-2 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ $ro->statusLabel() }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit"
                            class="w-full sm:w-auto bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold py-2 px-5 rounded-xl transition-colors">
                            {{ __('orders.execute_merge') }}
                        </button>
                    </form>
                </div>
            </div>
        @endif
    @endcan

</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     Modals (Alpine.js)
═══════════════════════════════════════════════════════════════════════════ --}}

{{-- Edit comment modal --}}
<div
    x-data="{
        show: false,
        commentId: null,
        body: '',
        init() {
            window.addEventListener('edit-comment', e => {
                this.commentId = e.detail.id;
                this.body = e.detail.body;
                this.show = true;
            })
        }
    }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40">
    <div @click.outside="show = false"
        class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">{{ __('orders.edit_comment') }}</h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form :action="`{{ url('/orders/' . $order->id . '/comments') }}/${commentId}`" method="POST">
            @csrf
            <input type="hidden" name="_method" value="PATCH">
            <textarea name="body" rows="5" x-model="body" required
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-400 resize-none leading-relaxed"></textarea>
            <div class="flex gap-2 mt-3">
                <button type="submit"
                    class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 rounded-xl transition-colors">
                    {{ __('orders.save') }}
                </button>
                <button type="button" @click="show = false"
                    class="flex-1 border border-gray-200 text-gray-600 text-sm py-2 rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('orders.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Image Lightbox ──────────────────────────────────────────────────────── --}}
<div
    x-data="{
        show: false,
        src: '',
        gallery: [],
        currentIndex: 0,
        open(src, gallery) {
            this.gallery = (gallery && gallery.length) ? gallery : [src];
            this.currentIndex = this.gallery.indexOf(src);
            if (this.currentIndex === -1) this.currentIndex = 0;
            this.src = this.gallery[this.currentIndex];
            this.show = true;
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.show = false;
            document.body.style.overflow = '';
        },
        prev() {
            if (this.gallery.length > 1) {
                this.currentIndex = (this.currentIndex - 1 + this.gallery.length) % this.gallery.length;
                this.src = this.gallery[this.currentIndex];
            }
        },
        next() {
            if (this.gallery.length > 1) {
                this.currentIndex = (this.currentIndex + 1) % this.gallery.length;
                this.src = this.gallery[this.currentIndex];
            }
        },
        init() {
            window.addEventListener('open-lightbox', e => {
                this.open(e.detail.src, e.detail.gallery || []);
            });
            document.addEventListener('keydown', e => {
                if (!this.show) return;
                if (e.key === 'Escape') this.close();
                if (e.key === 'ArrowLeft') this.prev();
                if (e.key === 'ArrowRight') this.next();
            });
        }
    }"
    x-show="show"
    x-cloak
    @click.self="close()"
    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/85 backdrop-blur-sm p-4"
    style="display: none;">

    {{-- Close --}}
    <button @click.stop="close()"
        class="absolute top-4 end-4 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors z-10"
        title="{{ __('nav.close_menu') }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    {{-- Prev --}}
    <template x-if="gallery.length > 1">
        <button @click.stop="prev()"
            class="absolute start-4 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors z-10">
            <svg class="w-5 h-5 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </template>

    {{-- Next --}}
    <template x-if="gallery.length > 1">
        <button @click.stop="next()"
            class="absolute end-4 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors z-10">
            <svg class="w-5 h-5 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </template>

    {{-- Image --}}
    <img :src="src" alt=""
        class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl select-none"
        @click.stop
        draggable="false">

    {{-- Gallery counter --}}
    <template x-if="gallery.length > 1">
        <div class="absolute bottom-5 left-1/2 -translate-x-1/2 text-white/80 text-xs font-medium bg-black/40 px-3 py-1 rounded-full tabular-nums select-none">
            <span x-text="currentIndex + 1"></span>&thinsp;/&thinsp;<span x-text="gallery.length"></span>
        </div>
    </template>
</div>

{{-- Edit history modal --}}
@if ($isStaff)
<div
    x-data="{
        show: false,
        edits: [],
        init() {
            window.addEventListener('view-history', e => {
                this.edits = e.detail.edits;
                this.show = true;
            })
        }
    }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40">
    <div @click.outside="show = false"
        class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-5 space-y-4 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">{{ __('orders.edit_history') }}</h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="space-y-3">
            <template x-for="(edit, i) in edits" :key="i">
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 space-y-1">
                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span x-text="edit.editor"></span>
                        <span x-text="edit.at"></span>
                    </div>
                    <p class="text-sm text-gray-600 whitespace-pre-wrap" x-text="edit.old_body"></p>
                </div>
            </template>
        </div>
    </div>
</div>
@endif

</x-app-layout>
