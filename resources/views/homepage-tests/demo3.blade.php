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
         DEMO 3: Bold Graphic — Circular Steps + Big Stats
         White hero with large circles, step numbers in circles,
         Why-us as big data stats
    ══════════════════════════════════════════ --}}

    <style>
    .d3-hero-ring {
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(249,115,22,0.12);
    }
    .d3-connector {
        position: absolute;
        top: 50%;
        width: 100%;
        height: 2px;
        background: linear-gradient(to left, transparent, #fed7aa, transparent);
        transform: translateY(-50%);
        z-index: 0;
    }
    </style>

    {{-- Hero: White centered with decorative rings --}}
    <section class="relative overflow-hidden bg-white pt-20 pb-24 sm:pt-28 sm:pb-32">

        {{-- Decorative concentric rings --}}
        <div class="d3-hero-ring" style="width:500px;height:500px;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.5;"></div>
        <div class="d3-hero-ring" style="width:700px;height:700px;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.3;"></div>
        <div class="d3-hero-ring" style="width:900px;height:900px;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.15;"></div>

        {{-- Orange center glow --}}
        <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 50% 40%, rgba(249,115,22,0.06) 0%, transparent 60%);"></div>

        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

            {{-- Badge --}}
            <span class="inline-flex items-center gap-2 bg-orange-50 border border-orange-200 text-orange-600 text-xs font-bold px-4 py-1.5 rounded-full mb-8 tracking-widest uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                {{ __('Trusted Service') }}
            </span>

            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-gray-950 tracking-tight leading-[1.05] mb-6">
                @if ($heroTitle)
                    {{ $heroTitle }}
                @else
                    {!! __('Shop from :store worldwide', ['store' => '<br><span class="text-orange-500">' . __('any store') . '</span>']) !!}
                @endif
            </h1>

            <p class="text-base sm:text-lg text-gray-500 max-w-xl mx-auto mb-10 leading-relaxed">
                {{ $heroSubtitle ?: __('Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.') }}
            </p>

            {{-- Inline form --}}
            <form id="hero-order-form" action="{{ route('new-order') }}" method="GET" class="w-full max-w-xl mx-auto"
                  @if ($heroInputRequired)
                  x-data
                  @submit="const inp = document.getElementById('hero-product-input'); if (!inp || !inp.value.trim()) { $event.preventDefault(); inp?.focus(); inp?.setCustomValidity('{{ __('Please enter a product link or description.') }}'); inp?.reportValidity(); } else { inp?.setCustomValidity(''); }"
                  @endif
            >
                <div class="flex flex-col sm:flex-row gap-2 p-1.5 bg-gray-50 border-2 border-gray-200 rounded-2xl focus-within:border-orange-400 transition-colors">
                    <input
                        type="text"
                        name="product_url"
                        id="hero-product-input"
                        aria-label="{{ __('Paste product link') }}"
                        class="flex-1 min-w-0 bg-transparent text-gray-900 placeholder-gray-400 text-sm px-4 py-3 focus:outline-none"
                        placeholder="{{ $heroPlaceholder ?: __('Paste a product link, or describe it if you don\'t have one') }}"
                        autocomplete="off"
                        dir="rtl"
                        @if ($heroInputRequired) required @endif
                    >
                    <button type="submit"
                            class="flex-shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 active:scale-95 text-white font-bold px-6 py-3 text-sm transition-all whitespace-nowrap shadow-lg shadow-orange-200">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        {{ $heroButtonText ?: __('Start Order') }}
                    </button>
                </div>
            </form>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-5">
                @if ($heroShowWhatsapp && $heroWhatsappNum)
                <a href="https://wa.me/{{ $heroWhatsappNum }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 text-gray-400 hover:text-green-600 text-xs font-medium transition-colors">
                    <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    {{ $heroWhatsappText ?: __('Or order via WhatsApp') }}
                </a>
                @endif

                @if ($heroShowNameNotice)
                <a href="{{ route('pages.show', 'wasetamazon-to-wasetzon') }}"
                   class="text-xs text-gray-300 hover:text-orange-400 transition-colors">
                    {{ __('Waset Amazon is now Waset Zone (why?)') }}
                </a>
                @endif
            </div>
        </div>
    </section>

    {{-- How it works —— Circular numbered steps with connector --}}
    <section class="py-20 sm:py-24 bg-orange-500">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-2xl sm:text-3xl font-black text-white mb-3">{{ __('How it works') }}</h2>
                <p class="text-orange-100 text-sm">{{ __('Three simple steps and your order is on its way') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 sm:gap-4 relative">
                {{-- Connector line (desktop only) --}}
                <div class="d3-connector hidden sm:block"></div>

                @foreach([
                    [
                        'num' => '1',
                        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                        'title' => __('Send the product link'),
                        'desc' => __('Copy the product URL from any store and paste it into the order form.')
                    ],
                    [
                        'num' => '2',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'title' => __('We handle the purchase'),
                        'desc' => __('Our team reviews your order and purchases the items on your behalf.')
                    ],
                    [
                        'num' => '3',
                        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        'title' => __('We ship to you'),
                        'desc' => __('We ship your order directly to your address with full tracking.')
                    ],
                ] as $item)
                <div class="relative z-10 text-center flex flex-col items-center">
                    {{-- Large numbered circle --}}
                    <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center mb-5 shadow-xl shadow-orange-700/30">
                        <span class="text-3xl font-black text-orange-500">{{ $item['num'] }}</span>
                    </div>
                    <svg class="w-5 h-5 text-orange-200 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <h3 class="font-bold text-white text-base mb-2">{{ $item['title'] }}</h3>
                    <p class="text-orange-100 text-sm leading-relaxed max-w-xs">{{ $item['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why us —— Big data stats --}}
    <section class="py-20 sm:py-24 bg-gray-950">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-2xl sm:text-3xl font-black text-white mb-3">
                    {{ __('Why Wasetzon?', ['site_name' => $site_name ?? config('app.name')]) }}
                </h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                @foreach([
                    [
                        'stat' => '70%',
                        'icon' => '💰',
                        'title' => __('Save up to 70%'),
                        'desc' => __('We consolidate your orders from different stores into one package and save you a fortune.'),
                    ],
                    [
                        'stat' => '90',
                        'icon' => '⏲️',
                        'title' => __('90 days free storage'),
                        'desc' => __('We give you the freedom to shop for 3 months with free and secure storage for your products.'),
                    ],
                    [
                        'stat' => '0%',
                        'icon' => '🏠',
                        'title' => __('Based in Delaware — tax free'),
                        'desc' => __('Our warehouse in Delaware is fully exempt from US sales tax.'),
                    ],
                    [
                        'stat' => '📦',
                        'icon' => '📦',
                        'title' => __('Remove excess packaging'),
                        'desc' => __('We reduce package size by removing excess packaging to lower your shipping costs.'),
                    ],
                ] as $feat)
                <div class="text-center p-5 rounded-2xl" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);">
                    <div class="text-4xl font-black text-orange-400 mb-2 leading-none">
                        @if ($feat['stat'] === '📦')
                            <span class="text-3xl">📦</span>
                        @else
                            {{ $feat['stat'] }}
                        @endif
                    </div>
                    <h3 class="text-xs font-semibold text-white mb-2 leading-tight">{{ $feat['title'] }}</h3>
                    <p class="text-xs text-gray-500 leading-relaxed hidden sm:block">{{ $feat['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA: Dark charcoal --}}
    <section class="bg-white border-t border-gray-100 py-20 sm:py-24">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mb-6">
                <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h2 class="text-2xl sm:text-3xl font-black text-gray-950 mb-4">
                {{ __('Ready to start? Place your order now') }}
            </h2>
            <p class="text-gray-500 mb-8 text-base leading-relaxed">
                {{ __('Create a free account and place your first order in minutes.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-500 hover:bg-orange-600 text-white font-bold px-10 py-4 text-base transition-colors shadow-xl shadow-orange-200">
                    {{ __('Register') }}
                </a>
                <a href="{{ route('new-order') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-2xl border-2 border-gray-200 hover:border-gray-400 text-gray-700 font-semibold px-10 py-4 text-base transition-colors">
                    {{ __('New Order') }}
                </a>
            </div>
        </div>
    </section>

</x-app-layout>
