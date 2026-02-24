<x-app-layout>
    @include('components.page-seo-slots', ['page' => $page])
    @php
        $body = app()->getLocale() === 'ar'
            ? ($page->body_ar ?: $page->body_en)
            : ($page->body_en ?: $page->body_ar);
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Page header --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-100 mb-4">
                <svg class="w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $page->getTitle() }}</h1>
        </div>

        {{-- Content --}}
        <div class="prose prose-gray max-w-none prose-headings:font-semibold prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl">
            {!! $body !!}
        </div>

    </div>
</x-app-layout>
