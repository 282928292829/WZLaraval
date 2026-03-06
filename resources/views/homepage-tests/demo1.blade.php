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
         DEMO 1: Split Panel
         Orange branding panel | White form panel
    ══════════════════════════════════════════ --}}

    {{-- Hero: Two-column split --}}
    <section class="flex flex-col lg:flex-row min-h-[88vh]">

        {{-- Orange panel (right side in RTL = start) --}}
        <div class="flex-1 bg-orange-500 relative overflow-hidden flex items-center justify-center px-8 py-16 lg:py-24">
            {{-- Decorative circles --}}
            <div class="absolute -top-16 -start-16 w-64 h-64 bg-orange-400 rounded-full opacity-30"></div>
            <div class="absolute -bottom-24 -end-12 w-96 h-96 bg-orange-600 rounded-full opacity-20"></div>

            <div class="relative z-10 max-w-sm w-full">
                {{-- Badge --}}
                <span class="inline-flex items-center gap-2 bg-white/20 text-white text-xs font-semibold px-4 py-1.5 rounded-full mb-8 tracking-wide">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ __('Trusted Service') }}
                </span>

                <h1 class="text-4xl sm:text-5xl font-extrabold text-white leading-tight mb-5">
                    @if ($heroTitle)
                        {{ $heroTitle }}
                    @else
                        {!! __('Shop from :store worldwide', ['store' => '<span class="text-orange-950/60">' . __('any store') . '</span>']) !!}
                    @endif
                </h1>

                <p class="text-orange-100 text-base leading-relaxed mb-8">
                    {{ $heroSubtitle ?: __('Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.') }}
                </p>

                {{-- WhatsApp on orange side --}}
                @if ($heroShowWhatsapp && $heroWhatsappNum)
                <a href="https://wa.me/{{ $heroWhatsappNum }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    {{ $heroWhatsappText ?: __('Or order via WhatsApp') }}
                </a>
                @endif

                @if ($heroShowNameNotice)
                <div class="mt-5">
                    <a href="{{ route('pages.show', 'wasetamazon-to-wasetzon') }}"
                       class="text-xs text-white/50 hover:text-white/80 transition-colors">
                        {{ __('Waset Amazon is now Waset Zone (why?)') }}
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- White form panel (left side in RTL) --}}
        <div class="flex-1 bg-white flex items-center justify-center px-8 py-16 lg:py-24">
            <div class="w-full max-w-sm">
                <div class="mb-8">
                    <p class="text-xs font-semibold text-orange-500 uppercase tracking-widest mb-2">{{ __('Start Order') }}</p>
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                        {{ __('Paste a product link, or describe it if you don\'t have one') }}
                    </h2>
                </div>

                <form id="hero-order-form" action="{{ route('new-order') }}" method="GET"
                      @if ($heroInputRequired)
                      x-data
                      @submit="const inp = document.getElementById('hero-product-input'); if (!inp || !inp.value.trim()) { $event.preventDefault(); inp?.focus(); inp?.setCustomValidity('{{ __('Please enter a product link or description.') }}'); inp?.reportValidity(); } else { inp?.setCustomValidity(''); }"
                      @endif
                >
                    <textarea
                        name="product_url"
                        id="hero-product-input"
                        rows="4"
                        aria-label="{{ __('Paste product link') }}"
                        class="w-full text-gray-900 placeholder-gray-400 text-sm px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 transition-colors resize-none mb-3"
                        placeholder="{{ $heroPlaceholder ?: __('Paste a product link, or describe it if you don\'t have one') }}"
                        autocomplete="off"
                        dir="rtl"
                        @if ($heroInputRequired) required @endif
                    ></textarea>
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white font-bold px-6 py-3.5 text-base shadow-lg shadow-orange-200 transition-all duration-150">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        {{ $heroButtonText ?: __('Start Order') }}
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-400 text-center mb-3">{{ __('New here?') }}</p>
                    <a href="{{ route('register') }}"
                       class="w-full inline-flex items-center justify-center gap-2 rounded-xl border-2 border-gray-200 hover:border-orange-300 text-gray-700 hover:text-orange-600 font-medium px-6 py-3 text-sm transition-all duration-150">
                        {{ __('Register') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works —— Large numbered watermark cards --}}
    <section class="py-20 sm:py-24 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">{{ __('How it works') }}</h2>
                <p class="text-gray-400 text-sm">{{ __('Three simple steps and your order is on its way') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                @foreach([
                    [
                        'num' => '١',
                        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                        'title' => __('Send the product link'),
                        'desc' => __('Copy the product URL from any store and paste it into the order form.')
                    ],
                    [
                        'num' => '٢',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'title' => __('We handle the purchase'),
                        'desc' => __('Our team reviews your order and purchases the items on your behalf.')
                    ],
                    [
                        'num' => '٣',
                        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        'title' => __('We ship to you'),
                        'desc' => __('We ship your order directly to your address with full tracking.')
                    ],
                ] as $item)
                <div class="relative bg-white rounded-2xl p-7 border border-gray-100 shadow-sm overflow-hidden group hover:border-orange-200 hover:shadow-md transition-all duration-200">
                    {{-- Watermark number --}}
                    <span class="absolute -top-4 -end-3 text-[7rem] font-black text-gray-100 leading-none select-none group-hover:text-orange-50 transition-colors">{{ $item['num'] }}</span>
                    <div class="relative z-10">
                        <div class="w-11 h-11 rounded-xl bg-orange-50 border border-orange-100 flex items-center justify-center mb-5">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 text-base mb-2">{{ $item['title'] }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">{{ $item['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why us —— Clean 2×2 grid --}}
    <section class="py-20 sm:py-24 bg-white border-t border-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                    {{ __('Why Wasetzon?', ['site_name' => $site_name ?? config('app.name')]) }}
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                @foreach([
                    [
                        'icon' => '📦',
                        'title' => __('Remove excess packaging'),
                        'desc' => __('We reduce package size by removing excess packaging to lower your shipping costs.'),
                        'color' => 'bg-blue-50 border-blue-100',
                    ],
                    [
                        'icon' => '🏠',
                        'title' => __('Based in Delaware — tax free'),
                        'desc' => __('Our warehouse in Delaware is fully exempt from US sales tax.'),
                        'color' => 'bg-green-50 border-green-100',
                    ],
                    [
                        'icon' => '💰',
                        'title' => __('Save up to 70%'),
                        'desc' => __('We consolidate your orders from different stores into one package and save you a fortune.'),
                        'color' => 'bg-orange-50 border-orange-100',
                    ],
                    [
                        'icon' => '⏲️',
                        'title' => __('90 days free storage'),
                        'desc' => __('We give you the freedom to shop for 3 months with free and secure storage for your products.'),
                        'color' => 'bg-purple-50 border-purple-100',
                    ],
                ] as $feat)
                <div class="rounded-2xl border p-6 flex items-start gap-4 {{ $feat['color'] }}">
                    <div class="text-3xl flex-shrink-0 mt-0.5">{{ $feat['icon'] }}</div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1.5 text-base">{{ $feat['title'] }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $feat['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 sm:py-20 bg-orange-500">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white mb-4">
                {{ __('Ready to start? Place your order now') }}
            </h2>
            <p class="text-orange-100 mb-8 text-base">
                {{ __('Create a free account and place your first order in minutes.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-white text-orange-600 hover:bg-orange-50 font-bold px-8 py-3.5 text-base transition-all duration-150 shadow-lg">
                    {{ __('Register') }}
                </a>
                <a href="{{ route('new-order') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-white/50 text-white hover:bg-white/10 font-semibold px-8 py-3.5 text-base transition-all duration-150">
                    {{ __('New Order') }}
                </a>
            </div>
        </div>
    </section>

</x-app-layout>
