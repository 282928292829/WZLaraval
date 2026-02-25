<x-guest-layout>
    <div class="text-center">
        {{-- Success icon --}}
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        {{-- Heading --}}
        <h1 class="text-xl font-bold text-gray-900">
            {{ __('Account Created Successfully!') }}
        </h1>
        <p class="mt-2 text-sm text-gray-500">
            {{ __('Welcome to Wasetzon. You will be redirected to your orders in') }}
            <span id="countdown" class="font-semibold text-primary-600">5</span>
            {{ __('seconds.') }}
        </p>

        {{-- Progress bar --}}
        <div class="mt-5 h-2 w-full overflow-hidden rounded-full bg-gray-200">
            <div id="progress-bar"
                 class="h-2 rounded-full bg-primary-600 transition-all ease-linear"
                 style="width: 100%">
            </div>
        </div>

        {{-- Manual link --}}
        <p class="mt-4 text-sm text-gray-500">
            {{ __('Or') }}
            <a href="{{ route('orders.index') }}" class="font-medium text-primary-600 hover:text-primary-700 transition-colors">
                {{ __('go now') }}
            </a>
        </p>
    </div>

    <script>
        (function () {
            const total = 5;
            let remaining = total;
            const countdownEl = document.getElementById('countdown');
            const progressEl  = document.getElementById('progress-bar');

            const interval = setInterval(function () {
                remaining -= 1;
                countdownEl.textContent = remaining;
                progressEl.style.width = ((remaining / total) * 100) + '%';

                if (remaining <= 0) {
                    clearInterval(interval);
                    window.location.href = '{{ route('orders.index') }}';
                }
            }, 1000);
        })();
    </script>
</x-guest-layout>
