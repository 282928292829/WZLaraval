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
        @php
            $siteName = \App\Models\Setting::get('site_name') ?: __('app.name');
            $siteDefaultDesc = \App\Models\Setting::get('seo_default_meta_description') ?: __('app.description');
            $siteDefaultOg = \App\Models\Setting::get('seo_default_og_image')
                ? url(\Illuminate\Support\Facades\Storage::disk('public')->url(\App\Models\Setting::get('seo_default_og_image')))
                : null;
        @endphp
        <meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
        <meta name="description" content="{{ $description ?? $siteDefaultDesc }}">
        @if(isset($robots) && $robots)
        <meta name="robots" content="{{ $robots }}">
        @endif
        @if(isset($canonicalUrl) && $canonicalUrl)
        <link rel="canonical" href="{{ $canonicalUrl }}">
        @endif
        <x-hreflang />
        {{-- Open Graph --}}
        @php $effectiveOgImage = $ogImage ?? $siteDefaultOg ?? null; @endphp
        <meta property="og:type" content="{{ $ogType ?? 'website' }}">
        <meta property="og:title" content="{{ $ogTitle ?? ($title ?? $siteName) }}">
        <meta property="og:description" content="{{ $ogDescription ?? ($description ?? $siteDefaultDesc) }}">
        <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
        <meta property="og:locale" content="{{ app()->getLocale() === 'ar' ? 'ar_SA' : 'en_US' }}">
        @if($effectiveOgImage)
        <meta property="og:image" content="{{ $effectiveOgImage }}">
        @if(request()->secure())
        <meta property="og:image:secure_url" content="{{ $effectiveOgImage }}">
        @endif
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $ogImageAlt ?? $siteName }}">
        @endif
        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        @php $twitterHandle = trim(ltrim((string) (\App\Models\Setting::get('seo_twitter_handle') ?? ''), '@')); @endphp
        @if($twitterHandle !== '')
        <meta name="twitter:site" content="@{{ $twitterHandle }}">
        @endif
        <meta name="twitter:title" content="{{ $ogTitle ?? ($title ?? $siteName) }}">
        <meta name="twitter:description" content="{{ $ogDescription ?? ($description ?? $siteDefaultDesc) }}">
        @if($effectiveOgImage)
        <meta name="twitter:image" content="{{ $effectiveOgImage }}">
        <meta name="twitter:image:alt" content="{{ $ogImageAlt ?? $siteName }}">
        @endif

        @stack('meta')

        <title>{{ isset($title) ? $title . ' — ' . $siteName : $siteName }}</title>

        @if($googleVerification = \App\Models\Setting::get('seo_google_verification'))
        <meta name="google-site-verification" content="{{ $googleVerification }}">
        @endif
        @if($bingVerification = \App\Models\Setting::get('seo_bing_verification'))
        <meta name="msvalidate.01" content="{{ $bingVerification }}">
        @endif

        @if(isset($schema))
        <script type="application/ld+json">{!! is_array($schema) ? json_encode($schema) : $schema !!}</script>
        @endif

        {{-- PWA manifest + icons --}}
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="icon" href="{{ \App\Models\Setting::faviconUrl('site') }}">

        {{-- Fonts: custom (from settings) or default Inter + IBM Plex Sans Arabic — Google Fonts --}}
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

        @include('layouts.partials.footer')

        @livewireScripts
        @stack('scripts')

        <x-impersonate::banner />

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
