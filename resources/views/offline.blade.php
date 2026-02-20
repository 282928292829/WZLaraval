<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-[50vh] text-center px-4 py-12">
        <div class="text-6xl mb-6">🔌</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            @if(app()->getLocale() === 'ar')
                لا يوجد اتصال بالإنترنت
            @else
                You're Offline
            @endif
        </h1>
        <p class="text-gray-500 mb-8 max-w-sm">
            @if(app()->getLocale() === 'ar')
                تحقق من اتصالك بالإنترنت وحاول مرة أخرى.
            @else
                Check your internet connection and try again.
            @endif
        </p>
        <a href="/"
           class="inline-flex items-center gap-2 bg-orange-500 text-white px-5 py-2.5 rounded-xl font-medium hover:bg-orange-600 transition-colors">
            @if(app()->getLocale() === 'ar')
                العودة للرئيسية
            @else
                Back to Home
            @endif
        </a>
    </div>
</x-guest-layout>
