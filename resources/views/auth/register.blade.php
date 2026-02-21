<x-guest-layout>
    {{-- Heading --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900">{{ __('Register') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Create your new account') }}
        </p>
    </div>

    @include('auth.partials.social-buttons', ['mode' => 'register'])

    <form method="POST" action="{{ route('register') }}" class="space-y-3">
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
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password"
                          class="block mt-1 w-full"
                          type="password"
                          name="password"
                          required
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        {{-- Confirm Password --}}
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation"
                          class="block mt-1 w-full"
                          type="password"
                          name="password_confirmation"
                          required
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        {{-- Submit --}}
        <x-primary-button class="w-full justify-center mt-1">
            {{ __('Register') }}
        </x-primary-button>

        {{-- Login link --}}
        <p class="text-center text-sm text-gray-500 pt-1">
            {{ __('Already registered?') }}
            <a href="{{ route('login') }}"
               class="font-medium text-primary-600 hover:text-primary-700 transition-colors">
                {{ __('Log in') }}
            </a>
        </p>
    </form>
</x-guest-layout>
