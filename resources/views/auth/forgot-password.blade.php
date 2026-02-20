<x-guest-layout>
    {{-- Heading --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900">{{ __('Forgot your password?') }}</h1>
        <p class="mt-2 text-sm text-gray-500 leading-relaxed">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </p>
    </div>

    {{-- Session status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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
                          autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        {{-- Submit --}}
        <x-primary-button class="w-full justify-center">
            {{ __('Email Password Reset Link') }}
        </x-primary-button>

        {{-- Back to login --}}
        <p class="text-center text-sm text-gray-500">
            <a href="{{ route('login') }}"
               class="font-medium text-primary-600 hover:text-primary-700 transition-colors">
                &larr; {{ __('Log in') }}
            </a>
        </p>
    </form>
</x-guest-layout>
