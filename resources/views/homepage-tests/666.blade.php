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
<x-app-layout :minimal-footer="true">

<style>
.hp666-dot-grid {
    background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
    background-size: 28px 28px;
}
.hp666-gradient-text {
    background: linear-gradient(135deg, #ffffff 0%, #a1a1aa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hp666-glow {
    box-shadow: 0 0 0 1px rgba(255,255,255,0.06), 0 8px 32px rgba(0,0,0,0.4);
}
</style>

    {{-- Hero --}}
    <section style="background:#09090b; color:#fff;" class="relative overflow-hidden">
        <div class="hp666-dot-grid absolute inset-0 pointer-events-none"></div>
        {{-- Subtle gradient orb --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div style="position:absolute;top:-20%;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(249,115,22,0.06) 0%,transparent 70%);border-radius:50%;"></div>
        </div>

        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 sm:pt-28 sm:pb-32 text-center">

            {{-- Badge --}}
            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1 mb-8 rounded-full" style="border:1px solid rgba(255,255,255,0.1);color:#a1a1aa;background:rgba(255,255,255,0.03);">
                <svg class="w-3 h-3" style="color:#22c55e;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ __('Trusted Service') }}
            </span>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6 leading-tight">
                @if ($heroTitle)
                    <span class="hp666-gradient-text">{{ $heroTitle }}</span>
                @else
                    <span class="hp666-gradient-text">{!! __('Shop from :store worldwide', ['store' => '<span style="-webkit-text-fill-color:#f97316;background:none;">' . __('any store') . '</span>']) !!}</span>
                @endif
            </h1>

            <p class="text-base sm:text-lg max-w-xl mx-auto mb-10 leading-relaxed" style="color:#71717a;">
                {{ $heroSubtitle ?: __('Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.') }}
            </p>

            {{-- Hero input form --}}
            <form id="hero-order-form" action="{{ route('new-order') }}" method="GET" class="w-full max-w-xl mx-auto"
                  @if ($heroInputRequired)
                  x-data
                  @submit="const inp = document.getElementById('hero-product-input'); if (!inp || !inp.value.trim()) { $event.preventDefault(); inp?.focus(); inp?.setCustomValidity('{{ __('Please enter a product link or description.') }}'); inp?.reportValidity(); } else { inp?.setCustomValidity(''); }"
                  @endif
            >
                <div class="flex flex-col sm:flex-row gap-2 rounded-xl p-1.5 hp666-glow" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                    <input
                        type="text"
                        name="product_url"
                        id="hero-product-input"
                        aria-label="{{ __('Paste product link') }}"
                        class="flex-1 min-w-0 text-sm px-4 py-3 rounded-lg focus:outline-none transition-all"
                        style="background:transparent;color:#fff;caret-color:#f97316;"
                        placeholder="{{ $heroPlaceholder ?: __('Paste a product link, or describe it if you don\'t have one') }}"
                        autocomplete="off"
                        dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
                        @if ($heroInputRequired) required @endif
                    >
                    <button type="submit"
                            class="flex-shrink-0 inline-flex items-center justify-center gap-2 rounded-lg font-semibold px-6 py-3 text-sm transition-all whitespace-nowrap"
                            style="background:#f97316;color:#fff;"
                            onmouseover="this.style.background='#ea6c0c'" onmouseout="this.style.background='#f97316'">
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        {{ $heroButtonText ?: __('Start Order') }}
                    </button>
                </div>
            </form>

            {{-- WhatsApp --}}
            @if ($heroShowWhatsapp && $heroWhatsappNum)
            <div class="mt-6">
                <a href="https://wa.me/{{ $heroWhatsappNum }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 text-xs font-medium transition-colors"
                   style="color:#52525b;"
                   onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#52525b'">
                    <svg class="w-3.5 h-3.5" style="color:#22c55e;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    {{ $heroWhatsappText ?: __('Or order via WhatsApp') }}
                </a>
            </div>
            @endif

            {{-- Name change notice --}}
            @if ($heroShowNameNotice)
            <div class="mt-4">
                <a href="{{ route('pages.show', 'wasetamazon-to-wasetzon') }}"
                   class="text-xs transition-colors"
                   style="color:#3f3f46;"
                   onmouseover="this.style.color='#f97316'" onmouseout="this.style.color='#3f3f46'">
                    {{ __('Waset Amazon is now Waset Zone (why?)') }}
                </a>
            </div>
            @endif

        </div>
    </section>

    {{-- How it works --}}
    <section style="background:#0d0d10;border-top:1px solid rgba(255,255,255,0.06);" class="py-20 sm:py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-xl sm:text-2xl font-bold mb-3" style="color:#fff;">
                    {{ __('How it works') }}
                </h2>
                <p class="text-sm max-w-sm mx-auto" style="color:#52525b;">
                    {{ __('Three simple steps and your order is on its way') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach([
                    [
                        'step' => '01',
                        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                        'title' => __('Send the product link'),
                        'desc' => __('Copy the product URL from any store and paste it into the order form.')
                    ],
                    [
                        'step' => '02',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'title' => __('We handle the purchase'),
                        'desc' => __('Our team reviews your order and purchases the items on your behalf.')
                    ],
                    [
                        'step' => '03',
                        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        'title' => __('We ship to you'),
                        'desc' => __('We ship your order directly to your address with full tracking.')
                    ],
                ] as $item)
                <div class="rounded-xl p-6" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                    <span class="text-xs font-mono tabular-nums block mb-5" style="color:#3f3f46;">{{ $item['step'] }}</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-4" style="background:rgba(249,115,22,0.1);">
                        <svg class="w-4 h-4" style="color:#f97316;" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold mb-2" style="color:#e4e4e7;">{{ $item['title'] }}</h3>
                    <p class="text-sm leading-relaxed" style="color:#52525b;">{{ $item['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why us --}}
    <section style="background:#09090b;border-top:1px solid rgba(255,255,255,0.06);" class="py-20 sm:py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-xl sm:text-2xl font-bold mb-3" style="color:#fff;">
                    {{ __('Why Wasetzon?', ['site_name' => $site_name ?? config('app.name')]) }}
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <div class="rounded-xl p-6 flex items-start gap-4" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                    <span class="text-2xl flex-shrink-0">{{ $feat['icon'] }}</span>
                    <div>
                        <h3 class="text-sm font-semibold mb-1.5" style="color:#e4e4e7;">{{ $feat['title'] }}</h3>
                        <p class="text-sm leading-relaxed" style="color:#52525b;">{{ $feat['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section style="background:#0d0d10;border-top:1px solid rgba(255,255,255,0.06);" class="py-20 sm:py-24">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-xl sm:text-2xl font-bold mb-3" style="color:#fff;">
                {{ __('Ready to start? Place your order now') }}
            </h2>
            <p class="text-sm mb-8 leading-relaxed" style="color:#52525b;">
                {{ __('Create a free account and place your first order in minutes.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg font-semibold px-7 py-3 text-sm transition-all"
                   style="background:#fff;color:#09090b;"
                   onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                    {{ __('Register') }}
                </a>
                <a href="{{ route('new-order') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg font-medium px-7 py-3 text-sm transition-all"
                   style="border:1px solid rgba(255,255,255,0.12);color:#a1a1aa;"
                   onmouseover="this.style.borderColor='rgba(255,255,255,0.24)';this.style.color='#fff'" onmouseout="this.style.borderColor='rgba(255,255,255,0.12)';this.style.color='#a1a1aa'">
                    {{ __('New Order') }}
                </a>
            </div>
        </div>
    </section>

</x-app-layout>
