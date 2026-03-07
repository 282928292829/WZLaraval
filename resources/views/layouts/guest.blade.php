<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <x-hreflang />

        <title>{{ $title ?? __('app.name') }}</title>

        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="icon" href="{{ \App\Models\Setting::faviconUrl('site') }}">

        @php
            $primaryColor = trim((string) \App\Models\Setting::get('primary_color', '#f97316'));
            $primaryHover = \App\Support\ColorHelper::darken($primaryColor, 5);
            $primaryLight = \App\Support\ColorHelper::lighten($primaryColor, 92);
            $primaryLight2 = \App\Support\ColorHelper::lighten($primaryColor, 80);
        @endphp
        {{-- Fonts: Inter (Latin) + IBM Plex Sans Arabic --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-sans-arabic:300,400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>:root {
            --primary: {{ $primaryColor }};
            --primary-hover: {{ $primaryHover }};
            --color-primary-50: {{ $primaryLight }};
            --color-primary-100: {{ $primaryLight2 }};
            --color-primary-400: {{ $primaryColor }};
            --color-primary-500: {{ $primaryColor }};
            --color-primary-600: {{ $primaryHover }};
        }</style>
        @livewireStyles
    </head>
    <body class="antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col" style="font-family: {{ \App\Support\FontHelper::cssFontFamily() }};">

        @include('layouts.navigation')

        @if (session('error'))
            <div class="bg-red-50 border-b border-red-100">
                <div class="max-w-md mx-auto px-4 py-3">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- No-scroll forms: compact on mobile so sign-in/sign-up fit without scrolling (LARAVEL_PLAN) --}}
        <div class="flex flex-col items-center px-3 sm:px-4 pt-4 sm:pt-8 pb-8 sm:pb-12 flex-1 min-h-0">
            <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-4 sm:px-6 sm:py-7">
                {{ $slot }}
            </div>
        </div>

        @php
            $yearInitiated = trim((string) \App\Models\Setting::get('copyright_year_initiated', ''));
            $currentYear = date('Y');
            $copyrightYear = ($yearInitiated !== '' && preg_match('/^\d{4}$/', $yearInitiated) && (int) $yearInitiated <= (int) $currentYear)
                ? ((int) $yearInitiated === (int) $currentYear ? $currentYear : $yearInitiated . '–' . $currentYear)
                : $currentYear;
        @endphp
        <footer class="py-5 text-center text-xs text-gray-400">
            <span>&copy; {{ $copyrightYear }} {{ __('app.name') }}.</span>
            <a href="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
               class="ml-2 text-gray-400 hover:text-gray-500"
               style="{{ app()->getLocale() === 'ar' ? '' : "font-family: 'IBM Plex Sans Arabic', sans-serif;" }}">
                ({{ app()->getLocale() === 'ar' ? __('English') : __('Arabic') }})
            </a>
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
