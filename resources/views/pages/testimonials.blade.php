<x-app-layout :minimal-footer="true">
    <x-slot name="title">{{ $title ?? __('testimonials.title') }}</x-slot>
    <x-slot name="description">{{ $description ?? __('testimonials.subtitle') }}</x-slot>
    @if(isset($canonicalUrl))
    <x-slot name="canonicalUrl">{{ $canonicalUrl }}</x-slot>
    @endif

    <div class="testimonials-page min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            {{-- Header --}}
            <header class="text-center mb-12 sm:mb-16">
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight mb-3">
                    {{ __('testimonials.title') }}
                </h1>
                <p class="text-gray-500 text-base sm:text-lg max-w-xl mx-auto">
                    {{ __('testimonials.subtitle') }}
                </p>
            </header>

            @if ($testimonials->isEmpty())
                <div class="text-center py-20 rounded-2xl bg-white border border-gray-100 shadow-sm">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                        </svg>
                    </div>
                    <p class="text-gray-500">{{ __('testimonials.no_testimonials') }}</p>
                </div>
            @else
                {{-- Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6" id="testimonials-grid">
                    @foreach ($testimonials as $i => $t)
                        <article class="group">
                            <button type="button"
                                    class="testimonial-card block w-full aspect-square rounded-2xl overflow-hidden bg-gray-100 border border-gray-200/80 shadow-sm hover:shadow-lg hover:border-gray-300 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                    data-index="{{ $i }}">
                                @if ($t->image_path)
                                    <img src="{{ $t->getImageUrl() }}"
                                         alt="{{ $t->getName() ?: __('testimonials.client_review_alt') }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy"
                                         draggable="false">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                        </svg>
                                    </div>
                                @endif
                            </button>
                        </article>
                    @endforeach
                </div>

                {{-- Modal (vanilla JS) --}}
                <div id="testimonial-modal"
                     class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                     role="dialog"
                     aria-modal="true"
                     aria-label="{{ __('testimonials.client_review_alt') }}">
                    <div class="absolute inset-0" id="modal-backdrop"></div>
                    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden"
                         id="modal-content">
                        <button type="button"
                                id="modal-close"
                                class="absolute top-4 end-4 z-10 p-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 transition-colors"
                                aria-label="{{ __('testimonials.close') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="flex overflow-hidden transition-transform duration-300 ease-out" id="modal-slider"
                             style="width: {{ $testimonials->count() * 100 }}%">
                            @foreach ($testimonials as $i => $t)
                                <div class="flex-shrink-0 p-6 sm:p-8" data-index="{{ $i }}"
                                     style="width: {{ 100 / $testimonials->count() }}%">
                                    @if ($t->image_path)
                                        <img src="{{ $t->getImageUrl() }}"
                                             alt="{{ $t->getName() ?: __('testimonials.client_review_alt') }}"
                                             class="w-full aspect-square object-cover rounded-xl mb-5">
                                    @endif
                                    @if ($t->getName())
                                        <p class="font-semibold text-gray-900 text-lg">{{ $t->getName() }}</p>
                                    @endif
                                    @if ($t->getQuote())
                                        <p class="text-gray-600 mt-2 leading-relaxed">{{ $t->getQuote() }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($testimonials->count() > 1)
                            <div class="absolute inset-y-0 start-0 flex items-center ps-2 sm:ps-4">
                                <button type="button"
                                        id="modal-prev"
                                        class="p-2.5 rounded-full bg-white/95 hover:bg-white text-gray-800 shadow-lg transition-colors"
                                        aria-label="{{ __('Previous') }}">
                                    <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="absolute inset-y-0 end-0 flex items-center justify-end pe-2 sm:pe-4">
                                <button type="button"
                                        id="modal-next"
                                        class="p-2.5 rounded-full bg-white/95 hover:bg-white text-gray-800 shadow-lg transition-colors"
                                        aria-label="{{ __('Next') }}">
                                    <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- CTA --}}
            <section class="mt-16 sm:mt-20 text-center py-12 px-6 rounded-2xl bg-gradient-to-br from-primary-50 to-orange-50 border border-orange-100/80">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">{{ __('testimonials.cta_title') }}</h2>
                <p class="text-gray-600 mb-6 max-w-md mx-auto">{{ __('testimonials.cta_subtitle') }}</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('new-order') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 text-sm transition-colors shadow-md hover:shadow-lg">
                        {{ __('testimonials.start_order') }}
                    </a>
                    <a href="{{ route('pages.show', 'how-to-order') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-primary-200 text-primary-600 hover:bg-primary-50 font-medium px-6 py-3 text-sm transition-colors">
                        {{ __('testimonials.learn_how') }}
                    </a>
                </div>
            </section>
        </div>
    </div>

    @if (!$testimonials->isEmpty())
    <script>
        (function() {
            const total = {{ $testimonials->count() }};
            const modal = document.getElementById('testimonial-modal');
            const slider = document.getElementById('modal-slider');
            const backdrop = document.getElementById('modal-backdrop');
            const closeBtn = document.getElementById('modal-close');
            const prevBtn = document.getElementById('modal-prev');
            const nextBtn = document.getElementById('modal-next');
            const cards = document.querySelectorAll('.testimonial-card');

            let currentIndex = 0;
            let touchStartX = 0;

            function openModal(index) {
                currentIndex = index;
                updateSlider();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                closeBtn.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }

            function updateSlider() {
                if (!slider) return;
                const slideWidth = 100 / total;
                const offset = (document.dir === 'rtl' ? 1 : -1) * currentIndex * slideWidth;
                slider.style.transform = `translateX(${offset}%)`;
            }

            function goPrev() {
                if (total <= 1) return;
                currentIndex = currentIndex > 0 ? currentIndex - 1 : total - 1;
                updateSlider();
            }

            function goNext() {
                if (total <= 1) return;
                currentIndex = currentIndex < total - 1 ? currentIndex + 1 : 0;
                updateSlider();
            }

            cards.forEach(function(card) {
                card.addEventListener('click', function() {
                    openModal(parseInt(card.dataset.index, 10));
                });
            });

            if (backdrop) backdrop.addEventListener('click', closeModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (prevBtn) prevBtn.addEventListener('click', function(e) { e.stopPropagation(); goPrev(); });
            if (nextBtn) nextBtn.addEventListener('click', function(e) { e.stopPropagation(); goNext(); });

            document.addEventListener('keydown', function(e) {
                if (!modal || modal.classList.contains('hidden')) return;
                if (e.key === 'Escape') { closeModal(); e.preventDefault(); }
                else if (e.key === 'ArrowLeft') { goPrev(); e.preventDefault(); }
                else if (e.key === 'ArrowRight') { goNext(); e.preventDefault(); }
            });

            if (slider) {
                slider.addEventListener('touchstart', function(e) { touchStartX = e.touches[0].clientX; }, { passive: true });
                slider.addEventListener('touchend', function(e) {
                    const delta = e.changedTouches[0].clientX - touchStartX;
                    if (Math.abs(delta) > 50) delta > 0 ? goPrev() : goNext();
                });
            }
        })();
    </script>
    @endif
</x-app-layout>
