@php
    $googleEnabled   = \App\Models\Setting::get('google_login_enabled', false);
    $twitterEnabled = \App\Models\Setting::get('twitter_login_enabled', false);
    $facebookEnabled = \App\Models\Setting::get('facebook_login_enabled', false);
    $appleEnabled   = \App\Models\Setting::get('apple_login_enabled', false);
    $anyEnabled     = $googleEnabled || $twitterEnabled || $facebookEnabled || $appleEnabled;
@endphp

@if ($anyEnabled)
    <div class="space-y-2.5 mb-5">

        @if ($googleEnabled)
            <a href="{{ route('auth.google') }}"
               class="flex items-center justify-center gap-3 w-full border border-gray-300 rounded-lg px-4 py-2.5
                      text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                {{ isset($mode) && $mode === 'register' ? __('Sign up with Google') : __('Sign in with Google') }}
            </a>
        @endif

        @if ($twitterEnabled)
            <a href="{{ route('auth.twitter') }}"
               class="flex items-center justify-center gap-3 w-full border border-gray-300 rounded-lg px-4 py-2.5
                      text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                {{-- X (Twitter) logo --}}
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
                {{ isset($mode) && $mode === 'register' ? __('Sign up with X') : __('Sign in with X') }}
            </a>
        @endif

        @if ($facebookEnabled)
            <a href="{{ route('auth.facebook') }}"
               class="flex items-center justify-center gap-3 w-full border border-gray-300 rounded-lg px-4 py-2.5
                      text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="#1877F2" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                {{ isset($mode) && $mode === 'register' ? __('Sign up with Facebook') : __('Sign in with Facebook') }}
            </a>
        @endif

        @if ($appleEnabled)
            <a href="{{ route('auth.apple') }}"
               class="flex items-center justify-center gap-3 w-full border border-gray-300 rounded-lg px-4 py-2.5
                      text-sm font-medium text-gray-900 bg-black hover:bg-gray-900 transition-colors shadow-sm">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 6.98.48 10.28l-.42 2.93zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                </svg>
                <span class="text-white">{{ isset($mode) && $mode === 'register' ? __('Sign up with Apple') : __('Sign in with Apple') }}</span>
            </a>
        @endif

    </div>

    <div class="relative mb-5">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
        <div class="relative flex justify-center text-xs">
            <span class="bg-white px-3 text-gray-400">{{ __('or') }}</span>
        </div>
    </div>
@endif
