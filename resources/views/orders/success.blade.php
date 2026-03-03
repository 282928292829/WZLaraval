<x-app-layout :minimal-footer="true">
    @php
        $orderUrl = route('orders.show', $order);
    @endphp
    <div class="min-h-dvh min-h-screen flex items-start justify-center bg-gradient-to-br from-orange-50 to-orange-100 p-4 pt-12">
        <div class="text-center max-w-[420px] w-full md:max-w-[560px]">
            {{-- Checkmark --}}
            <div class="w-14 h-14 md:w-[72px] md:h-[72px] md:mb-4 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 md:w-9 md:h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-800 mb-1.5 md:mb-2">
                {{ $title }}
            </h1>
            <div class="text-lg md:text-xl font-semibold text-orange-500 mb-2.5 md:mb-3.5">
                {{ $subtitle }}
            </div>
            <p class="text-slate-600 leading-relaxed text-sm md:text-base mb-3.5 md:mb-4 whitespace-pre-line">
                {{ $message }}
            </p>
            <a
                href="{{ $orderUrl }}"
                class="inline-block bg-orange-500 text-white font-semibold px-6 py-3 rounded-lg no-underline text-base md:text-lg md:px-7 md:py-3.5 mb-2.5 md:mb-3.5 hover:bg-orange-600 transition-colors"
            >
                {{ $goToOrder }}
            </a>
            <div class="text-slate-500 text-sm md:text-base">
                {{ $prefix }}<span id="wz-countdown-seconds">{{ $seconds }}</span>{{ $suffix }}
            </div>
        </div>
    </div>
    <script>
        (function() {
            const url = @json($orderUrl);
            const seconds = @json($seconds);
            if (seconds <= 0) {
                window.location.href = url;
                return;
            }
            function start() {
                const span = document.getElementById('wz-countdown-seconds');
                if (!span) return setTimeout(start, 50);
                let s = seconds;
                const t = setInterval(function() {
                    s--;
                    span.textContent = s;
                    if (s <= 0) { clearInterval(t); window.location.href = url; }
                }, 1000);
            }
            setTimeout(start, 0);
        })();
    </script>
</x-app-layout>
