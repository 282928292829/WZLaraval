<x-app-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-orange-50 via-white to-amber-50">
        <div class="absolute inset-0 pointer-events-none opacity-40"
             style="background-image: radial-gradient(circle at 70% 30%, #fb923c22 0%, transparent 60%), radial-gradient(circle at 20% 80%, #fde68a22 0%, transparent 55%);">
        </div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-24 sm:pb-28 text-center">
            {{-- Badge --}}
            <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-100 text-orange-700 text-xs font-semibold px-3 py-1 mb-6 tracking-wide uppercase">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ __('Trusted Service') }}
            </span>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight mb-4">
                {!! __('Shop from :store worldwide', ['store' => '<span class="text-orange-500">' . __('any store') . '</span>']) !!}
            </h1>

            <p class="text-lg sm:text-xl text-gray-500 max-w-2xl mx-auto mb-8 leading-relaxed">
                {{ __('Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.') }}
            </p>

            {{-- Hero input form --}}
            <form id="hero-order-form" action="{{ route('new-order') }}" method="GET" class="w-full max-w-2xl mx-auto">
                <div class="flex flex-col sm:flex-row gap-2 bg-white rounded-2xl shadow-lg shadow-gray-200/60 border border-gray-100 p-2">
                    <input
                        type="text"
                        name="product_url"
                        id="hero-product-input"
                        aria-label="{{ __('Paste product link') }}"
                        class="flex-1 min-w-0 bg-transparent text-gray-900 placeholder-gray-400 text-base px-4 py-3 focus:outline-none"
                        placeholder="{{ app()->getLocale() === 'ar' ? 'الصق رابط المنتج، أو وصفه إذا لم يكن لديك رابط' : 'Paste a product link, or describe it if you don\'t have one' }}"
                        autocomplete="off"
                        dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
                    >
                    <button type="submit"
                            class="flex-shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white font-semibold px-6 py-3 text-base shadow-sm transition-all duration-150 whitespace-nowrap">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        {{ __('Start Order') }}
                    </button>
                </div>
            </form>

            {{-- WhatsApp secondary CTA --}}
            <div class="mt-5">
                <a href="https://wa.me/00966556063500"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 text-gray-500 hover:text-green-600 text-sm font-medium transition-colors duration-150">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    {{ __('Or order on WhatsApp') }}
                </a>
            </div>

            {{-- Name change notice --}}
            <div class="mt-3">
                <a href="/wasetamazon-to-wasetzon"
                   class="text-xs text-gray-400 hover:text-orange-500 transition-colors duration-150">
                    وسيط أمازون صار وسيط زون (السبب؟)
                </a>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                    {{ __('How it works') }}
                </h2>
                <p class="text-gray-500 max-w-xl mx-auto">
                    {{ __('Three simple steps and your order is on its way') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
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
                <div class="relative bg-gray-50 rounded-2xl p-6 border border-gray-100">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-xs font-bold text-orange-400 tracking-widest">{{ $item['step'] }}</span>
                            <h3 class="font-semibold text-gray-900 mt-0.5 mb-1">
                                {{ $item['title'] }}
                            </h3>
                            <p class="text-sm text-gray-500 leading-relaxed">
                                {{ $item['desc'] }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why us --}}
    <section class="py-16 sm:py-20 bg-gray-50 border-t border-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                    {{ __('Why Wasetzon?') }}
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
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
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-start gap-4">
                    <div class="text-3xl flex-shrink-0">{{ $feat['icon'] }}</div>
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-1">
                            {{ $feat['title'] }}
                        </h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            {{ $feat['desc'] }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 sm:py-20 bg-orange-500">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">
                {{ __('Ready to start? Place your order now') }}
            </h2>
            <p class="text-orange-100 mb-8 text-base">
                {{ __('Create a free account and place your first order in minutes.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-white text-orange-600 hover:bg-orange-50 font-semibold px-8 py-3.5 text-base transition-all duration-150 shadow-md">
                    {{ __('Register') }}
                </a>
                <a href="{{ route('new-order') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-white/40 text-white hover:bg-white/10 font-semibold px-8 py-3.5 text-base transition-all duration-150">
                    {{ __('New Order') }}
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
