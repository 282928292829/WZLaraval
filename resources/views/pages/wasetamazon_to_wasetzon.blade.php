<x-app-layout>
    @include('components.page-seo-slots', ['page' => $page])
    @php
        $body = app()->getLocale() === 'ar'
            ? ($page->body_ar ?: $page->body_en)
            : ($page->body_en ?: $page->body_ar);
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Migration notice banner --}}
        <div class="bg-primary-50 border border-primary-200 rounded-2xl px-6 py-5 mb-8 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-base font-bold text-primary-900 mb-1">{{ $page->getTitle() }}</h1>
                <p class="text-sm text-primary-700">{{ $page->getSeoDescription() }}</p>
            </div>
        </div>

        {{-- Content --}}
        <article class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-8 sm:px-8">
            <div class="prose prose-gray max-w-none prose-headings:font-semibold prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl">
                {!! $body !!}
            </div>
        </article>

    </div>
</x-app-layout>
