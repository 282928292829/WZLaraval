<x-guest-layout>
    {{-- Heading --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900">{{ __('Reset Password') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('Choose a new password for your account') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email"
                          class="block mt-1 w-full"
                          type="email"
                          name="email"
                          :value="old('email', $request->email)"
                          required
                          autofocus
                          autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        {{-- New Password --}}
        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password"
                          class="block mt-1 w-full"
                          type="password"
                          name="password"
                          required
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        {{-- Confirm New Password --}}
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
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
            {{ __('Reset Password') }}
        </x-primary-button>
    </form>
</x-guest-layout>
