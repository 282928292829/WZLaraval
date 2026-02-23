@php
    // Build the gallery array for the lightbox (all image URLs on this page)
    $lightboxImages = [];
    // Order-level product_image files (for items that have no image_path — e.g. attach on submit)
    $productImageFiles = $order->files->whereNull('comment_id')->where('type', 'product_image')->filter(fn ($f) => str_starts_with($f->mime_type ?? '', 'image/'))->values();
    $productImageIndex = 0;
    foreach ($order->items as $item) {
        if ($item->image_path) {
            $lightboxImages[] = Storage::disk('public')->url($item->image_path);
        } elseif ($productImageFiles->has($productImageIndex)) {
            $lightboxImages[] = $productImageFiles[$productImageIndex]->url();
            $productImageIndex++;
        }
    }
    foreach ($order->files->whereNull('comment_id') as $file) {
        if ($file->isImage()) {
            $lightboxImages[] = $file->url();
        }
    }
    $allVisibleComments = $order->comments->filter(fn ($c) => $c->isVisibleTo(auth()->user()));
    foreach ($allVisibleComments as $c) {
        foreach ($order->files->where('comment_id', $c->id) as $cf) {
            if ($cf->isImage()) {
                $lightboxImages[] = $cf->url();
            }
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
    @if (session('error'))
        <div class="flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V5z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Comments discovery banner: first 2 visits only (same as WP: top of order, link to comments at bottom) --}}
    @if($showCommentsDiscovery ?? true)
    <div id="comments-discovery-banner"
         class="rounded-xl mb-4 flex items-center justify-between gap-3 flex-wrap"
         style="background: linear-gradient(135deg, #f97316 0%, #fb923c 100%); color: #fff; padding: 12px 16px; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.2);"
         x-data="{ hidden: sessionStorage.getItem('commentsDiscoveryBannerDismissed') === '1' }"
         x-show="!hidden"
         x-transition>
        <div class="flex flex-1 items-center gap-2 min-w-0">
            <span class="text-lg shrink-0">💬</span>
            <div class="min-w-0">
                <div class="font-semibold mb-0.5">{{ __('orders.comments_discovery_title') }}</div>
                <div class="text-sm opacity-95">
                    {{ __('orders.comments_discovery_description') }}
                    <a href="#comments" class="text-white underline font-semibold cursor-pointer hover:opacity-90">{{ __('orders.comments_discovery_link') }}</a>
                </div>
            </div>
        </div>
        <button type="button"
                class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-lg transition-colors hover:bg-white/20"
                title="{{ __('common.dismiss') }}"
                @click="sessionStorage.setItem('commentsDiscoveryBannerDismissed', '1'); hidden = true">
            ✕
        </button>
    </div>
    @endif

    {{-- ── Order header card ─────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3">
        {{-- 1 line on desktop; 2 lines on mobile (identity row + status/payment row) --}}
        <div class="overflow-x-auto">
            <div class="flex flex-col gap-1.5 md:flex-row md:flex-nowrap md:items-center md:gap-x-3 md:min-w-max">
                {{-- Row 1 (mobile) / inline (desktop): number, date, time, (+ staff: name, email) --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5">
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-lg font-bold text-gray-900 whitespace-nowrap">
                            {{ __('orders.order_number', ['number' => $order->order_number]) }}
                        </span>
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            @click="(async () => {
                                try {
                                    await navigator.clipboard.writeText('{{ $order->order_number }}');
                                } catch(e) {
                                    const ta = document.createElement('textarea');
                                    ta.value = '{{ $order->order_number }}';
                                    ta.style.position = 'fixed';
                                    ta.style.opacity = '0';
                                    document.body.appendChild(ta);
                                    ta.focus(); ta.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(ta);
                                }
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            })()"
                            class="inline-flex items-center gap-1 transition-colors text-xs"
                            :class="copied ? 'text-green-600 font-medium' : 'text-gray-400 hover:text-primary-500'"
                            title="{{ __('orders.copy_number') }}"
                        >
                            <template x-if="!copied">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </template>
                            <template x-if="copied">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ __('orders.number_copied') }}
                                </span>
                            </template>
                        </button>
                    </div>
                    <span class="hidden md:inline text-gray-300 select-none shrink-0">|</span>
                    <span class="text-xs text-gray-400 whitespace-nowrap shrink-0"><strong>{{ __('orders.order_date') }}</strong> {{ $order->created_at->format('Y/m/d') }}</span>
                    @if ($isStaff)
                        <span class="hidden md:inline text-gray-300 select-none shrink-0">|</span>
                        <span class="text-xs text-gray-400 whitespace-nowrap shrink-0"><strong>{{ __('orders.order_time') }}</strong> {{ $order->created_at->format('H:i') }}</span>
                        <span class="hidden md:inline text-gray-300 select-none shrink-0">|</span>
                        <span class="text-xs text-gray-400 whitespace-nowrap shrink-0">{{ $order->user->name }}</span>
                        <span class="hidden md:inline text-gray-300 select-none shrink-0">|</span>
                        <span class="text-xs text-gray-400 truncate min-w-0 max-w-[160px] md:max-w-[200px]">{{ $order->user->email }}</span>
                    @endif
                </div>
                <span class="hidden md:inline text-gray-300 select-none shrink-0">|</span>
                {{-- Row 2 (mobile) / same line (desktop): status + payment --}}
                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ring-1 ring-inset shrink-0 {{ $statusClasses }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                        {{ $order->statusLabel() }}
                    </span>
                    @if ($order->is_paid)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 ring-1 ring-green-200 shrink-0">
                            ✓ {{ __('orders.paid') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-500 ring-1 ring-gray-200 shrink-0">
                            {{ __('orders.unpaid') }}
                        </span>
                    @endif
                </div>
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

        {{-- ── Shipping Address (inside header card) ──────────────────────── --}}
        @php
            $addressVisibleStatuses = ['processing', 'purchasing', 'shipped', 'delivered', 'completed', 'cancelled', 'on_hold'];
            $showAddressToCustomer  = $isOwner && in_array($order->status, $addressVisibleStatuses);
            $editableStatuses       = ['pending', 'needs_payment', 'on_hold'];
            $canChangeAddress       = in_array($order->status, $editableStatuses) && ($isOwner || $isStaff);
            $orderUserAddresses     = $canChangeAddress
                                      ? $order->user->addresses()->orderByDesc('is_default')->get()
                                      : collect();
            $snap                   = $order->shipping_address_snapshot;
            $showAddressBlock       = $isStaff || $showAddressToCustomer;
        @endphp

        @if ($showAddressBlock && ($snap || $canChangeAddress || $isStaff))
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="space-y-0.5 flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-500">{{ __('orders.shipping_address') }}</p>
                        @if ($snap)
                            <p class="text-sm text-gray-700 font-medium">
                                {{ $snap['recipient_name'] ?? '' }}
                                @if (!empty($snap['phone']))
                                    · <span class="tabular-nums font-normal text-gray-500">{{ $snap['phone'] }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ implode('، ', array_filter([
                                    $snap['address'] ?? null,
                                    $snap['city']    ?? null,
                                    $snap['country'] ?? null,
                                ])) }}
                            </p>
                            @if (!empty($snap['label']))
                                <span class="inline-block text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500 mt-0.5">
                                    {{ $snap['label'] }}
                                </span>
                            @endif
                        @elseif ($isOwner)
                            {{-- Customer has no address: show prompt + open modal --}}
                            <div x-data="{ open: false }" class="mt-1">
                                <button type="button" @click="open = true" data-open-address
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-xs font-medium hover:bg-amber-100 transition-colors">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ __('orders.no_address_prompt') }}
                                </button>
                                <p class="mt-1 text-xs text-gray-400">{{ __('orders.no_address_hint') }}</p>

                                {{-- Add address modal --}}
                                <div x-show="open" x-cloak
                                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40"
                                    @keydown.escape.window="open = false">
                                    <div @click.outside="open = false"
                                        class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">

                                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                                            <h3 class="text-sm font-semibold text-gray-900">{{ __('orders.add_address_modal_title') }}</h3>
                                            <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="overflow-y-auto flex-1">
                                            @php $waNum = preg_replace('/\D/', '', \App\Models\Setting::get('whatsapp', '966500000000')); @endphp
                                            <form method="POST" action="{{ route('account.addresses.store') }}"
                                                class="px-5 py-5 space-y-3">
                                                @csrf
                                                <input type="hidden" name="_form" value="add_address">
                                                <input type="hidden" name="_order_id" value="{{ $order->id }}">
                                                <input type="hidden" name="_redirect_back" value="{{ route('orders.show', $order->id) }}">

                                                {{-- Label + Recipient --}}
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.address_label') }}</label>
                                                        <input type="text" name="label" value="{{ old('label') }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                                            placeholder="{{ __('account.label_placeholder') }}">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.recipient_name') }} <span class="text-red-400">*</span></label>
                                                        <input type="text" name="recipient_name" required value="{{ old('recipient_name', auth()->user()->name) }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                    </div>
                                                </div>

                                                {{-- Country + City --}}
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            {{ __('account.country') }}
                                                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                                        </label>
                                                        <input type="text" name="country" value="{{ old('country', 'السعودية') }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            {{ __('account.city') }} <span class="text-red-400">*</span>
                                                        </label>
                                                        <input type="text" name="city" required value="{{ old('city') }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                    </div>
                                                </div>

                                                {{-- Street + District --}}
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            {{ __('account.street') }}
                                                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                                        </label>
                                                        <input type="text" name="street" value="{{ old('street') }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            {{ __('account.district') }}
                                                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                                        </label>
                                                        <input type="text" name="district" value="{{ old('district') }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                    </div>
                                                </div>

                                                {{-- Short address + Address details --}}
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div x-data="{ open: false }">
                                                        <label class="flex items-center gap-1 text-xs font-medium text-gray-600 mb-1">
                                                            {{ __('account.short_address') }}
                                                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                                            <button type="button" @click="open = !open" class="text-blue-400 hover:text-blue-600 transition-colors">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                            </button>
                                                        </label>
                                                        <input type="text" name="short_address" value="{{ old('short_address') }}"
                                                            maxlength="20"
                                                            placeholder="{{ __('account.short_address_placeholder') }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                        <div x-show="open" x-collapse class="mt-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5 space-y-1.5">
                                                            <p class="text-xs text-blue-700 leading-relaxed">
                                                                <span class="font-medium">١.</span>
                                                                {{ __('account.national_address_tip_whatsapp') }}
                                                                &nbsp;<a href="https://wa.me/966112898888" target="_blank" rel="noopener"
                                                                    class="underline underline-offset-2 font-semibold hover:text-blue-900 transition" dir="ltr">0112898888</a>
                                                                ثم شارك موقعك الجغرافي وسيُرسَل إليك الرمز.
                                                            </p>
                                                            <p class="text-xs text-blue-700 leading-relaxed">
                                                                <span class="font-medium">٢.</span>
                                                                {{ __('account.national_address_tip_apps') }}
                                                            </p>
                                                            <p class="text-xs text-blue-700 leading-relaxed">
                                                                <span class="font-medium">٣.</span>
                                                                <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener"
                                                                    class="underline underline-offset-2 font-semibold hover:text-blue-900 transition">{{ __('account.national_address_tip_us') }}</a>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            {{ __('account.address') }}
                                                            <span class="text-gray-400 font-normal">({{ __('order.optional') }})</span>
                                                        </label>
                                                        <input type="text" name="address" value="{{ old('address') }}"
                                                            placeholder="{{ __('account.address_placeholder') }}"
                                                            class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                    </div>
                                                </div>

                                                {{-- Phone --}}
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('account.mobile') }} <span class="text-red-400">*</span></label>
                                                    <input type="tel" name="phone" required
                                                        value="{{ old('phone', auth()->user()->phone) }}"
                                                        placeholder="{{ __('account.mobile_placeholder') }}"
                                                        inputmode="numeric" pattern="[0-9]*"
                                                        class="block w-full px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                                </div>

                                                <div class="flex items-center gap-3 pt-1">
                                                    <button type="submit"
                                                        class="flex-1 sm:flex-none px-5 py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors">
                                                        {{ __('account.save_address') }}
                                                    </button>
                                                    <button type="button" @click="open = false"
                                                        class="flex-1 sm:flex-none px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                                                        {{ __('orders.cancel') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Staff view: no address set --}}
                            <p class="text-xs text-amber-600">{{ __('orders.no_shipping_address') }}</p>
                        @endif
                    </div>

                    @if ($canChangeAddress && $orderUserAddresses->isNotEmpty())
                        <div x-data="{ open: false }" class="relative shrink-0">
                            <button type="button" @click="open = !open" data-open-address
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
    </div>

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
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
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
                                </div>
                                @if ($isStaff)
                                    <form action="{{ route('orders.timeline.add-as-comment', [$order->id, $entry->id]) }}" method="POST" class="shrink-0">
                                        @csrf
                                        <button type="submit" class="text-xs text-gray-400 hover:text-primary-600 transition-colors whitespace-nowrap">
                                            {{ __('orders.add_as_comment') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    @endif

    {{-- ── Tracking Card (customer-visible) ──────────────────────────────── --}}
    @if ($order->tracking_number)
        @php
            $carrierUrlTemplates = [
                'aramex' => \App\Models\Setting::get('carrier_url_aramex', 'https://www.aramex.com/track/results?mode=0&ShipmentNumber={tracking}'),
                'smsa'   => \App\Models\Setting::get('carrier_url_smsa',   'https://www.smsaexpress.com/track/?tracknumbers={tracking}'),
                'dhl'    => \App\Models\Setting::get('carrier_url_dhl',    'https://www.dhl.com/sa-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}'),
                'fedex'  => \App\Models\Setting::get('carrier_url_fedex',  'https://www.fedextrack/?trknbr={tracking}'),
                'ups'    => \App\Models\Setting::get('carrier_url_ups',    'https://www.ups.com/track?tracknum={tracking}'),
            ];
            $carrierLabels = [
                'aramex' => 'Aramex',
                'smsa'   => 'SMSA',
                'dhl'    => 'DHL',
                'fedex'  => 'FedEx',
                'ups'    => 'UPS',
                'other'  => __('orders.carrier_other'),
            ];
            $trackingUrl = null;
            if ($order->tracking_company && isset($carrierUrlTemplates[$order->tracking_company])) {
                $tpl = $carrierUrlTemplates[$order->tracking_company];
                if (!empty($tpl)) {
                    $trackingUrl = str_replace('{tracking}', urlencode($order->tracking_number), $tpl);
                }
            }
            $carrierLabel = $carrierLabels[$order->tracking_company ?? ''] ?? $order->tracking_company;
        @endphp

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4"
             x-data="{ copied: false }">
            <p class="text-xs font-semibold text-gray-500 mb-2">{{ __('orders.tracking_card_title') }}</p>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Carrier badge --}}
                @if ($carrierLabel)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-medium">
                        🚚 {{ $carrierLabel }}
                    </span>
                @endif

                {{-- Tracking number --}}
                <span class="font-mono font-semibold text-gray-900 text-sm tracking-wide">
                    {{ $order->tracking_number }}
                </span>

                {{-- Copy button --}}
                <button type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $order->tracking_number }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 text-xs font-medium text-gray-600 transition-colors">
                    <template x-if="!copied">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            {{ __('orders.copy') }}
                        </span>
                    </template>
                    <template x-if="copied">
                        <span class="inline-flex items-center gap-1.5 text-green-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('orders.copied') }}
                        </span>
                    </template>
                </button>

                {{-- Track link --}}
                @if ($trackingUrl)
                    <a href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        {{ __('orders.track_shipment') }}
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Staff Notes (staff only) — always show form + Save so button is visible --}}
    @if ($isStaff)
    <div class="bg-amber-50 border border-amber-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-amber-100">
            <h2 class="text-sm font-semibold text-amber-800 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                {{ __('orders.staff_notes_title') }}
            </h2>
        </div>
        <form action="{{ route('orders.staff-notes.update', $order->id) }}" method="POST" class="px-4 py-3 space-y-3">
            @csrf @method('PATCH')
            <textarea name="staff_notes" rows="4"
                class="w-full text-sm px-3 py-2.5 rounded-xl border border-amber-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition resize-none"
                placeholder="{{ __('orders.staff_notes_placeholder') }}">{{ old('staff_notes', $order->staff_notes) }}</textarea>
            <div class="flex items-center gap-2 pt-1">
                <button type="submit"
                    class="shrink-0 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2.5 px-5 rounded-xl transition-colors">
                    {{ __('orders.save') }}
                </button>
            </div>
        </form>
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

        {{-- Items table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-fixed">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100 bg-gray-50/50">
                        <th class="px-4 py-2 font-medium text-start w-8">#</th>
                        <th class="px-4 py-2 font-medium text-start w-[40%] min-w-0">{{ __('orders.product') }}</th>
                        <th class="px-4 py-2 font-medium text-center w-14">{{ __('orders.qty') }}</th>
                        <th class="px-4 py-2 font-medium text-start w-24 hidden sm:table-cell">{{ __('orders.color') }}</th>
                        <th class="px-4 py-2 font-medium text-start w-24 hidden sm:table-cell">{{ __('orders.size') }}</th>
                        <th class="px-4 py-2 font-medium text-start w-28 hidden sm:table-cell">{{ __('orders.price') }}</th>
                        @if ($isStaff)
                            <th class="px-4 py-2 font-medium text-start w-28 hidden sm:table-cell">{{ __('orders.final') }}</th>
                        @endif
                        <th class="px-4 py-2 font-medium text-start hidden md:table-cell">{{ __('orders.notes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $productImageFilesForItems = $order->files->whereNull('comment_id')->where('type', 'product_image')->filter(fn ($f) => str_starts_with($f->mime_type ?? '', 'image/'))->values();
                        $productImageIdx = 0;
                        $itemDisplayImageUrls = [];
                        foreach ($order->items as $it) {
                            if ($it->image_path) {
                                $itemDisplayImageUrls[] = Storage::disk('public')->url($it->image_path);
                            } elseif ($productImageFilesForItems->has($productImageIdx)) {
                                $itemDisplayImageUrls[] = $productImageFilesForItems[$productImageIdx]->url();
                                $productImageIdx++;
                            } else {
                                $itemDisplayImageUrls[] = null;
                            }
                        }
                    @endphp
                    @forelse ($order->items as $i => $item)
                        @php $itemImgUrl = $itemDisplayImageUrls[$i] ?? ($item->image_path ? Storage::disk('public')->url($item->image_path) : null); @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">

                            {{-- # + thumbnail --}}
                            <td class="px-4 py-3 align-middle">
                                @if ($itemImgUrl)
                                    <button type="button"
                                        @click="$dispatch('open-lightbox', { src: '{{ $itemImgUrl }}', gallery: window.orderLightboxImages })"
                                        class="block rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400">
                                        <img src="{{ $itemImgUrl }}" alt=""
                                            class="w-9 h-9 rounded-lg object-cover border border-gray-100 cursor-zoom-in hover:opacity-90 transition-opacity">
                                    </button>
                                @else
                                    <span class="text-xs text-gray-300 font-medium">{{ $i + 1 }}</span>
                                @endif
                            </td>

                            {{-- URL / description: show domain only + Open + Copy --}}
                            <td class="px-4 py-3 align-middle min-w-0">
                                @if ($item->is_url)
                                    @php
                                        $itemHost = parse_url($item->url, PHP_URL_HOST) ?: $item->url;
                                        $itemHost = preg_replace('/^www\./i', '', $itemHost);
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                        <span class="text-gray-800 font-medium truncate shrink-0 max-w-full" title="{{ $item->url }}">{{ $itemHost }}</span>
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener"
                                            class="shrink-0 inline-flex items-center gap-0.5 text-xs font-semibold py-1 px-2 rounded-md border border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors">
                                            {{ __('orders.view') }}
                                        </a>
                                        <button type="button"
                                            x-data="{ copied: false }"
                                            data-copy-url="{{ e($item->url) }}"
                                            @click="(async () => {
                                                const url = $el.getAttribute('data-copy-url');
                                                try { await navigator.clipboard.writeText(url); } catch(e) {
                                                    const ta = document.createElement('textarea'); ta.value = url;
                                                    ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta);
                                                    ta.focus(); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                                                }
                                                copied = true; setTimeout(() => copied = false, 2000);
                                            })()"
                                            class="shrink-0 inline-flex items-center gap-0.5 text-xs font-semibold py-1 px-2 rounded-md border border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors"
                                            :class="copied && '!border-green-200 !bg-green-50 !text-green-700'"
                                            title="{{ __('orders.copy') }}">
                                            <span x-show="!copied">{{ __('orders.copy') }}</span>
                                            <span x-show="copied" x-cloak>{{ __('orders.copied') }}</span>
                                        </button>
                                    </div>
                                @else
                                    <div class="truncate min-w-0">
                                        <span class="text-gray-800">{{ $item->url }}</span>
                                    </div>
                                @endif
                                {{-- Mobile-only: show hidden columns inline --}}
                                <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5 text-xs text-gray-400 sm:hidden">
                                    @if ($item->color)
                                        <span>{{ $item->color }}</span>
                                    @endif
                                    @if ($item->size)
                                        <span>{{ $item->size }}</span>
                                    @endif
                                    @if ($item->currency && $item->unit_price)
                                        <span>{{ number_format($item->unit_price, 2) }} {{ $item->currency }}</span>
                                    @endif
                                    @if ($isStaff && $item->final_price)
                                        <span class="text-primary-600">{{ number_format($item->final_price, 2) }} SAR</span>
                                    @endif
                                    @if ($item->notes)
                                        <span class="italic text-gray-300 md:hidden">{{ Str::limit($item->notes, 40) }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Qty --}}
                            <td class="px-4 py-3 align-middle text-center">
                                <span class="font-semibold text-gray-800">{{ $item->qty }}</span>
                            </td>

                            {{-- Color --}}
                            <td class="px-4 py-3 align-middle text-gray-600 hidden sm:table-cell">
                                {{ $item->color ?: '—' }}
                            </td>

                            {{-- Size --}}
                            <td class="px-4 py-3 align-middle text-gray-600 hidden sm:table-cell">
                                {{ $item->size ?: '—' }}
                            </td>

                            {{-- Unit price --}}
                            <td class="px-4 py-3 align-middle text-gray-600 hidden sm:table-cell" dir="ltr">
                                @if ($item->currency && $item->unit_price)
                                    {{ number_format($item->unit_price, 2) }} {{ $item->currency }}
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Final price (staff) --}}
                            @if ($isStaff)
                                <td class="px-4 py-3 align-middle hidden sm:table-cell" dir="ltr">
                                    @if ($item->final_price)
                                        <span class="font-medium text-primary-600">{{ number_format($item->final_price, 2) }} SAR</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endif

                            {{-- Notes --}}
                            <td class="px-4 py-3 align-middle text-xs text-gray-400 italic hidden md:table-cell max-w-[160px]">
                                <div class="truncate" title="{{ $item->notes }}">
                                    {{ $item->notes ?: '—' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400">{{ __('orders.no_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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

    {{-- ══════════════════════════════════════════════════════════════════════
         Staff Quick Actions — إجراءات سريعة للفريق
    ══════════════════════════════════════════════════════════════════════ --}}
    @if ($isStaff)
        @php
            $showTeamSection      = (bool) \App\Models\Setting::get('qa_team_section', true);
            $showTransferOrder    = (bool) \App\Models\Setting::get('qa_transfer_order', true);
            $showPaymentTracking  = (bool) \App\Models\Setting::get('qa_payment_tracking', true);
            $showCreateInvoice    = auth()->user()->can('generate-pdf-invoice');
            $showShippingTracking = (bool) \App\Models\Setting::get('qa_shipping_tracking', true);
            $showTeamStatus       = (bool) \App\Models\Setting::get('qa_mark_shipped', true) || (bool) \App\Models\Setting::get('qa_mark_paid', true) || (bool) \App\Models\Setting::get('qa_request_info', true);
            $showTeamMerge        = (bool) \App\Models\Setting::get('qa_team_merge', true) && auth()->user()->can('merge-orders');

            $hasTeamQA = $showTeamSection && (
                $showTransferOrder || $showPaymentTracking || $showCreateInvoice ||
                $showShippingTracking || $showTeamMerge
            );
        @endphp

        @if ($hasTeamQA)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4"
                 x-data="{
                     openPanel: null,
                     togglePanel(name) { this.openPanel = this.openPanel === name ? null : name; }
                 }">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('orders.team_quick_actions') }}</h3>

                {{-- Button row --}}
                <div class="flex flex-wrap gap-2 mb-0">

                    {{-- 🔄 تحويل ملكية الطلب --}}
                    @if ($showTransferOrder)
                        <button type="button" @click="$dispatch('open-transfer-order')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition-colors">
                            🔄 {{ __('orders.btn_transfer_order') }}
                        </button>
                    @endif

                    {{-- 💰 تتبع الدفع --}}
                    @if ($showPaymentTracking)
                        <button type="button" @click="togglePanel('payment')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors"
                            :class="openPanel === 'payment'
                                ? 'bg-green-100 text-green-800 border border-green-300'
                                : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200'">
                            💰 {{ __('orders.btn_payment_tracking') }}
                        </button>
                    @endif

                    {{-- 📄 إنشاء فاتورة --}}
                    @if ($showCreateInvoice)
                        <button type="button" @click="togglePanel('invoice')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors"
                            :class="openPanel === 'invoice'
                                ? 'bg-amber-100 text-amber-800 border border-amber-300'
                                : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200'">
                            📄 {{ __('orders.btn_create_invoice') }}
                        </button>
                    @endif

                    {{-- 📦 تحديث معلومات الشحن --}}
                    @if ($showShippingTracking)
                        <button type="button" @click="togglePanel('tracking')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors"
                            :class="openPanel === 'tracking'
                                ? 'bg-purple-100 text-purple-800 border border-purple-300'
                                : 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200'">
                            📦 {{ __('orders.btn_shipping_tracking') }}
                        </button>
                    @endif

                    {{-- 📋 تحديث حالة الطلب --}}
                    @can('update-order-status')
                        <button type="button" @click="togglePanel('status')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors"
                            :class="openPanel === 'status'
                                ? 'bg-orange-100 text-orange-800 border border-orange-300'
                                : 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200'">
                            📋 {{ __('orders.btn_update_status') }}
                        </button>
                        {{-- Mark Paid / Mark Shipped / Request Info quick buttons --}}
                        @php
                            $showMarkPaid    = (bool) \App\Models\Setting::get('qa_mark_paid', true);
                            $showMarkShipped = (bool) \App\Models\Setting::get('qa_mark_shipped', true);
                            $showRequestInfo = (bool) \App\Models\Setting::get('qa_request_info', true);
                            $showStaffCancel = (bool) \App\Models\Setting::get('qa_cancel_order', true);
                        @endphp
                        @if ($showMarkPaid)
                            <form action="{{ route('orders.mark-paid', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    @if ($order->is_paid) disabled title="{{ __('orders.already_paid') }}" @endif
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                        {{ $order->is_paid ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    {{ __('orders.mark_paid') }}
                                </button>
                            </form>
                        @endif
                        @if ($showMarkShipped)
                            <form action="{{ route('orders.status.update', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="shipped">
                                <button type="submit"
                                    @if ($order->status === 'shipped') disabled @endif
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                        {{ $order->status === 'shipped' ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12h12l1-12"/></svg>
                                    {{ __('orders.mark_shipped') }}
                                </button>
                            </form>
                        @endif
                        @if ($showRequestInfo)
                            <form action="{{ route('orders.status.update', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="on_hold">
                                <button type="submit"
                                    @if ($order->status === 'on_hold') disabled @endif
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                        {{ $order->status === 'on_hold' ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ __('orders.request_info') }}
                                </button>
                            </form>
                        @endif
                        @if ($showStaffCancel)
                            <form action="{{ route('orders.status.update', $order->id) }}" method="POST"
                                x-data
                                @submit.prevent="if (confirm('{{ addslashes(__('orders.confirm_cancel')) }}')) $el.submit()">
                                @csrf
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit"
                                    @if ($order->status === 'cancelled') disabled @endif
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors
                                        {{ $order->status === 'cancelled' ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    {{ __('orders.quick_cancel') }}
                                </button>
                            </form>
                        @endif
                    @endcan

                    {{-- 🔗 دمج الطلبات --}}
                    @if ($showTeamMerge && $recentOrders->isNotEmpty())
                        <button type="button" @click="togglePanel('merge')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors"
                            :class="openPanel === 'merge'
                                ? 'bg-orange-100 text-orange-800 border border-orange-300'
                                : 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200'">
                            🔗 {{ __('orders.btn_team_merge') }}
                        </button>
                    @endif

                    {{-- ✉️ إرسال بريد تأكيد الطلب --}}
                    <span x-data="{ sending: false, done: false, err: '' }">
                        <button type="button"
                            :disabled="sending || done"
                            @click="sending = true; err = '';
                                fetch('{{ route('orders.send-email', $order->id) }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                }).then(r => r.json()).then(d => {
                                    sending = false;
                                    if (d.success) { done = true; } else { err = d.message; }
                                }).catch(() => { sending = false; err = '{{ __('Network error. Please try again.') }}'; })"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-colors"
                            :class="done
                                ? 'bg-green-100 text-green-700 border border-green-300 cursor-not-allowed'
                                : sending
                                    ? 'bg-gray-100 text-gray-400 border border-gray-200 cursor-wait'
                                    : 'bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span x-show="!sending && !done">{{ __('orders.send_confirmation_email') }}</span>
                            <span x-show="sending" x-cloak class="animate-pulse">{{ __('Sending…') }}</span>
                            <span x-show="done" x-cloak>✓ {{ __('orders.email_queued') }}</span>
                        </button>
                        <span x-show="err" x-cloak class="text-xs text-red-500 ms-1" x-text="err"></span>
                    </span>

                </div>

                {{-- ── Payment Tracking Panel ──────────────────────────────── --}}
                @if ($showPaymentTracking)
                    <div x-show="openPanel === 'payment'" x-collapse class="mt-4">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-3">
                            <h4 class="text-xs font-semibold text-gray-700">💰 {{ __('orders.panel_payment_tracking') }}</h4>

                            {{-- Current payment info --}}
                            @if ($order->payment_amount)
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-gray-600 mb-2">
                                    <div><span class="text-gray-400">{{ __('orders.payment_amount') }}: </span><strong>{{ number_format($order->payment_amount, 0) }} {{ __('orders.sar') }}</strong></div>
                                    @if ($order->payment_date)<div><span class="text-gray-400">{{ __('orders.payment_date') }}: </span><strong>{{ $order->payment_date->format('Y/m/d') }}</strong></div>@endif
                                    @if ($order->payment_method)<div><span class="text-gray-400">{{ __('orders.payment_method') }}: </span><strong>{{ __('orders.payment_method_' . $order->payment_method) }}</strong></div>@endif
                                    @if ($order->payment_receipt)<div><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($order->payment_receipt) }}" target="_blank" class="text-primary-600 hover:underline font-medium">📄 {{ __('orders.view_receipt') }}</a></div>@endif
                                </div>
                            @endif

                            <form action="{{ route('orders.update-payment', $order->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders.payment_amount') }} ({{ __('orders.sar') }})</label>
                                        <input type="number" name="payment_amount" step="0.01" min="0"
                                            value="{{ $order->payment_amount }}" placeholder="0"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders.payment_date') }}</label>
                                        <input type="date" name="payment_date"
                                            value="{{ $order->payment_date?->format('Y-m-d') }}"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders.payment_method') }}</label>
                                        <select name="payment_method"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                            <option value="">{{ __('orders.select_method') }}</option>
                                            <option value="bank_transfer" @selected($order->payment_method === 'bank_transfer')>{{ __('orders.payment_method_bank_transfer') }}</option>
                                            <option value="credit_card" @selected($order->payment_method === 'credit_card')>{{ __('orders.payment_method_credit_card') }}</option>
                                            <option value="cash" @selected($order->payment_method === 'cash')>{{ __('orders.payment_method_cash') }}</option>
                                            <option value="other" @selected($order->payment_method === 'other')>{{ __('orders.payment_method_other') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ __('orders.payment_receipt_file') }}</label>
                                        <input type="file" name="payment_receipt" accept="image/*,application/pdf"
                                            class="w-full text-xs border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-400">
                                    </div>
                                </div>
                                <button type="submit"
                                    class="bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 px-5 rounded-xl transition-colors">
                                    {{ __('orders.save_payment') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- ── Invoice Panel ───────────────────────────────────────── --}}
                @can('generate-pdf-invoice')
                    <div x-show="openPanel === 'invoice'" x-collapse class="mt-4">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                            <h4 class="text-xs font-semibold text-gray-700 mb-3">📄 {{ __('orders.generate_invoice') }}</h4>
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
                                <p class="text-xs text-gray-400">💡 {{ __('orders.invoice_posted_as_comment') }}</p>
                                <button type="submit"
                                    class="bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 px-5 rounded-xl transition-colors">
                                    {{ __('orders.generate_and_post') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endcan

                {{-- ── Shipping Tracking Panel ─────────────────────────────── --}}
                @if ($showShippingTracking)
                    <div x-show="openPanel === 'tracking'" x-collapse class="mt-4">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                            <h4 class="text-xs font-semibold text-gray-700 mb-3">📦 {{ __('orders.panel_shipping_tracking') }}</h4>
                            @if ($order->tracking_number)
                                <div class="flex items-center gap-2 mb-3 text-xs text-gray-600">
                                    <span>🚚 {{ __('orders.current_tracking') }}: <strong class="text-gray-800">{{ $order->tracking_number }}</strong></span>
                                    @if ($order->tracking_company)<span class="text-gray-400">({{ $order->tracking_company }})</span>@endif
                                </div>
                            @endif
                            <form action="{{ route('orders.shipping-tracking', $order->id) }}" method="POST" class="flex flex-wrap gap-3 items-end">
                                @csrf
                                <div class="flex-1 min-w-32">
                                    <label class="block text-xs text-gray-500 mb-1">{{ __('orders.tracking_number') }}</label>
                                    <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" placeholder="{{ __('orders.tracking_number_placeholder') }}"
                                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                </div>
                                <div class="flex-1 min-w-32">
                                    <label class="block text-xs text-gray-500 mb-1">{{ __('orders.tracking_company') }}</label>
                                    <select name="tracking_company"
                                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400">
                                        <option value="">{{ __('orders.select_carrier') }}</option>
                                        <option value="aramex" @selected($order->tracking_company === 'aramex')>أرامكس</option>
                                        <option value="smsa" @selected($order->tracking_company === 'smsa')>سمسا</option>
                                        <option value="dhl" @selected($order->tracking_company === 'dhl')>DHL</option>
                                        <option value="fedex" @selected($order->tracking_company === 'fedex')>FedEx</option>
                                        <option value="ups" @selected($order->tracking_company === 'ups')>UPS</option>
                                        <option value="other" @selected($order->tracking_company === 'other')>{{ __('orders.carrier_other') }}</option>
                                    </select>
                                </div>
                                <button type="submit"
                                    class="shrink-0 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 px-4 rounded-xl transition-colors">
                                    {{ __('orders.save') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- ── Status Update Panel ─────────────────────────────────── --}}
                @can('update-order-status')
                    <div x-show="openPanel === 'status'" x-collapse class="mt-4">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                            <h4 class="text-xs font-semibold text-gray-700 mb-3">📋 {{ __('orders.change_status') }}</h4>
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

                {{-- ── Team Merge Panel ────────────────────────────────────── --}}
                @if ($showTeamMerge && $recentOrders->isNotEmpty())
                    <div x-show="openPanel === 'merge'" x-collapse class="mt-4">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                            <h4 class="text-xs font-semibold text-gray-700 mb-2">🔗 {{ __('orders.merge_orders') }}</h4>
                            <p class="text-xs text-gray-500 mb-3">{{ __('orders.merge_description') }}</p>
                            <form action="{{ route('orders.merge', $order->id) }}" method="POST"
                                x-data
                                @submit.prevent="if (confirm('{{ __('orders.confirm_merge') }}')) $el.submit()">
                                @csrf
                                <div class="space-y-1 max-h-48 overflow-y-auto mb-3 border border-gray-100 rounded-xl divide-y divide-gray-50">
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
                                    class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold py-2 px-5 rounded-xl transition-colors">
                                    {{ __('orders.execute_merge') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

            </div>
        @endif
    @endif

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

    {{-- ── Device / IP metadata (staff only) ─────────────────────────────── --}}
    @if ($isStaff && $orderCreationLog)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
         x-data="{ open: false }">
        <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                {{ __('orders.device_info_title') }}
            </span>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse>
            <div class="px-4 pb-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-xs text-gray-600 border-t border-gray-50 pt-3">
                @if ($orderCreationLog->ip_address)
                <div class="flex justify-between sm:flex-col gap-0.5">
                    <span class="text-gray-400 font-medium">{{ __('orders.device_ip') }}</span>
                    <span class="font-mono">{{ $orderCreationLog->ip_address }}</span>
                </div>
                @endif
                @if ($orderCreationLog->device)
                <div class="flex justify-between sm:flex-col gap-0.5">
                    <span class="text-gray-400 font-medium">{{ __('orders.device_type') }}</span>
                    <span>{{ $orderCreationLog->device }}{{ $orderCreationLog->device_model ? ' — ' . $orderCreationLog->device_model : '' }}</span>
                </div>
                @endif
                @if ($orderCreationLog->browser)
                <div class="flex justify-between sm:flex-col gap-0.5">
                    <span class="text-gray-400 font-medium">{{ __('orders.device_browser') }}</span>
                    <span>{{ $orderCreationLog->browser }}{{ $orderCreationLog->browser_version ? ' ' . $orderCreationLog->browser_version : '' }}</span>
                </div>
                @endif
                @if ($orderCreationLog->os)
                <div class="flex justify-between sm:flex-col gap-0.5">
                    <span class="text-gray-400 font-medium">{{ __('orders.device_os') }}</span>
                    <span>{{ $orderCreationLog->os }}{{ $orderCreationLog->os_version ? ' ' . $orderCreationLog->os_version : '' }}</span>
                </div>
                @endif
                @if ($orderCreationLog->country || $orderCreationLog->city)
                <div class="flex justify-between sm:flex-col gap-0.5">
                    <span class="text-gray-400 font-medium">{{ __('orders.device_location') }}</span>
                    <span>{{ implode(', ', array_filter([$orderCreationLog->city, $orderCreationLog->country])) }}</span>
                </div>
                @endif
                @if ($orderCreationLog->user_agent)
                <div class="col-span-full flex flex-col gap-0.5">
                    <span class="text-gray-400 font-medium">{{ __('orders.device_user_agent') }}</span>
                    <span class="font-mono text-gray-500 break-all">{{ $orderCreationLog->user_agent }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Comments & conversation ────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden order-comments-section" id="comments" data-order-id="{{ $order->id }}" data-mark-read-url="{{ route('orders.comments.mark-read', $order->id) }}">
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

                {{-- System / automated comments get a distinct teal-tinted treatment --}}
                @if ($comment->is_system)
                <div class="comment-item px-4 py-4 bg-teal-50/60 {{ $comment->deleted_at ? 'opacity-60' : '' }}"
                    id="comment-{{ $comment->id }}" data-comment-id="{{ $comment->id }}">
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 bg-teal-100 text-teal-700 mt-0.5">
                            🤖
                        </span>
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-teal-800">{{ __('orders.system_comment_label') }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-700">auto</span>
                                </div>
                                <span class="text-xs text-gray-400 tabular-nums">{{ $comment->created_at->format('Y/m/d H:i') }}</span>
                            </div>
                            <div class="text-sm text-teal-900 leading-relaxed whitespace-pre-wrap break-words">{{ $comment->body }}</div>
                        </div>
                    </div>
                </div>
                @else
                <div class="comment-item px-4 py-4 space-y-2
                    {{ $comment->is_internal ? 'bg-amber-50/50' : '' }}
                    {{ $comment->deleted_at ? 'opacity-60' : '' }}"
                    id="comment-{{ $comment->id }}" data-comment-id="{{ $comment->id }}">

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

                    {{-- Read receipts (staff only) — same placement & UI as WordPress — above body --}}
                    @if ($isStaff && auth()->user()->can('view-comment-reads') && ($customerReads->count() || $staffReads->count()))
                        @php
                            $firstCustomerRead = $customerReads->sortBy('read_at')->first();
                            $staffReadsDeduped = $staffReads->sortByDesc('read_at')->unique('user_id')->values();
                        @endphp
                        <div style="display: flex; gap: 6px; align-items: flex-start; flex-wrap: wrap;">
                            @if ($firstCustomerRead)
                                <span style="font-size: 0.75rem; padding: 4px 8px; background: #10b981; border-radius: 4px; color: #fff; font-weight: 500;">
                                    ✓ {{ __('orders.read_by_customer_at', ['date' => $firstCustomerRead->read_at->format('Y/m/d'), 'time' => $firstCustomerRead->read_at->format('H:i')]) }}
                                </span>
                            @endif
                            @if ($staffReadsDeduped->count() > 0)
                                <div style="position: relative;" x-data="{ open: false }">
                                    <button type="button" class="comment-read-toggle" style="font-size: 0.7rem; padding: 3px 6px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; color: #64748b; cursor: pointer;" data-comment-id="{{ $comment->id }}" @click="open = !open">
                                        {{ __('orders.team_reads_label', ['count' => $staffReadsDeduped->count()]) }}
                                    </button>
                                    <div class="comment-read-list" id="comment-read-{{ $comment->id }}" x-show="open" x-cloak style="position: absolute; top: 100%; right: 0; margin-top: 4px; padding: 10px; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 10; min-width: 200px;" @click.outside="open = false">
                                        @foreach ($staffReadsDeduped as $read)
                                            <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0;">
                                                <strong>{{ optional($read->user)->name ?? 'user#'.$read->user_id }}</strong>
                                                @if (optional($read->user)->email)
                                                    <br><span style="color: #64748b; font-size: 0.9em;">{{ $read->user->email }}</span>
                                                @endif
                                                <br><span style="color: #64748b; font-size: 0.9em;">{{ __('orders.read_by_team_at', ['date' => $read->read_at->format('Y/m/d'), 'time' => $read->read_at->format('H:i')]) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Body --}}
                    @if ($comment->deleted_at)
                        <p class="text-sm text-gray-400 italic">{{ __('orders.comment_deleted_placeholder') }}</p>
                    @else
                        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap break-words">{{ $comment->body }}</div>

                        {{-- Attached files --}}
                        @php $commentFiles = $order->files->where('comment_id', $comment->id); @endphp
                        @if ($commentFiles->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                @foreach ($commentFiles as $commentFile)
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
                                @endforeach
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

                                {{-- WhatsApp: log send then open wa.me (staff, non-internal) --}}
                                @can('send-comment-notification')
                                    @php
                                        $customerPhone = optional($order->user)->phone;
                                        $waText = __('orders.whatsapp_comment_message', [
                                            'order' => $order->order_number,
                                            'body'  => $comment->body,
                                        ]);
                                        $waUrl = $customerPhone && preg_match('/[0-9]/', (string) $customerPhone)
                                            ? 'https://wa.me/' . preg_replace('/\D/', '', $customerPhone) . '?text=' . rawurlencode($waText)
                                            : null;
                                        $waLogUrl = route('orders.comments.log-whatsapp', [$order->id, $comment->id]);
                                        $waLogs = $comment->notificationLogs->where('channel', 'whatsapp');
                                    @endphp
                                    @if ($waUrl)
                                        <button type="button"
                                            x-data="{ loading: false }"
                                            @click="loading = true; fetch('{{ $waLogUrl }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).then(() => { window.open('{{ $waUrl }}', '_blank'); }).finally(() => loading = false)"
                                            :disabled="loading"
                                            class="inline-flex items-center gap-1 text-xs transition-colors"
                                            :class="loading ? 'text-gray-300 cursor-wait' : 'text-gray-400 hover:text-green-600'">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.122 1.532 5.86L.057 23.86a.5.5 0 00.617.61l6.162-1.617A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.907 0-3.693-.503-5.24-1.383l-.376-.214-3.896 1.022 1.01-3.797-.234-.39A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                            </svg>
                                            {{ __('orders.send_by_whatsapp') }}
                                        </button>
                                        @if ($waLogs->isNotEmpty())
                                            <span x-data="{ open: false }" class="relative inline-flex items-center gap-1">
                                                <button type="button" @click="open = !open"
                                                    class="text-xs text-gray-400 hover:text-green-600 transition-colors">
                                                    {{ __('orders.whatsapp_history') }} ({{ $waLogs->count() }})
                                                </button>
                                                <div x-show="open" x-collapse
                                                    class="absolute left-0 top-full z-10 mt-1 p-2 bg-white border border-gray-200 rounded-lg shadow text-xs text-gray-600 space-y-1 max-h-32 overflow-y-auto min-w-[180px]"
                                                    @click.outside="open = false">
                                                    @foreach ($waLogs->sortByDesc('sent_at') as $log)
                                                        <div>{{ optional($log->user)->name }} — {{ $log->sent_at->format('Y/m/d H:i') }}</div>
                                                    @endforeach
                                                </div>
                                            </span>
                                        @endif
                                    @else
                                        <span title="{{ __('orders.whatsapp_no_phone') }}"
                                            class="inline-flex items-center gap-1 text-xs text-gray-300 cursor-not-allowed">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.122 1.532 5.86L.057 23.86a.5.5 0 00.617.61l6.162-1.617A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.907 0-3.693-.503-5.24-1.383l-.376-.214-3.896 1.022 1.01-3.797-.234-.39A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                            </svg>
                                            {{ __('orders.send_by_whatsapp') }}
                                        </span>
                                    @endif
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
                @endif {{-- end @else (non-system comment) --}}
            @empty
                <div class="px-4 py-10 text-center text-sm text-gray-400">
                    {{ __('orders.no_comments') }}
                </div>
            @endforelse
        </div>

        {{-- ── Add comment ──────────────────────────────────────────────── --}}
        @php $maxCommentFiles = (int) \App\Models\Setting::get('comment_max_files', 5); @endphp
        <div class="border-t border-gray-100 px-4 py-4">
            <form action="{{ route('orders.comments.store', $order->id) }}" method="POST"
                enctype="multipart/form-data" class="space-y-3"
                x-data="{
                    body: '{{ old('body') }}',
                    pickedFiles: [],
                    maxFiles: {{ $maxCommentFiles }},
                    addFiles(e) {
                        const incoming = Array.from(e.target.files);
                        const remaining = this.maxFiles - this.pickedFiles.length;
                        this.pickedFiles = this.pickedFiles.concat(incoming.slice(0, remaining));
                        e.target.value = '';
                    },
                    removeFile(i) {
                        this.pickedFiles.splice(i, 1);
                    },
                    get fileLabel() {
                        if (this.pickedFiles.length === 0) return '{{ __('orders.attach_files', ['max' => $maxCommentFiles]) }}';
                        return '{{ __('orders.files_selected', ['count' => '']) }}' + this.pickedFiles.length;
                    },
                    submitForm(e) {
                        if (this.pickedFiles.length > 0) {
                            const dt = new DataTransfer();
                            this.pickedFiles.forEach(f => dt.items.add(f));
                            const realInput = e.target.querySelector('input[data-multi-files]');
                            if (realInput) realInput.files = dt.files;
                        }
                    }
                }"
                @submit="submitForm($event)">
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

                <div class="flex items-center gap-3 flex-wrap">
                    {{-- File attach picker (visible trigger) --}}
                    <label class="flex items-center gap-1.5 text-xs cursor-pointer"
                        :class="pickedFiles.length >= maxFiles ? 'text-gray-300 pointer-events-none' : 'text-gray-500 hover:text-gray-700'">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span x-text="fileLabel"></span>
                        <input type="file" class="sr-only" multiple
                            accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                            :disabled="pickedFiles.length >= maxFiles"
                            @change="addFiles($event)">
                    </label>
                    {{-- Hidden file input that carries the actual File objects on submit --}}
                    <input type="file" name="files[]" multiple class="sr-only" data-multi-files
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                    {{-- Chips for selected files --}}
                    <template x-for="(f, i) in pickedFiles" :key="i">
                        <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-600 rounded-full px-2 py-0.5 max-w-[140px]">
                            <span class="truncate" x-text="f.name"></span>
                            <button type="button" @click="removeFile(i)"
                                class="text-gray-400 hover:text-red-500 shrink-0 leading-none">✕</button>
                        </span>
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

    {{-- ══════════════════════════════════════════════════════════════════════
         Customer Quick Actions — إجراءات سريعة للعميل (below comments)
    ══════════════════════════════════════════════════════════════════════ --}}
    @if ($isOwner)
        @php
            $showCustomerSection   = (bool) \App\Models\Setting::get('qa_customer_section', true);
            $showPaymentNotifyBtn  = (bool) \App\Models\Setting::get('qa_payment_notify', true);
            $showShippingAddrBtn   = (bool) \App\Models\Setting::get('qa_shipping_address_btn', true);
            $showSimilarOrderBtn   = (bool) \App\Models\Setting::get('qa_similar_order', true);
            $showCustomerMergeBtn  = (bool) \App\Models\Setting::get('qa_customer_merge', true);
            $showCustomerCancelBtn = (bool) \App\Models\Setting::get('qa_customer_cancel', true);

            $hasCustomerQA = $showCustomerSection && (
                $showPaymentNotifyBtn ||
                $showShippingAddrBtn  ||
                $showSimilarOrderBtn  ||
                ($showCustomerMergeBtn && $customerRecentOrders->isNotEmpty()) ||
                ($showCustomerCancelBtn && $order->isCancellable())
            );
        @endphp

        @if ($hasCustomerQA)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('orders.customer_quick_actions') }}</h3>
                <div class="flex flex-wrap gap-2">

                    {{-- 💰 إبلاغ عن تحويل مبلغ --}}
                    @if ($showPaymentNotifyBtn)
                        <button type="button" @click="$dispatch('open-payment-notify')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 transition-colors">
                            💰 {{ __('orders.btn_payment_notify') }}
                        </button>
                    @endif

                    {{-- 📍 تحديد عنوان الشحن --}}
                    @if ($showShippingAddrBtn)
                        <button type="button" @click="$dispatch('open-address-selector')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors">
                            @if ($order->shipping_address_id)
                                ✅ {{ __('orders.btn_address_set') }}
                            @else
                                📍 {{ __('orders.btn_shipping_address') }}
                            @endif
                        </button>
                    @endif

                    {{-- 📝 طلب مشابه --}}
                    @if ($showSimilarOrderBtn)
                        <button type="button" @click="$dispatch('open-similar-order')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors">
                            📝 {{ __('orders.btn_similar_order') }}
                        </button>
                    @endif

                    {{-- 🔀 طلب دمج الطلبات --}}
                    @if ($showCustomerMergeBtn && $customerRecentOrders->isNotEmpty())
                        <button type="button" @click="$dispatch('open-customer-merge')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition-colors">
                            🔀 {{ __('orders.btn_customer_merge') }}
                        </button>
                    @endif

                    {{-- ❌ إلغاء الطلب --}}
                    @if ($showCustomerCancelBtn && $order->isCancellable())
                        <button type="button" @click="$dispatch('open-customer-cancel')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 transition-colors">
                            ❌ {{ __('orders.btn_customer_cancel') }}
                        </button>
                    @endif

                </div>
            </div>
        @endif
    @endif

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
        openLightbox(src, gallery) {
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
        }
    }"
    @open-lightbox.window="openLightbox($event.detail.src, $event.detail.gallery || [])"
    @keydown.escape.window="if (show) close()"
    @keydown.arrow-left.window="if (show) prev()"
    @keydown.arrow-right.window="if (show) next()"
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

{{-- ═══════════════════════════════════════════════════════════════════════════
     Customer Action Modals
═══════════════════════════════════════════════════════════════════════════ --}}

{{-- 💰 Payment Notification Modal --}}
@if ($isOwner)
<div
    x-data="{ show: false }"
    @open-payment-notify.window="show = true"
    x-show="show" x-cloak
    :class="{ 'pointer-events-none': !show }"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40"
    @keydown.escape.window="show = false">
    <div @click.outside="show = false"
        class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">💰 {{ __('orders.modal_payment_notify_title') }}</h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('orders.payment-notify', $order->id) }}" method="POST" class="px-5 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('orders.transfer_amount') }} <span class="text-red-400">*</span></label>
                <input type="number" name="transfer_amount" step="0.01" min="0.01" required
                    placeholder="{{ __('orders.transfer_amount_placeholder') }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('orders.transfer_bank') }} <span class="text-red-400">*</span></label>
                <select name="transfer_bank" required
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                    <option value="">{{ __('orders.select_bank') }}</option>
                    <option value="الراجحي">الراجحي</option>
                    <option value="الأهلي">الأهلي</option>
                    <option value="الإنماء">الإنماء</option>
                    <option value="الرياض">الرياض</option>
                    <option value="سامبا">سامبا</option>
                    <option value="البلاد">البلاد</option>
                    <option value="الجزيرة">الجزيرة</option>
                    <option value="الفرنسي">الفرنسي</option>
                    <option value="العربي">العربي</option>
                    <option value="stc pay">stc pay</option>
                    <option value="مدى">مدى</option>
                    <option value="{{ __('orders.bank_other') }}">{{ __('orders.bank_other') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('orders.transfer_notes') }}</label>
                <textarea name="transfer_notes" rows="3"
                    placeholder="{{ __('orders.transfer_notes_placeholder') }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition resize-none"></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 rounded-xl transition-colors">
                    {{ __('orders.send_notification') }}
                </button>
                <button type="button" @click="show = false"
                    class="flex-1 border border-gray-200 text-gray-600 text-sm py-2 rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('orders.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 📍 Address Selector Modal (already handled inline in header — this dispatches to open it) --}}
{{-- The address selector is in the order header card. Button dispatches scroll + open. --}}
@push('scripts')
<script>
window.addEventListener('open-address-selector', () => {
    const btn = document.querySelector('[data-open-address]');
    if (btn) { btn.click(); btn.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
});
</script>
@endpush

{{-- 📝 Similar Order Modal --}}
<div
    x-data="{ show: false }"
    @open-similar-order.window="show = true"
    x-show="show" x-cloak
    :class="{ 'pointer-events-none': !show }"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40"
    @keydown.escape.window="show = false">
    <div @click.outside="show = false"
        class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">📝 {{ __('orders.modal_similar_order_title') }}</h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-5 space-y-4">
            <div class="text-center text-4xl">📝</div>
            <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 space-y-2 text-sm text-gray-600">
                <p class="font-semibold text-gray-800">{{ __('orders.similar_order_how') }}</p>
                <p>{{ __('orders.similar_order_desc') }}</p>
                <ul class="list-disc ps-5 space-y-1 text-xs">
                    <li>{{ __('orders.similar_order_item1') }}</li>
                    <li>{{ __('orders.similar_order_item2') }}</li>
                    <li>{{ __('orders.similar_order_item3') }}</li>
                    <li>{{ __('orders.similar_order_item4') }}</li>
                </ul>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700 mt-2">
                    ✅ <strong>{{ __('orders.similar_order_edit_note') }}</strong>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="show = false"
                    class="flex-1 border border-gray-200 text-gray-600 text-sm py-2 rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('orders.cancel') }}
                </button>
                <a href="{{ route('new-order') }}?duplicate_from={{ $order->id }}"
                    class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 rounded-xl transition-colors text-center">
                    {{ __('orders.open_order_form') }}
                </a>
            </div>
        </div>
    </div>
</div>

{{-- 🔀 Customer Merge Request Modal --}}
@if ($customerRecentOrders->isNotEmpty())
<div
    x-data="{ show: false }"
    @open-customer-merge.window="show = true"
    x-show="show" x-cloak
    :class="{ 'pointer-events-none': !show }"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40"
    @keydown.escape.window="show = false">
    <div @click.outside="show = false"
        class="w-full max-w-lg bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white">
            <h3 class="text-sm font-semibold text-gray-900">🔀 {{ __('orders.modal_customer_merge_title') }}</h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-5">
            <p class="text-sm text-gray-500 mb-4">{{ __('orders.customer_merge_desc') }}</p>
            <form action="{{ route('orders.customer-merge', $order->id) }}" method="POST">
                @csrf
                <div class="border border-gray-100 rounded-xl divide-y divide-gray-50 max-h-64 overflow-y-auto mb-4">
                    @foreach ($customerRecentOrders as $ro)
                        <label class="flex items-center gap-3 px-3 py-3 cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="merge_with_order" value="{{ $ro->id }}" required
                                class="text-primary-500 focus:ring-primary-400 shrink-0">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-800">#{{ $ro->order_number }}</span>
                                <span class="ms-2 text-xs text-gray-400">{{ $ro->created_at->format('Y/m/d') }}</span>
                                <span class="ms-2 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ $ro->statusLabel() }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 rounded-xl transition-colors">
                        {{ __('orders.send_merge_request') }}
                    </button>
                    <button type="button" @click="show = false"
                        class="flex-1 border border-gray-200 text-gray-600 text-sm py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        {{ __('orders.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ❌ Customer Cancel Confirmation Modal --}}
@if ($order->isCancellable())
<div
    x-data="{ show: false }"
    @open-customer-cancel.window="show = true"
    x-show="show" x-cloak
    :class="{ 'pointer-events-none': !show }"
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40"
    @keydown.escape.window="show = false">
    <div @click.outside="show = false"
        class="w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="px-5 py-5 space-y-4 text-center">
            <div class="text-4xl">❌</div>
            <h3 class="text-base font-semibold text-gray-900">{{ __('orders.confirm_cancel_title') }}</h3>
            <p class="text-sm text-gray-500">{{ __('orders.confirm_cancel_desc') }}</p>
            <div class="flex gap-2 pt-1">
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit"
                        class="w-full bg-red-500 hover:bg-red-600 text-white text-sm font-semibold py-2 rounded-xl transition-colors">
                        {{ __('orders.confirm_cancel_btn') }}
                    </button>
                </form>
                <button type="button" @click="show = false"
                    class="flex-1 border border-gray-200 text-gray-600 text-sm py-2 rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('orders.keep_order') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endif {{-- end $isOwner --}}

{{-- ═══════════════════════════════════════════════════════════════════════════
     Staff Action Modals
═══════════════════════════════════════════════════════════════════════════ --}}

{{-- 🔄 Transfer Order Modal --}}
@if ($isStaff)
<div
    x-data="{ show: false, init() { window.addEventListener('open-transfer-order', () => this.show = true) } }"
    x-show="show" x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/40"
    @keydown.escape.window="show = false">
    <div @click.outside="show = false"
        class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">🔄 {{ __('orders.modal_transfer_title') }}</h3>
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-5 space-y-4">
            <div class="bg-blue-50 border-s-4 border-blue-400 rounded-lg p-4 text-xs text-blue-700 space-y-1">
                <p class="font-semibold mb-1">{{ __('orders.transfer_what_happens') }}</p>
                <ul class="list-disc ps-4 space-y-0.5">
                    <li>{{ __('orders.transfer_note1') }}</li>
                    <li>{{ __('orders.transfer_note2') }}</li>
                    <li>{{ __('orders.transfer_note3') }}</li>
                    <li>{{ __('orders.transfer_note4') }}</li>
                </ul>
            </div>
            <form action="{{ route('orders.transfer', $order->id) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('orders.transfer_email_label') }} <span class="text-red-400">*</span></label>
                    <input type="email" name="transfer_email" required
                        placeholder="example@email.com"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                        dir="ltr">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold py-2 rounded-xl transition-colors">
                        {{ __('orders.transfer_btn') }}
                    </button>
                    <button type="button" @click="show = false"
                        class="flex-1 border border-gray-200 text-gray-600 text-sm py-2 rounded-xl hover:bg-gray-50 transition-colors">
                        {{ __('orders.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Transfer: new user credentials modal (shown after redirect when new account was created) --}}
@if (session('transfer_new_user'))
    @php $creds = session('transfer_new_user'); @endphp
<div x-data="{
    show: true,
    copiedEmail: false,
    copiedPass: false,
    async copyToClipboard(id, trigger) {
        const el = document.getElementById(id);
        if (!el) return;
        const text = (el.textContent || el.innerText || '').trim();
        if (!text) return;
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(text);
            } else {
                const range = document.createRange();
                range.selectNodeContents(el);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                if (!document.execCommand('copy')) throw new Error('execCommand failed');
                window.getSelection().removeAllRanges();
            }
            if (trigger === 'email') { this.copiedEmail = true; setTimeout(() => this.copiedEmail = false, 2000); }
            else { this.copiedPass = true; setTimeout(() => this.copiedPass = false, 2000); }
        } catch (e) {}
    }
}"
    x-show="show" x-cloak
    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50">
    <div @click.outside="show = false"
        class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6 space-y-4">
        <h3 class="text-base font-semibold text-gray-900">✅ {{ __('orders.transfer_new_account_created') }}</h3>
        <p class="text-sm text-gray-500">{{ __('orders.transfer_send_creds_hint') }}</p>
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-3">
            <div>
                <p class="text-xs text-gray-400 mb-1">{{ __('orders.transfer_creds_email') }}</p>
                <div class="flex items-center gap-2">
                    <code id="tc-email" class="flex-1 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm" dir="ltr">{{ $creds['email'] }}</code>
                    <span role="button" tabindex="0"
                        @click="copyToClipboard('tc-email', 'email')"
                        @keydown.enter.prevent="copyToClipboard('tc-email', 'email')"
                        class="text-sm text-blue-600 cursor-pointer hover:underline whitespace-nowrap"
                        :class="copiedEmail && 'text-green-600'"
                        x-text="copiedEmail ? copiedLabel : copyLabel"></span>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">{{ __('orders.transfer_creds_password') }}</p>
                <div class="flex items-center gap-2">
                    <code id="tc-pass" class="flex-1 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm tracking-widest" dir="ltr">{{ $creds['password'] }}</code>
                    <span role="button" tabindex="0"
                        @click="copyToClipboard('tc-pass', 'pass')"
                        @keydown.enter.prevent="copyToClipboard('tc-pass', 'pass')"
                        class="text-sm text-blue-600 cursor-pointer hover:underline whitespace-nowrap"
                        :class="copiedPass && 'text-green-600'"
                        x-text="copiedPass ? copiedLabel : copyLabel"></span>
                </div>
            </div>
        </div>
        <button type="button" @click="show = false"
            class="w-full border border-gray-200 text-gray-600 text-sm py-2 rounded-xl hover:bg-gray-50 transition-colors">
            {{ __('orders.close') }}
        </button>
    </div>
</div>
@endif

@endif {{-- end $isStaff --}}

@push('scripts')
<script>
(function() {
  var commentsSection = document.querySelector('.order-comments-section');
  if (!commentsSection || typeof IntersectionObserver === 'undefined') return;
  var orderId = commentsSection.getAttribute('data-order-id');
  var markReadUrl = commentsSection.getAttribute('data-mark-read-url');
  if (!orderId || !markReadUrl) return;

  var viewedComments = new Set();
  var pendingComments = new Set();
  var batchTimer = null;
  var csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  function sendBatch() {
    if (pendingComments.size === 0) return;
    var ids = Array.from(pendingComments);
    pendingComments.clear();
    if (!csrfToken) return;
    fetch(markReadUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ comment_ids: ids })
    }).then(function(r) { return r.json(); }).then(function(data) {
      if (data.success) { ids.forEach(function(id) { viewedComments.add(String(id)); }); }
    }).catch(function() {});
  }

  function scheduleBatch() {
    if (batchTimer) clearTimeout(batchTimer);
    batchTimer = setTimeout(sendBatch, 3000);
  }

  var commentObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (!entry.isIntersecting) return;
      var commentId = entry.target.getAttribute('data-comment-id');
      if (!commentId || viewedComments.has(commentId)) return;
      viewedComments.add(commentId);
      pendingComments.add(commentId);
      scheduleBatch();
    });
  }, { threshold: 0.3 });

  var commentItems = document.querySelectorAll('.comment-item[data-comment-id]');
  commentItems.forEach(function(item) { commentObserver.observe(item); });

  window.addEventListener('beforeunload', sendBatch);
})();
</script>
@endpush

</x-app-layout>
