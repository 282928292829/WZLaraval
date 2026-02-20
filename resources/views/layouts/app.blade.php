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
        <footer class="bg-gray-50 border-t border-gray-100 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                    <p>
                        &copy; {{ date('Y') }} {{ __('app.name') }}.
                        @if (app()->getLocale() === 'ar')
                            جميع الحقوق محفوظة.
                        @else
                            All rights reserved.
                        @endif
                    </p>
                    <div class="flex items-center gap-4">
                        @if (app()->getLocale() === 'ar')
                            <form method="POST" action="{{ route('language.switch', 'en') }}">
                                @csrf
                                <button type="submit"
                                        class="hover:text-gray-700 transition-colors">
                                    English
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('language.switch', 'ar') }}">
                                @csrf
                                <button type="submit"
                                        class="hover:text-gray-700 transition-colors"
                                        style="font-family: 'IBM Plex Sans Arabic', sans-serif;">
                                    العربية
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </footer>

        @livewireScripts

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
