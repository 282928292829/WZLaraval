@php
    $heroTitle = trim((string) \App\Models\Setting::get('hero_title', ''));
    $heroSubtitle = trim((string) \App\Models\Setting::get('hero_subtitle', ''));
    $heroPlaceholder = trim((string) \App\Models\Setting::get('hero_input_placeholder', ''));
    $heroButtonText = trim((string) \App\Models\Setting::get('hero_button_text', ''));
    $heroShowWhatsapp = (bool) \App\Models\Setting::get('hero_show_whatsapp', true);
    $heroWhatsappText = trim((string) \App\Models\Setting::get('hero_whatsapp_button_text', ''));
    $heroWhatsappNum = trim((string) \App\Models\Setting::get('hero_whatsapp_number', ''));
    if (!$heroWhatsappNum) {
        $heroWhatsappNum = preg_replace('/\D/', '', \App\Models\Setting::get('whatsapp', ''));
    } else {
        $heroWhatsappNum = preg_replace('/\D/', '', $heroWhatsappNum);
    }
    $heroShowNameNotice = (bool) \App\Models\Setting::get('hero_show_name_change_notice', true);
    $heroInputRequired = (bool) \App\Models\Setting::get('hero_input_required', false);
@endphp
<x-app-layout>

    {{-- ══════════════════════════════════════════
         DEMO 4: Deep Dark Amber
         Warm dark background, orange glow hero,
         Horizontal feature strip, vertical step cards
    ══════════════════════════════════════════ --}}

    <style>
    .d4-bg       { background: #120a02; }
    .d4-bg-mid   { background: #1a1005; }
    .d4-bg-light { background: #1f1408; }
    .d4-border   { border-color: rgba(249,115,22,0.12); }
    .d4-card     { background: rgba(249,115,22,0.05); border: 1px solid rgba(249,115,22,0.12); }
    .d4-text     { color: #f5d9b0; }
    .d4-muted    { color: #9a7550; }
    .d4-glow {
        position: absolute;
        width: 800px; height: 500px;
        top: -100px; left: 50%; transform: translateX(-50%);
        background: radial-gradient(ellipse at 50% 0%, rgba(249,115,22,0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .d4-step-num {
        position: absolute;
        inset-inline-end: 16px;
        bottom: 12px;
        font-size: 5rem;
        font-weight: 900;
        line-height: 1;
        color: rgba(249,115,22,0.08);
        user-select: none;
    }
    </style>

    {{-- Hero --}}
    <section class="d4-bg relative overflow-hidden pt-20 pb-28 sm:pt-28 sm:pb-36">
        <div class="d4-glow"></div>

        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

            {{-- Badge --}}
            <span class="inline-flex items-center gap-2 text-xs font-semibold px-4 py-1.5 rounded-full mb-8 tracking-widest"
                  style="background:rgba(249,115,22,0.12);color:#fb923c;border:1px solid rgba(249,115,22,0.2);">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ __('Trusted Service') }}
            </span>

            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black leading-[1.05] mb-6 tracking-tight"
                style="color:#fff3e0;">
                @if ($heroTitle)
                    {{ $heroTitle }}
                @else
                    {!! __('Shop from :store worldwide', ['store' => '<span style="color:#f97316;">' . __('any store') . '</span>']) !!}
                @endif
            </h1>

            <p class="text-base sm:text-lg max-w-xl mx-auto mb-10 leading-relaxed d4-text">
                {{ $heroSubtitle ?: __('Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.') }}
            </p>

            {{-- Hero input --}}
            <form id="hero-order-form" action="{{ route('new-order') }}" method="GET" class="w-full max-w-xl mx-auto"
                  @if ($heroInputRequired)
                  x-data
                  @submit="const inp = document.getElementById('hero-product-input'); if (!inp || !inp.value.trim()) { $event.preventDefault(); inp?.focus(); inp?.setCustomValidity('{{ __('Please enter a product link or description.') }}'); inp?.reportValidity(); } else { inp?.setCustomValidity(''); }"
                  @endif
            >
                <div class="flex flex-col sm:flex-row gap-2 p-1.5 rounded-2xl"
                     style="background:rgba(255,255,255,0.05);border:1px solid rgba(249,115,22,0.2);box-shadow:0 0 40px rgba(249,115,22,0.08);">
                    <input
                        type="text"
                        name="product_url"
                        id="hero-product-input"
                        aria-label="{{ __('Paste product link') }}"
                        class="flex-1 min-w-0 text-sm px-4 py-3 rounded-xl focus:outline-none transition-all"
                        style="background:transparent;color:#fff3e0;caret-color:#f97316;"
                        placeholder="{{ $heroPlaceholder ?: __('Paste a product link, or describe it if you don\'t have one') }}"
                        autocomplete="off"
                        dir="rtl"
                        @if ($heroInputRequired) required @endif
                    >
                    <button type="submit"
                            class="flex-shrink-0 inline-flex items-center justify-center gap-2 rounded-xl font-bold px-6 py-3 text-sm transition-all whitespace-nowrap"
                            style="background:#f97316;color:#fff;box-shadow:0 4px 20px rgba(249,115,22,0.4);"
                            onmouseover="this.style.background='#ea6c0c'" onmouseout="this.style.background='#f97316'">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        {{ $heroButtonText ?: __('Start Order') }}
                    </button>
                </div>
            </form>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-5">
                @if ($heroShowWhatsapp && $heroWhatsappNum)
                <a href="https://wa.me/{{ $heroWhatsappNum }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 text-xs font-medium transition-colors d4-muted"
                   onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color=''">
                    <svg class="w-3.5 h-3.5" style="color:#22c55e;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    {{ $heroWhatsappText ?: __('Or order via WhatsApp') }}
                </a>
                @endif

                @if ($heroShowNameNotice)
                <a href="{{ route('pages.show', 'wasetamazon-to-wasetzon') }}"
                   class="text-xs transition-colors d4-muted"
                   onmouseover="this.style.color='#f97316'" onmouseout="this.style.color=''">
                    {{ __('Waset Amazon is now Waset Zone (why?)') }}
                </a>
                @endif
            </div>
        </div>
    </section>

    {{-- How it works —— Tall vertical cards with huge watermark numbers --}}
    <section class="d4-bg-mid py-20 sm:py-24" style="border-top:1px solid rgba(249,115,22,0.1);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-2xl sm:text-3xl font-black mb-3" style="color:#fff3e0;">{{ __('How it works') }}</h2>
                <p class="text-sm d4-muted">{{ __('Three simple steps and your order is on its way') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                @foreach([
                    [
                        'num' => '01',
                        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                        'title' => __('Send the product link'),
                        'desc' => __('Copy the product URL from any store and paste it into the order form.')
                    ],
                    [
                        'num' => '02',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'title' => __('We handle the purchase'),
                        'desc' => __('Our team reviews your order and purchases the items on your behalf.')
                    ],
                    [
                        'num' => '03',
                        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        'title' => __('We ship to you'),
                        'desc' => __('We ship your order directly to your address with full tracking.')
                    ],
                ] as $item)
                <div class="d4-card relative rounded-2xl p-6 overflow-hidden" style="min-height:200px;">
                    <span class="d4-step-num">{{ $item['num'] }}</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-5"
                         style="background:rgba(249,115,22,0.12);border:1px solid rgba(249,115,22,0.2);">
                        <svg class="w-5 h-5" style="color:#f97316;" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-base mb-2" style="color:#fff3e0;">{{ $item['title'] }}</h3>
                    <p class="text-sm leading-relaxed d4-muted">{{ $item['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why us —— Horizontal feature strip --}}
    <section class="d4-bg-light py-20 sm:py-24" style="border-top:1px solid rgba(249,115,22,0.1);">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-2xl sm:text-3xl font-black mb-3" style="color:#fff3e0;">
                    {{ __('Why Wasetzon?', ['site_name' => $site_name ?? config('app.name')]) }}
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    [
                        'icon' => '📦',
                        'title' => __('Remove excess packaging'),
                        'desc' => __('We reduce package size by removing excess packaging to lower your shipping costs.'),
                    ],
                    [
                        'icon' => '🏠',
                        'title' => __('Based in Delaware — tax free'),
                        'desc' => __('Our warehouse in Delaware is fully exempt from US sales tax.'),
                    ],
                    [
                        'icon' => '💰',
                        'title' => __('Save up to 70%'),
                        'desc' => __('We consolidate your orders from different stores into one package and save you a fortune.'),
                    ],
                    [
                        'icon' => '⏲️',
                        'title' => __('90 days free storage'),
                        'desc' => __('We give you the freedom to shop for 3 months with free and secure storage for your products.'),
                    ],
                ] as $feat)
                <div class="d4-card rounded-2xl p-5 text-center">
                    <div class="text-3xl mb-3">{{ $feat['icon'] }}</div>
                    <h3 class="text-sm font-bold mb-2" style="color:#fff3e0;">{{ $feat['title'] }}</h3>
                    <p class="text-xs leading-relaxed d4-muted">{{ $feat['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA: Orange gradient --}}
    <section class="relative overflow-hidden py-20 sm:py-24"
             style="background:linear-gradient(135deg,#c2410c 0%,#f97316 50%,#ea580c 100%);">
        {{-- Decorative pattern --}}
        <div class="absolute inset-0 pointer-events-none opacity-10"
             style="background-image:radial-gradient(circle at 20% 50%, #fff 0%, transparent 40%), radial-gradient(circle at 80% 50%, #fff 0%, transparent 40%);"></div>

        <div class="relative max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-black text-white mb-4">
                {{ __('Ready to start? Place your order now') }}
            </h2>
            <p class="text-orange-100 mb-10 text-base leading-relaxed">
                {{ __('Create a free account and place your first order in minutes.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl font-bold px-10 py-4 text-base transition-all"
                   style="background:#120a02;color:#f97316;box-shadow:0 8px 30px rgba(0,0,0,0.3);"
                   onmouseover="this.style.background='#1f1408'" onmouseout="this.style.background='#120a02'">
                    {{ __('Register') }}
                </a>
                <a href="{{ route('new-order') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl font-semibold px-10 py-4 text-base transition-all"
                   style="background:rgba(255,255,255,0.15);color:#fff;border:2px solid rgba(255,255,255,0.3);"
                   onmouseover="this.style.background='rgba(255,255,255,0.22)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                    {{ __('New Order') }}
                </a>
            </div>
        </div>
    </section>

</x-app-layout>
