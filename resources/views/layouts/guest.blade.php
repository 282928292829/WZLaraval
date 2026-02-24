<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? __('app.name') }}</title>

        {{-- Fonts: Inter (Latin) + IBM Plex Sans Arabic --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-sans-arabic:300,400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col">

        @include('layouts.navigation')

        @if (session('error'))
            <div class="bg-red-50 border-b border-red-100">
                <div class="max-w-md mx-auto px-4 py-3">
                    <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <div class="flex flex-col items-center px-4 pt-8 pb-12 flex-1">
            <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-7">
                {{ $slot }}
            </div>
        </div>

        <footer class="py-5 text-center text-xs text-gray-400">
            <span>&copy; {{ date('Y') }} {{ __('app.name') }}</span>
        </footer>

        @livewireScripts

        @stack('scripts')

        <x-dev-toolbar />
    </body>
</html>
