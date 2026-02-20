<x-guest-layout>
    {{-- Heading --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900">{{ __('Log in') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            @if (app()->getLocale() === 'ar')
                أدخل بياناتك للدخول إلى حسابك
            @else
                Enter your credentials to access your account
            @endif
        </p>
    </div>

    {{-- Session status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-3">
        @csrf

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email"
                          class="block mt-1 w-full"
                          type="email"
                          name="email"
                          :value="old('email')"
                          required
                          autofocus
                          autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs text-primary-600 hover:text-primary-700 transition-colors">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password"
                          class="block w-full"
                          type="password"
                          name="password"
                          required
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        {{-- Remember me --}}
        <div>
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me"
                       type="checkbox"
                       class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                       name="remember">
                <span class="text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        {{-- Submit --}}
        <x-primary-button class="w-full justify-center mt-1">
            {{ __('Log in') }}
        </x-primary-button>

        {{-- Register link --}}
        <p class="text-center text-sm text-gray-500 pt-1">
            @if (app()->getLocale() === 'ar')
                ليس لديك حساب؟
            @else
                Don't have an account?
            @endif
            <a href="{{ route('register') }}"
               class="font-medium text-primary-600 hover:text-primary-700 transition-colors">
                {{ __('Register') }}
            </a>
        </p>
    </form>
</x-guest-layout>
