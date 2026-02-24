<x-app-layout>
    @include('components.page-seo-slots', ['page' => $page])

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <div class="text-center mb-10">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $page->getTitle() }}</h1>
            <p class="text-gray-500 text-sm sm:text-base">{{ __('faq.subtitle') }}</p>
        </div>

        <div class="space-y-3" x-data="{ open: 0 }">

            @php
                        $faqs = [
                [
                    'q' => __('faq.q1'),
                    'a' => __('faq.a1'),
                ],
                [
                    'q' => __('faq.q2'),
                    'a' => __('faq.a2'),
                ],
                [
                    'q' => __('faq.q3'),
                    'a' => __('faq.a3'),
                ],
                [
                    'q' => __('faq.q4'),
                    'a' => __('faq.a4'),
                ],
                [
                    'q' => __('faq.q5'),
                    'a' => __('faq.a5'),
                ],
                [
                    'q' => __('faq.q6'),
                    'a' => __('faq.a6'),
                ],
                [
                    'q' => __('faq.q7'),
                    'a' => __('faq.a7'),
                ],
                [
                    'q' => __('faq.q8'),
                    'a' => __('faq.a8'),
                ],
                [
                    'q' => __('faq.q9'),
                    'a' => __('faq.a9'),
                ],
                [
                    'q' => __('faq.q10'),
                    'a' => __('faq.a10'),
                ],
                [
                    'q' => __('faq.q11'),
                    'a' => __('faq.a11'),
                ],
                [
                    'q' => __('faq.q12'),
                    'a' => __('faq.a12'),
                ],
                [
                    'q' => __('faq.q13'),
                    'a' => __('faq.a13'),
                ],
                [
                    'q' => __('faq.q14'),
                    'a' => __('faq.a14'),
                ],
                [
                    'q' => __('faq.q15'),
                    'a' => __('faq.a15'),
                ],
                [
                    'q' => __('faq.q16'),
                    'a' => __('faq.a16'),
                ],
                [
                    'q' => __('faq.q17'),
                    'a' => __('faq.a17'),
                ],
                [
                    'q' => __('faq.q18'),
                    'a' => __('faq.a18'),
                ],
                [
                    'q' => __('faq.q19'),
                    'a' => __('faq.a19'),
                ],
                [
                    'q' => __('faq.q20'),
                    'a' => __('faq.a20'),
                ],
                [
                    'q' => __('faq.q21'),
                    'a' => __('faq.a21'),
                ],
            ];
            @endphp

            @foreach($faqs as $i => $faq)
                <div class="border border-gray-200 rounded-xl overflow-hidden" x-data="{ isOpen: {{ $i === 0 ? 'true' : 'false' }} }">
                    <button
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-right bg-white hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
                        @click="isOpen = !isOpen"
                        :aria-expanded="isOpen"
                    >
                        <span class="flex-1 text-sm sm:text-base font-semibold text-gray-900 text-right">{{ $faq['q'] }}</span>
                        <span class="flex-shrink-0 text-primary-500 text-xl font-bold transition-transform duration-300" :class="isOpen ? 'rotate-45' : ''">+</span>
                    </button>

                    <div
                        x-show="isOpen"
                        x-collapse
                        class="px-5 pb-5 text-gray-600 text-sm sm:text-base leading-relaxed space-y-2"
                    >
                        <div class="pt-2 border-t border-gray-100">
                            {!! $faq['a'] !!}
                        </div>
                    </div>
                </div>
            @endforeach

        </div>

    </div>
</x-app-layout>
