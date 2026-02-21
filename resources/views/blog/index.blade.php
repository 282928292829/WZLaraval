<x-app-layout>
    <x-slot name="title">{{ __('blog.blog') }}</x-slot>
    <x-slot name="description">{{ __('blog.blog_description') }}</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Page heading --}}
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ __('blog.blog') }}</h1>
                @if ($search !== '')
                    <p class="mt-1 text-sm text-gray-500">
                        {{ __('blog.no_results_for') }} "<span class="font-medium text-gray-700">{{ $search }}</span>"
                        @if ($posts->total() > 0)
                            — {{ $posts->total() }} {{ __('results') }}
                        @endif
                        &mdash; <a href="{{ route('blog.index') }}" class="text-primary-600 hover:underline">{{ __('blog.view_all') }}</a>
                    </p>
                @elseif ($category)
                    <p class="mt-1 text-sm text-gray-500">
                        {{ __('blog.posts_in_category') }}:
                        <span class="font-medium text-gray-700">{{ $category->getName() }}</span>
                        — <a href="{{ route('blog.index') }}" class="text-primary-600 hover:underline">{{ __('blog.view_all') }}</a>
                    </p>
                @else
                    <p class="mt-1 text-sm text-gray-500">{{ __('blog.blog_description') }}</p>
                @endif
            </div>

            {{-- Search input --}}
            <form method="GET" action="{{ route('blog.index') }}" class="flex gap-2 w-full sm:w-72 shrink-0">
                @if ($category)
                    <input type="hidden" name="category" value="{{ $category->slug }}">
                @endif
                <div class="relative flex-1">
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="{{ __('blog.search_placeholder') }}"
                        aria-label="{{ __('blog.search_label') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent pe-10">
                    <button type="submit"
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-primary-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:grid lg:grid-cols-4 lg:gap-8">

            {{-- Main content --}}
            <div class="lg:col-span-3">

                @if ($posts->isEmpty())
                    <div class="text-center py-20 text-gray-400">
                        <svg class="mx-auto w-12 h-12 mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <p class="text-lg font-medium">{{ __('blog.no_posts') }}</p>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($posts as $post)
                            <article class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all overflow-hidden flex flex-col">

                                {{-- Featured image --}}
                                @if ($post->featured_image)
                                    <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden aspect-video bg-gray-100">
                                        <img src="{{ Storage::url($post->featured_image) }}"
                                             alt="{{ $post->getTitle() }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    </a>
                                @else
                                    <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-video bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                    </a>
                                @endif

                                <div class="p-5 flex flex-col flex-1">

                                    {{-- Category badge --}}
                                    @if ($post->category)
                                        <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}"
                                           class="self-start mb-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors">
                                            {{ $post->category->getName() }}
                                        </a>
                                    @endif

                                    {{-- Title --}}
                                    <h2 class="text-base font-semibold text-gray-900 leading-snug mb-2">
                                        <a href="{{ route('blog.show', $post->slug) }}"
                                           class="hover:text-primary-600 transition-colors line-clamp-2">
                                            {{ $post->getTitle() }}
                                        </a>
                                    </h2>

                                    {{-- Excerpt --}}
                                    @php $excerpt = $post->getExcerpt(); @endphp
                                    @if ($excerpt)
                                        <p class="text-sm text-gray-500 line-clamp-3 mb-4 flex-1">{{ $excerpt }}</p>
                                    @else
                                        <div class="flex-1"></div>
                                    @endif

                                    {{-- Footer meta --}}
                                    <div class="flex items-center justify-between pt-3 border-t border-gray-50 text-xs text-gray-400 mt-auto">
                                        <span>{{ $post->published_at?->diffForHumans() ?? $post->created_at->diffForHumans() }}</span>
                                        <a href="{{ route('blog.show', $post->slug) }}"
                                           class="font-medium text-primary-600 hover:text-primary-700 transition-colors flex items-center gap-1">
                                            {{ __('blog.read_more') }}
                                            @if (app()->getLocale() === 'ar')
                                                <svg class="w-3.5 h-3.5 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                            @endif
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($posts->hasPages())
                        <div class="mt-8">
                            {{ $posts->links() }}
                        </div>
                    @endif
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="hidden lg:block lg:col-span-1 space-y-6 mt-8 lg:mt-0">

                {{-- Search --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{{ __('blog.search_label') }}</h3>
                    <form method="GET" action="{{ route('blog.index') }}" class="flex gap-2">
                        @if ($category)
                            <input type="hidden" name="category" value="{{ $category->slug }}">
                        @endif
                        <div class="relative flex-1">
                            <input type="search" name="search" value="{{ $search }}"
                                placeholder="{{ __('blog.search_placeholder') }}"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400 pe-9">
                            <button type="submit" class="absolute inset-y-0 end-0 flex items-center pe-2.5 text-gray-400 hover:text-primary-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Categories --}}
                @if ($categories->isNotEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{{ __('blog.categories') }}</h3>
                        <ul class="space-y-1">
                            <li>
                                <a href="{{ route('blog.index') }}"
                                   class="flex items-center justify-between text-sm px-2.5 py-1.5 rounded-lg transition-colors {{ !$category ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                    {{ __('blog.all_posts') }}
                                </a>
                            </li>
                            @foreach ($categories as $cat)
                                <li>
                                    <a href="{{ route('blog.index', ['category' => $cat->slug]) }}"
                                       class="flex items-center justify-between text-sm px-2.5 py-1.5 rounded-lg transition-colors {{ $category?->id === $cat->id ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                                        <span>{{ $cat->getName() }}</span>
                                        <span class="text-xs text-gray-400">{{ $cat->posts->count() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </aside>

        </div>
    </div>
</x-app-layout>
