<x-guest-layout>
    {{-- No-scroll form: compact on mobile so all fields + submit visible without scrolling (LARAVEL_PLAN) --}}
    <div class="mb-3 sm:mb-5">
        <h1 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('Log in') }}</h1>
        <p class="mt-0.5 sm:mt-1 text-xs sm:text-sm text-gray-500">
            {{ __('Enter your credentials to access your account') }}
        </p>
    </div>

    <x-auth-session-status class="mb-3 sm:mb-4" :status="session('status')" />

    @include('auth.partials.social-buttons', ['mode' => 'login'])

    <form method="POST" action="{{ route('login') }}" class="space-y-2.5 sm:space-y-3">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm" />
            <x-text-input id="email"
                          class="block mt-0.5 sm:mt-1 w-full py-2 sm:py-2.5"
                          type="email"
                          name="email"
                          :value="old('email')"
                          required
                          autofocus
                          autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-0.5 sm:mt-1 text-xs" />
        </div>

        <div>
            <div class="flex items-center justify-between mb-0.5 sm:mb-1">
                <x-input-label for="password" :value="__('Password')" class="text-sm" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-[11px] sm:text-xs text-primary-600 hover:text-primary-700 transition-colors">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password"
                          class="block w-full py-2 sm:py-2.5"
                          type="password"
                          name="password"
                          required
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-0.5 sm:mt-1 text-xs" />
        </div>

        <div>
            <label for="remember_me" class="inline-flex items-center gap-1.5 cursor-pointer">
                <input id="remember_me"
                       type="checkbox"
                       class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 size-3.5 sm:size-4"
                       name="remember">
                <span class="text-xs sm:text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <x-primary-button class="w-full justify-center py-2 sm:py-2.5 text-sm">
            {{ __('Log in') }}
        </x-primary-button>

        <p class="text-center text-[11px] sm:text-sm text-gray-500 pt-0.5">
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}"
               class="font-medium text-primary-600 hover:text-primary-700 transition-colors">
                {{ __('Register') }}
            </a>
        </p>
    </form>
</x-guest-layout>
