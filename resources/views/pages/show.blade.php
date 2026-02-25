<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])
    @php
        $body = app()->getLocale() === 'ar'
            ? ($page->body_ar ?: $page->body_en)
            : ($page->body_en ?: $page->body_ar);
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <article class="bg-white">
            <header class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-snug">
                    {{ $page->getTitle() }}
                </h1>
            </header>

            <div class="prose prose-gray max-w-none prose-headings:font-semibold prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl">
                {!! $body !!}
            </div>
        </article>

    </div>
</x-app-layout>
