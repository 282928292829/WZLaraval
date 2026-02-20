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
    <body class="antialiased bg-gray-50 text-gray-900 min-h-screen">

        {{-- Top bar: logo + language toggle --}}
        <div class="flex items-center justify-between px-4 pt-4 pb-2 max-w-sm mx-auto">
            <a href="{{ url('/') }}"
               class="text-xl font-bold text-primary-600 tracking-tight">
                {{ __('app.name') }}
            </a>

            <div class="flex items-center gap-1">
                @if (app()->getLocale() === 'ar')
                    <form method="POST" action="{{ route('language.switch', 'en') }}">
                        @csrf
                        <button type="submit"
                                class="text-xs font-medium text-gray-500 hover:text-gray-700 px-2 py-1 rounded-md hover:bg-gray-100 transition-colors">
                            English
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('language.switch', 'ar') }}">
                        @csrf
                        <button type="submit"
                                class="text-sm font-medium text-gray-500 hover:text-gray-700 px-2 py-1 rounded-md hover:bg-gray-100 transition-colors"
                                style="font-family: 'IBM Plex Sans Arabic', sans-serif;">
                            العربية
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Auth card --}}
        <div class="flex flex-col items-center px-4 pt-2 pb-8">
            <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-7">
                {{ $slot }}
            </div>
        </div>

        @livewireScripts
    </body>
</html>
