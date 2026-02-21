<x-app-layout>
    @php
        $seoTitle = app()->getLocale() === 'ar'
            ? ($page->seo_title_ar ?: $page->getTitle())
            : ($page->seo_title_en ?: $page->getTitle());
        $seoDesc = app()->getLocale() === 'ar'
            ? ($page->seo_description_ar ?? '')
            : ($page->seo_description_en ?? '');
        $body = app()->getLocale() === 'ar'
            ? ($page->body_ar ?: $page->body_en)
            : ($page->body_en ?: $page->body_ar);
    @endphp

    <x-slot name="title">{{ $seoTitle }}</x-slot>
    <x-slot name="description">{{ $seoDesc }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Page header --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-100 mb-4">
                <svg class="w-7 h-7 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $page->getTitle() }}</h1>
        </div>

        {{-- Policy content --}}
        <article class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-8 sm:px-8">
            <div class="prose prose-gray max-w-none prose-headings:font-semibold prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl">
                {!! $body !!}
            </div>
        </article>

    </div>
</x-app-layout>
