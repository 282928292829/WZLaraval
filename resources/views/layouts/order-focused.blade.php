{{-- Order-focused layout: header + nav, no footer. For new order page, sticky submit bars, forms. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $primaryColor = trim((string) \App\Models\Setting::get('primary_color', '#f97316'));
            $primaryHover = \App\Support\ColorHelper::darken($primaryColor, 5);
            $primaryLight = \App\Support\ColorHelper::lighten($primaryColor, 92);
            $primaryLight2 = \App\Support\ColorHelper::lighten($primaryColor, 80);
        @endphp
        <meta name="theme-color" content="{{ $primaryColor }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ __('app.name') }}">
        <meta name="description" content="{{ $description ?? __('app.description') }}">

        <x-hreflang />

        <title>{{ isset($title) ? $title . ' — ' . __('app.name') : __('app.name') }}</title>

        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="icon" href="{{ \App\Models\Setting::faviconUrl('site') }}">

        {{-- Fonts: custom (from settings) or default --}}
        @if(\App\Support\FontHelper::hasCustomFont())
            {!! \App\Support\FontHelper::getFontHeadHtml() !!}
        @else
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        @endif

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
    <body class="antialiased bg-white text-gray-900 min-h-screen flex flex-col" style="font-family: {{ \App\Support\FontHelper::cssFontFamily() }};">

        @include('layouts.navigation')

        @isset($header)
            <div class="bg-white border-b border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    {{ $header }}
                </div>
            </div>
        @endisset

        <main class="flex-1">
            {!! $slot ?? '' !!}
        </main>

        {{-- No footer: for sticky submit bars, forms, mobile/desktop order flows --}}

        @livewireScripts
        @stack('scripts')

        <x-dev-toolbar />

        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .catch(() => {});
            }
        </script>
    </body>
</html>
