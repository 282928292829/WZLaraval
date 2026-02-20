<x-guest-layout>
    {{-- Heading --}}
    <div class="mb-5">
        <div class="w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">
            @if (app()->getLocale() === 'ar')
                تحقق من بريدك الإلكتروني
            @else
                Check your email
            @endif
        </h1>
        <p class="mt-2 text-sm text-gray-500 leading-relaxed">
            {{ __("Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.") }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-100 rounded-lg">
            <p class="text-sm text-green-700 font-medium">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full justify-center">
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full text-center text-sm text-gray-500 hover:text-gray-700 py-2 transition-colors">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
