<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f97316">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ __('app.name') }}">
        <meta name="description" content="{{ $description ?? __('app.description') }}">

        <title>{{ isset($title) ? $title . ' — ' . __('app.name') : __('app.name') }}</title>

        {{-- PWA manifest + icons --}}
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-96x96.png">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        {{-- Fonts: Inter (Latin) + IBM Plex Sans Arabic --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-sans-arabic:300,400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="antialiased bg-white text-gray-900 min-h-screen flex flex-col">

        @include('layouts.navigation')

        {{-- Global error flash (e.g. dev login: test user not found) --}}
        @if (session('error'))
            <div class="bg-red-50 border-b border-red-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- Optional page heading slot --}}
        @isset($header)
            <div class="bg-white border-b border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    {{ $header }}
                </div>
            </div>
        @endisset

        {{-- Main content --}}
        <main class="flex-1">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        @php
            $footerWhatsapp = \App\Models\Setting::get('whatsapp', '');
            $footerEmail    = \App\Models\Setting::get('contact_email', '');
            $commercialReg  = \App\Models\Setting::get('commercial_registration', '');
        @endphp
        <footer class="bg-gray-50 border-t border-gray-100 mt-auto">

            {{-- Main footer links --}}
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-8">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-8">

                    {{-- Column 1: معلومات --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.information') }}</h4>
                        <ul class="space-y-2.5">
                            <li><a href="{{ url('/pages/how-to-order') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.how_to_order') }}</a></li>
                            <li><a href="{{ url('/pages/shipping-calculator') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.shipping_calculator') }}</a></li>
                            <li><a href="{{ url('/pages/payment-methods') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.payment_methods') }}</a></li>
                            <li><a href="{{ url('/pages/faq') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.faq') }}</a></li>
                        </ul>
                    </div>

                    {{-- Column 2: خدمات --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.services') }}</h4>
                        <ul class="space-y-2.5">
                            <li><a href="{{ url('/new-order') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.new_order') }}</a></li>
                            <li><a href="{{ url('/orders') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.my_orders') }}</a></li>
                            <li><a href="{{ url('/pages/calculator') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.currency_converter') }}</a></li>
                            <li><a href="{{ url('/pages/membership') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.membership') }}</a></li>
                        </ul>
                    </div>

                    {{-- Column 3: سياسات --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.policies') }}</h4>
                        <ul class="space-y-2.5">
                            <li><a href="{{ url('/pages/terms-and-conditions') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.terms') }}</a></li>
                            <li><a href="{{ url('/pages/privacy-policy') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.privacy') }}</a></li>
                            <li><a href="{{ url('/pages/refund-policy') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.refund') }}</a></li>
                        </ul>
                    </div>

                    {{-- Column 4: معلومات الإتصال --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.contact') }}</h4>
                        <ul class="space-y-2.5">
                            @if ($footerWhatsapp)
                                <li>
                                    <span class="text-xs text-gray-400 block">{{ __('footer.whatsapp') }}</span>
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $footerWhatsapp) }}"
                                       target="_blank" rel="noopener"
                                       class="text-sm text-gray-600 hover:text-primary-600 transition-colors">
                                        {{ $footerWhatsapp }}
                                    </a>
                                </li>
                            @endif
                            @if ($footerEmail)
                                <li>
                                    <span class="text-xs text-gray-400 block">{{ __('footer.email') }}</span>
                                    <a href="mailto:{{ $footerEmail }}"
                                       class="text-sm text-gray-600 hover:text-primary-600 transition-colors">
                                        {{ $footerEmail }}
                                    </a>
                                </li>
                            @endif
                            <li class="text-sm text-gray-500">{{ __('footer.support_hours') }}</li>
                        </ul>
                    </div>

                </div>
            </div>

            {{-- Partners / logos section --}}
            @php $showPartners = \App\Models\Setting::get('show_partners', true); @endphp
            @if ($showPartners)
                <div class="border-t border-gray-100">
                    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col items-center gap-6">
                        <h4 class="text-sm font-semibold text-gray-500">{{ __('footer.partners') }}</h4>
                        {{-- Bank logos ordered right→left: Rajhi, Ahli, Bilad, Alinma, SAB, Riyad, SAIB --}}
                        <div class="flex flex-wrap items-center justify-center gap-5 opacity-70" dir="ltr">
                            @foreach([
                                ['src' => 'images/banks/saib.svg',    'alt' => 'SAIB'],
                                ['src' => 'images/banks/riyad.svg',   'alt' => 'Riyad Bank'],
                                ['src' => 'images/banks/sab.svg',     'alt' => 'SAB'],
                                ['src' => 'images/banks/alinma.svg',  'alt' => 'Alinma Bank'],
                                ['src' => 'images/banks/albilad.svg', 'alt' => 'Bank Albilad'],
                                ['src' => 'images/banks/snb.svg',     'alt' => 'NCB / Ahli'],
                                ['src' => 'images/banks/rajhi.svg',   'alt' => 'Al Rajhi Bank'],
                            ] as $bank)
                                <img src="{{ asset($bank['src']) }}"
                                     alt="{{ $bank['alt'] }}"
                                     class="h-7 w-auto object-contain"
                                     loading="lazy">
                            @endforeach
                        </div>
                        {{-- Individual payment logos — flex so RTL ordering works correctly --}}
                        <div class="flex flex-wrap items-center justify-center gap-5 opacity-70">
                            @foreach([
                                ['src' => 'images/payment/visa.svg',          'alt' => 'Visa',          'h' => 'h-6'],
                                ['src' => 'images/payment/mastercard.svg',     'alt' => 'Mastercard',    'h' => 'h-8'],
                                ['src' => 'images/payment/paypal.svg',         'alt' => 'PayPal',        'h' => 'h-6'],
                                ['src' => 'images/payment/western-union.svg',  'alt' => 'Western Union', 'h' => 'h-7'],
                                ['src' => 'images/payment/moneygram.svg',      'alt' => 'MoneyGram',     'h' => 'h-7'],
                            ] as $pm)
                                <img src="{{ asset($pm['src']) }}"
                                     alt="{{ $pm['alt'] }}"
                                     class="{{ $pm['h'] }} w-auto object-contain"
                                     loading="lazy">
                            @endforeach
                        </div>
                        <img src="{{ asset('images/shipping-line.svg') }}"
                             alt="Shipping"
                             class="w-full max-w-2xl object-contain opacity-70"
                             loading="lazy"
                             height="18">
                    </div>
                </div>
            @endif

            {{-- Bottom bar: legal + last updated + copyright --}}
            <div class="border-t border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-4 text-center text-xs text-gray-400">

                    {{-- Commercial registration + SBC badge --}}
                    @if ($commercialReg)
                        <div>
                            <span>{{ __('footer.commercial_reg') }}: {{ $commercialReg }}</span>
                        </div>
                    @endif
                    <div>
                        <a href="https://eauthenticate.saudibusiness.gov.sa/certificate-details/0000020424"
                           target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:border-gray-300 hover:text-gray-600 transition-colors text-xs text-gray-500">
                            <img src="https://wasetzon.com/wp-content/uploads/2024/04/شعار-المركز-السعودي-للأعمال-–-Saudi-Business-Center-Logo-–-PNG-–-SVG-svg-1.png"
                                 alt="Saudi Business Center"
                                 class="w-6 h-6 object-contain"
                                 loading="lazy">
                            {{ __('footer.certified_by') }}
                        </a>
                    </div>

                    {{-- Last updated --}}
                    @php
                        $manifestPath = public_path('build/manifest.json');
                        $lastUpdated  = file_exists($manifestPath) ? filemtime($manifestPath) : null;
                    @endphp
                    @if ($lastUpdated)
                        <div>
                            <strong>{{ __('footer.last_updated') }}:</strong>
                            {{ date('Y/m/d', $lastUpdated) }} - {{ date('H:i', $lastUpdated) }}
                        </div>
                    @endif

                    {{-- Language toggle --}}
                    <div>
                        <form method="POST" action="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}">
                            @csrf
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:text-gray-800 border border-gray-200 hover:border-gray-400 rounded-lg transition-colors"
                                    style="{{ app()->getLocale() === 'ar' ? '' : "font-family: 'IBM Plex Sans Arabic', sans-serif;" }}">
                                {{ __('Switch language text') }}
                            </button>
                        </form>
                    </div>

                    {{-- Copyright --}}
                    <p>&copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('footer.all_rights') }}.</p>

                </div>
            </div>

        </footer>

        @livewireScripts
        @stack('scripts')

        <x-dev-toolbar />

        {{-- PWA service worker registration --}}
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js', { scope: '/' })
                        .catch(() => {});
                });
            }
        </script>
    </body>
</html>
