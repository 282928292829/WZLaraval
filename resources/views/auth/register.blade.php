<x-guest-layout>
    {{-- No-scroll form: compact on mobile so all fields + submit visible without scrolling (LARAVEL_PLAN) --}}
    <div class="mb-3 sm:mb-5">
        <h1 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('Register') }}</h1>
        <p class="mt-0.5 sm:mt-1 text-xs sm:text-sm text-gray-500">
            {{ __('Create your new account') }}
        </p>
    </div>

    @include('auth.partials.social-buttons', ['mode' => 'register'])

    <form method="POST" action="{{ route('register') }}" class="space-y-2.5 sm:space-y-3">
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
            <x-input-label for="password" :value="__('Password')" class="text-sm" />
            <x-text-input id="password"
                          class="block mt-0.5 sm:mt-1 w-full py-2 sm:py-2.5"
                          type="password"
                          name="password"
                          required
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-0.5 sm:mt-1 text-xs" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-sm" />
            <x-text-input id="password_confirmation"
                          class="block mt-0.5 sm:mt-1 w-full py-2 sm:py-2.5"
                          type="password"
                          name="password_confirmation"
                          required
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-0.5 sm:mt-1 text-xs" />
        </div>

        <x-primary-button class="w-full justify-center py-2 sm:py-2.5 text-sm">
            {{ __('Register') }}
        </x-primary-button>

        <p class="text-center text-[11px] sm:text-sm text-gray-500 pt-0.5">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}"
               class="font-medium text-primary-600 hover:text-primary-700 transition-colors">
                {{ __('Log in') }}
            </a>
        </p>
    </form>
</x-guest-layout>
