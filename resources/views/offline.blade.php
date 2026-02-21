<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-[50vh] text-center px-4 py-12">
        <div class="text-6xl mb-6">🔌</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            {{ __("You're Offline") }}
        </h1>
        <p class="text-gray-500 mb-8 max-w-sm">
            {{ __('Check your internet connection and try again.') }}
        </p>
        <a href="/"
           class="inline-flex items-center gap-2 bg-orange-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-orange-600 transition-colors">
            {{ __('Back to Home') }}
        </a>
    </div>
</x-guest-layout>
