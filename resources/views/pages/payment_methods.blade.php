<x-app-layout>
    <x-slot name="title">{{ app()->getLocale() === 'ar' ? $page->seo_title_ar : $page->seo_title_en }}</x-slot>
    <x-slot name="description">{{ app()->getLocale() === 'ar' ? $page->seo_description_ar : $page->seo_description_en }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border-t-4 border-primary-500 p-6 mb-6 text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">طرق الدفع المتاحة</h1>
            <p class="text-gray-500">اختر الطريقة الأنسب لك لإتمام عملية الدفع</p>
        </div>

        {{-- Alert --}}
        <div class="bg-amber-50 border border-amber-300 rounded-xl p-5 mb-6 flex gap-4" x-data="{ showForm: false }">
            <div class="text-2xl flex-shrink-0">⚠️</div>
            <div class="flex-1">
                <h3 class="font-bold text-amber-800 mb-2">تنبيه هام</h3>
                <p class="text-amber-800 text-sm leading-relaxed">يرجى اخبارنا المبلغ المدفوع وعلى اي بنك عن طريق الرد على طلبك أو ارسال عبر الواتساب أو البريد الإلكتروني بعد إتمام عملية التحويل لتأكيد الدفع وبدء تنفيذ طلبك. (لايلزم ارسال ايصال إلا في حالة طلبه من الفريق)</p>
                <p class="text-amber-800 text-sm font-semibold mt-3 mb-2">كما يمكن اخبارنا عن طريق هذا النموذج البسيط بضغطة زر:</p>
                <button
                    @click="showForm = !showForm"
                    class="mt-1 px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition"
                >
                    📝 أبلغنا بالدفع
                </button>

                {{-- Payment Notification Form --}}
                <div x-show="showForm" x-collapse class="mt-4">
                    @auth
                        <livewire:payment-notification-form />
                    @else
                        <div class="bg-white border border-gray-200 rounded-xl p-5 mt-3">
                            <p class="text-gray-700 text-sm mb-4">يجب تسجيل الدخول أولاً لإرسال إبلاغ عن الدفع عبر الموقع، أو تواصل معنا مباشرة:</p>
                            <div class="flex gap-3 flex-wrap">
                                <a href="{{ route('login') }}" class="px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition">تسجيل الدخول</a>
                                <a href="https://wa.me/00966556063500" class="px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-lg hover:bg-green-600 transition" target="_blank" rel="noopener">واتساب</a>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Bank Cards --}}
        @php
        $banks = [
            [
                'name'    => 'مصرف الراجحي',
                'account' => '624608010055610',
                'iban'    => 'SA4180000624608010055610',
            ],
            [
                'name'    => 'البنك الأهلي التجاري',
                'account' => '26561106000110',
                'iban'    => 'SA9710000026561106000110',
            ],
            [
                'name'    => 'بنك البلاد',
                'account' => '436117332070002',
                'iban'    => 'SA9315000436117332070002',
            ],
            [
                'name'    => 'بنك الإنماء',
                'account' => '68222222010000',
                'iban'    => 'SA8905000068222222010000',
            ],
            [
                'name'    => 'البنك السعودي الأول',
                'account' => '611065905001',
                'iban'    => 'SA8345000000611065905001',
            ],
            [
                'name'    => 'البنك السعودي للإستثمار',
                'account' => '0128605051001',
                'iban'    => 'SA4465000000128605051001',
            ],
        ];
        @endphp

        <div class="space-y-4 mb-6" x-data="copyHelper()">
            @foreach($banks as $bank)
                <div class="bg-white border border-gray-200 border-r-4 border-r-primary-500 rounded-xl p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $bank['name'] }}</h2>
                    <div class="space-y-3">
                        {{-- Beneficiary --}}
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 pb-3 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-500 sm:w-40 flex-shrink-0">اسم المستفيد</span>
                            <div class="flex items-center gap-3 flex-1">
                                <span class="font-semibold text-gray-900 text-sm flex-1">مؤسسة جسور الاستيراد للتجارة</span>
                                <button
                                    @click="copy('مؤسسة جسور الاستيراد للتجارة', $el)"
                                    class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex-shrink-0"
                                >نسخ</button>
                            </div>
                        </div>
                        {{-- Account --}}
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 pb-3 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-500 sm:w-40 flex-shrink-0">رقم الحساب</span>
                            <div class="flex items-center gap-3 flex-1">
                                <span class="font-mono font-bold text-gray-900 text-sm flex-1 ltr:text-left rtl:text-right" dir="ltr">{{ $bank['account'] }}</span>
                                <button
                                    @click="copy('{{ $bank['account'] }}', $el)"
                                    class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex-shrink-0"
                                >نسخ</button>
                            </div>
                        </div>
                        {{-- IBAN --}}
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <span class="text-sm font-semibold text-gray-500 sm:w-40 flex-shrink-0">رقم الايبان (IBAN)</span>
                            <div class="flex items-center gap-3 flex-1">
                                <span class="font-mono font-bold text-gray-900 text-sm flex-1 ltr:text-left rtl:text-right break-all" dir="ltr">{{ $bank['iban'] }}</span>
                                <button
                                    @click="copy('{{ $bank['iban'] }}', $el)"
                                    class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex-shrink-0"
                                >نسخ</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- International customers --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-3">خيارات الدفع للعملاء خارج السعودية</h3>
            <p class="text-gray-700 text-sm mb-2">💳 خيار الدفع بالبطاقات الائتمانية (Credit Card) متاحة لجميع عملائنا.</p>
            <p class="text-gray-700 text-sm mb-3">💰 خدمة الدفع بالبايبال متاحة لعملائنا المقيمين في أوروبا وأستراليا وأمريكا الشمالية.</p>
            <p class="text-gray-500 text-xs leading-relaxed">نحاول دائما تأمين كافة الطرق المتاحة للدفع لعملائنا الكرام. يرجى مراجعة هذه الصفحة بشكل دوري للاطلاع على كافة خيارات الدفع المتاحة.</p>
        </div>

        {{-- Steps --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-5 pb-3 border-b border-gray-100">خطوات إتمام الدفع</h2>
            <ol class="space-y-4">
                @foreach([
                    'اختر طريقة الدفع المناسبة لك من الخيارات أعلاه',
                    'قم بتحويل المبلغ المطلوب حسب الفاتورة المرسلة إليك',
                    'احتفظ بإيصال الدفع أو لقطة شاشة للعملية',
                    'أرسل إيصال الدفع إلى فريق الدعم عبر الواتساب أو البريد الإلكتروني',
                    'انتظر تأكيد استلام الدفع وبدء تنفيذ طلبك (عادة خلال ساعات قليلة)',
                ] as $step => $text)
                    <li class="flex items-start gap-4">
                        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">{{ $step + 1 }}</span>
                        <span class="text-gray-600 text-sm leading-relaxed pt-1">{{ $text }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Contact --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm text-center">
            <div class="text-4xl mb-3">📞</div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">هل تحتاج مساعدة؟</h3>
            <p class="text-gray-500 text-sm mb-5">فريقنا جاهز لمساعدتك في أي استفسار</p>
            <div class="flex gap-3 justify-center flex-wrap">
                <a href="https://wa.me/00966556063500" class="px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition">
                    📱 واتساب
                </a>
                <a href="mailto:info@wasetzon.com" class="px-5 py-2.5 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition">
                    📧 بريد إلكتروني
                </a>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    function copyHelper() {
        return {
            copy(text, btn) {
                navigator.clipboard.writeText(text).then(() => {
                    const orig = btn.textContent;
                    btn.textContent = '✓ تم';
                    btn.classList.add('bg-green-600');
                    btn.classList.remove('bg-primary-600');
                    setTimeout(() => {
                        btn.textContent = orig;
                        btn.classList.remove('bg-green-600');
                        btn.classList.add('bg-primary-600');
                    }, 2000);
                }).catch(() => {
                    prompt('انسخ النص:', text);
                });
            }
        }
    }
    </script>
    @endpush

</x-app-layout>
