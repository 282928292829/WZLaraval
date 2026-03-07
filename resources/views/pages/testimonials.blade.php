<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])

    @php
        $testimonials = \App\Models\Testimonial::query()
            ->published()
            ->ordered()
            ->get();
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <div class="text-center mb-10">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $page->getTitle() }}</h1>
            <p class="text-gray-500 text-sm sm:text-base">{{ __('testimonials.subtitle') }}</p>
        </div>

        @if ($testimonials->isEmpty())
            <div class="text-center py-16 bg-gray-50 rounded-2xl border border-gray-100">
                <p class="text-gray-500">{{ __('No testimonials yet.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6"
                 x-data="{ open: null }">
                @foreach ($testimonials as $i => $t)
                    <div class="group relative">
                        <button type="button"
                                @click="open = open === {{ $i }} ? null : {{ $i }}"
                                class="block w-full aspect-square rounded-xl overflow-hidden border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            @if ($t->image_path)
                                <img src="{{ $t->getImageUrl() }}"
                                     alt="{{ $t->getName() ?: __('testimonials.client_review_alt') }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                    </svg>
                                </div>
                            @endif
                        </button>
                        @if ($t->getName() || $t->getQuote())
                            <div x-show="open === {{ $i }}"
                                 x-collapse
                                 x-cloak
                                 class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/70 to-transparent rounded-b-xl">
                                @if ($t->getName())
                                    <p class="text-white font-semibold text-sm">{{ $t->getName() }}</p>
                                @endif
                                @if ($t->getQuote())
                                    <p class="text-white/90 text-xs mt-0.5 line-clamp-2">{{ $t->getQuote() }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Full-screen modal for selected testimonial --}}
            <template x-teleport="body">
                <div x-show="open !== null"
                     x-cloak
                     @click="open = null"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div @click.stop
                         class="relative max-w-lg w-full bg-white rounded-2xl shadow-xl overflow-hidden"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        @foreach ($testimonials as $i => $t)
                            <div x-show="open === {{ $i }}"
                                 class="p-6"
                                 x-cloak>
                                @if ($t->image_path)
                                    <img src="{{ $t->getImageUrl() }}"
                                         alt="{{ $t->getName() ?: __('testimonials.client_review_alt') }}"
                                         class="w-full aspect-square object-cover rounded-xl mb-4">
                                @endif
                                @if ($t->getName())
                                    <p class="font-semibold text-gray-900">{{ $t->getName() }}</p>
                                @endif
                                @if ($t->getQuote())
                                    <p class="text-gray-600 text-sm mt-2 leading-relaxed">{{ $t->getQuote() }}</p>
                                @endif
                            </div>
                        @endforeach
                        <button type="button"
                                @click="open = null"
                                class="absolute top-4 end-4 p-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors"
                                aria-label="{{ __('testimonials.close') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        @endif

        {{-- CTA --}}
        <section class="mt-16 text-center py-12 bg-orange-50 rounded-2xl border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-2">{{ __('testimonials.cta_title') }}</h2>
            <p class="text-gray-600 text-sm mb-6">{{ __('testimonials.cta_subtitle') }}</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('new-order') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-3 text-sm transition-colors">
                    {{ __('testimonials.start_order') }}
                </a>
                <a href="{{ route('pages.show', 'how-to-order') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-orange-200 text-orange-600 hover:bg-orange-50 font-medium px-6 py-3 text-sm transition-colors">
                    {{ __('testimonials.learn_how') }}
                </a>
            </div>
        </section>

    </div>
</x-app-layout>
