<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Header --}}
        <div class="text-center mb-10">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">آراء العملاء</h1>
            <p class="text-gray-500">شاهد تجارب عملائنا الكرام معنا</p>
        </div>

        {{-- Testimonials Grid + Lightbox --}}
        <div x-data="testimonials()" @keydown.escape.window="close()">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-12">
                @php
                $images = [
                    asset('images/testimonials/12.png'),
                    asset('images/testimonials/11.png'),
                    asset('images/testimonials/10.png'),
                    asset('images/testimonials/9.png'),
                    asset('images/testimonials/8.png'),
                    asset('images/testimonials/7.png'),
                    asset('images/testimonials/6.png'),
                    asset('images/testimonials/5-1.png'),
                    asset('images/testimonials/4.png'),
                    asset('images/testimonials/3.png'),
                    asset('images/testimonials/2.png'),
                    asset('images/testimonials/1.png'),
                ];
                @endphp

                @foreach($images as $i => $img)
                    <div
                        class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 group cursor-pointer hover:-translate-y-1 hover:shadow-md transition-all duration-300"
                        @click="open('{{ $img }}')"
                        style="animation: fadeInUp 0.6s ease {{ $i * 0.05 }}s both"
                    >
                        <div class="relative aspect-square overflow-hidden bg-gray-100">
                            <img
                                src="{{ $img }}"
                                alt="رأي العميل {{ $i + 1 }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.35-4.35"/>
                                        <line x1="11" y1="8" x2="11" y2="14"/>
                                        <line x1="8" y1="11" x2="14" y2="11"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 text-center text-xs font-semibold text-gray-500">
                            رأي العميل #{{ $i + 1 }}
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Lightbox --}}
            <div
                x-show="active"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
                @click.self="close()"
            >
                <button
                    @click="close()"
                    class="absolute top-4 end-4 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition"
                    aria-label="إغلاق"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
                <div
                    x-show="active"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="relative max-w-2xl w-full"
                >
                    <img :src="currentImage" alt="رأي العميل" class="w-full h-auto max-h-[85vh] object-contain rounded-xl shadow-2xl" />
                </div>
            </div>

        </div>

        {{-- CTA --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center max-w-xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">هل أنت مستعد لتجربة خدماتنا؟</h2>
            <p class="text-gray-500 mb-6">انضم إلى آلاف العملاء الراضين عن خدماتنا</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('new-order') }}" class="inline-flex items-center justify-center px-6 py-3 bg-primary-600 text-white font-bold rounded-xl hover:bg-primary-700 transition shadow-sm">
                    ابدأ طلبك الآن
                </a>
                <a href="{{ route('pages.show', 'how-to-order') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary-600 font-semibold rounded-xl border-2 border-primary-500 hover:bg-primary-50 transition">
                    تعرف على طريقة الطلب
                </a>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    function testimonials() {
        return {
            active: false,
            currentImage: '',
            open(img) {
                this.currentImage = img;
                this.active = true;
                document.body.style.overflow = 'hidden';
            },
            close() {
                this.active = false;
                document.body.style.overflow = '';
                setTimeout(() => { this.currentImage = ''; }, 200);
            }
        }
    }
    </script>
    <style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    </style>
    @endpush

</x-app-layout>
