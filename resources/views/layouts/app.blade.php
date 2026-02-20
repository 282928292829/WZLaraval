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
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-7 flex flex-col items-center gap-5">
                        <img src="/images/footer-banks.svg"
                             alt="Banks"
                             class="w-full max-w-xs sm:max-w-sm object-contain opacity-75"
                             loading="lazy">
                        <img src="/images/footer-payments.svg"
                             alt="Payment Methods"
                             class="w-full max-w-sm sm:max-w-md object-contain opacity-75"
                             loading="lazy">
                        <img src="/images/footer-shipping.svg"
                             alt="Shipping Partners"
                             class="w-full max-w-md sm:max-w-lg object-contain opacity-75"
                             loading="lazy">
                    </div>
                </div>
            @endif

            {{-- Bottom bar: legal + copyright + language --}}
            <div class="border-t border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-400">

                        {{-- Legal / registration --}}
                        <div class="flex flex-col items-center sm:items-start gap-1.5 text-center sm:text-start">
                            @if ($commercialReg)
                                <span>{{ __('footer.commercial_reg') }}: {{ $commercialReg }}</span>
                            @endif
                            <a href="https://eauthenticate.saudibusiness.gov.sa/certificate-details/0000020424"
                               target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 hover:text-gray-600 transition-colors">
                                {{ __('footer.certified_by') }}
                            </a>
                        </div>

                        {{-- Copyright --}}
                        <p class="text-center">
                            &copy; {{ date('Y') }} {{ __('app.name') }}.
                            {{ __('footer.all_rights') }}.
                        </p>

                        {{-- Language toggle --}}
                        <div>
                            @if (app()->getLocale() === 'ar')
                                <form method="POST" action="{{ route('language.switch', 'en') }}">
                                    @csrf
                                    <button type="submit" class="hover:text-gray-600 transition-colors">English</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('language.switch', 'ar') }}">
                                    @csrf
                                    <button type="submit"
                                            class="hover:text-gray-600 transition-colors"
                                            style="font-family: 'IBM Plex Sans Arabic', sans-serif;">
                                        العربية
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

        </footer>

        @livewireScripts

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
