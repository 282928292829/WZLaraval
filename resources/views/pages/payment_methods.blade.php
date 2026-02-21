<x-app-layout>
    <x-slot name="title">{{ app()->getLocale() === 'ar' ? $page->seo_title_ar : $page->seo_title_en }}</x-slot>
    <x-slot name="description">{{ app()->getLocale() === 'ar' ? $page->seo_description_ar : $page->seo_description_en }}</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border-t-4 border-primary-500 p-6 mb-6 text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ __('payment.title') }}</h1>
            <p class="text-gray-500">{{ __('payment.subtitle') }}</p>
        </div>

        {{-- Alert --}}
        <div class="bg-amber-50 border border-amber-300 rounded-xl p-5 mb-6 flex gap-4" x-data="{ showForm: false }">
            <div class="text-2xl flex-shrink-0">⚠️</div>
            <div class="flex-1">
                <h3 class="font-bold text-amber-800 mb-2">{{ __('payment.important_alert') }}</h3>
                <p class="text-amber-800 text-sm leading-relaxed">{{ __('payment.alert_body') }}</p>
                <p class="text-amber-800 text-sm font-semibold mt-3 mb-2">{{ __('payment.alert_form_hint') }}</p>
                <button
                    @click="showForm = !showForm"
                    class="mt-1 px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition"
                >
                    📝 {{ __('payment.notify_payment') }}
                </button>

                {{-- Payment Notification Form --}}
                <div x-show="showForm" x-collapse class="mt-4">
                    <livewire:payment-notification-form />
                </div>
            </div>
        </div>

        {{-- Bank Cards --}}
        @php
        $banks = [
            [
                'name'    => '{{ __('payment.alrajhi') }}',
                'account' => '624608010055610',
                'iban'    => 'SA4180000624608010055610',
            ],
            [
                'name'    => '{{ __('payment.alahli') }}',
                'account' => '26561106000110',
                'iban'    => 'SA9710000026561106000110',
            ],
            [
                'name'    => '{{ __('payment.albilad') }}',
                'account' => '436117332070002',
                'iban'    => 'SA9315000436117332070002',
            ],
            [
                'name'    => '{{ __('payment.alinma') }}',
                'account' => '68222222010000',
                'iban'    => 'SA8905000068222222010000',
            ],
            [
                'name'    => '{{ __('payment.sab') }}',
                'account' => '611065905001',
                'iban'    => 'SA8345000000611065905001',
            ],
            [
                'name'    => '{{ __('payment.saib') }}',
                'account' => '0128605051001',
                'iban'    => 'SA4465000000128605051001',
            ],
            [
                'name'    => '{{ __('payment.riyad') }}',
                'account' => '00000000000000',
                'iban'    => 'SA0000000000000000000000',
            ],
        ];
        @endphp

        <div class="space-y-4 mb-6" x-data="copyHelper()">
            @foreach($banks as $bank)
                <div class="bg-white border border-gray-200 border-r-4 border-r-primary-500 rounded-xl p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $bank['name'] }}</h2>

                    @if(empty($bank['account']) || empty($bank['iban']))
                        {{-- Placeholder: account details not yet configured --}}
                        <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg px-4 py-5 text-center">
                            <p class="text-gray-500 text-sm mb-3">{{ __('payment.account_details_soon') }}</p>
                            <a href="https://wa.me/00966556063500" class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-lg hover:bg-green-600 transition" target="_blank" rel="noopener">
                                📱 {{ __('payment.contact_whatsapp') }}
                            </a>
                        </div>
                    @else
                        <div class="space-y-3">
                            {{-- Beneficiary --}}
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 pb-3 border-b border-gray-100">
                                <span class="text-sm font-semibold text-gray-500 sm:w-40 flex-shrink-0">{{ __('payment.beneficiary_name') }}</span>
                                <div class="flex items-center gap-3 flex-1">
                                    <span class="font-semibold text-gray-900 text-sm flex-1">{{ __('payment.company_name') }}</span>
                                    <button
                                        @click="copy('{{ __('payment.company_name') }}', $el)"
                                        class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex-shrink-0"
                                    >{{ __('payment.copy') }}</button>
                                </div>
                            </div>
                            {{-- Account --}}
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 pb-3 border-b border-gray-100">
                                <span class="text-sm font-semibold text-gray-500 sm:w-40 flex-shrink-0">{{ __('payment.account_number') }}</span>
                                <div class="flex items-center gap-3 flex-1">
                                    <span class="font-mono font-bold text-gray-900 text-sm flex-1 ltr:text-left rtl:text-right" dir="ltr">{{ $bank['account'] }}</span>
                                    <button
                                        @click="copy('{{ $bank['account'] }}', $el)"
                                        class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex-shrink-0"
                                    >{{ __('payment.copy') }}</button>
                                </div>
                            </div>
                            {{-- IBAN --}}
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                <span class="text-sm font-semibold text-gray-500 sm:w-40 flex-shrink-0">{{ __('payment.iban') }}</span>
                                <div class="flex items-center gap-3 flex-1">
                                    <span class="font-mono font-bold text-gray-900 text-sm flex-1 ltr:text-left rtl:text-right break-all" dir="ltr">{{ $bank['iban'] }}</span>
                                    <button
                                        @click="copy('{{ $bank['iban'] }}', $el)"
                                        class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex-shrink-0"
                                    >{{ __('payment.copy') }}</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- International customers --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-3">{{ __('payment.intl_options_title') }}</h3>
            <p class="text-gray-700 text-sm mb-2">💳 {{ __('payment.intl_credit_card') }}</p>
            <p class="text-gray-700 text-sm mb-3">💰 {{ __('payment.intl_paypal') }}</p>
            <p class="text-gray-500 text-xs leading-relaxed">{{ __('payment.intl_note') }}</p>
        </div>

        {{-- Steps --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-5 pb-3 border-b border-gray-100">{{ __('payment.steps_title') }}</h2>
            <ol class="space-y-4">
                @foreach([
                    __('payment.step_1'),
                    __('payment.step_2'),
                    __('payment.step_3'),
                    __('payment.step_4'),
                    __('payment.step_5'),
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
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('payment.need_help') }}</h3>
            <p class="text-gray-500 text-sm mb-5">{{ __('payment.team_ready') }}</p>
            <div class="flex gap-3 justify-center flex-wrap">
                <a href="https://wa.me/00966556063500" class="px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition">
                    📱 {{ __('payment.whatsapp') }}
                </a>
                <a href="mailto:info@wasetzon.com" class="px-5 py-2.5 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition">
                    📧 {{ __('payment.email') }}
                </a>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    function copyHelper() {
        return {
            copy(text, btn) {
                const showCopied = () => {
                    const orig = btn.textContent;
                    btn.textContent = '✓ ' . __('payment.copied');
                    btn.classList.add('bg-green-600');
                    btn.classList.remove('bg-primary-600');
                    setTimeout(() => {
                        btn.textContent = orig;
                        btn.classList.remove('bg-green-600');
                        btn.classList.add('bg-primary-600');
                    }, 2000);
                };

                const fallbackCopy = () => {
                    try {
                        const el = document.createElement('textarea');
                        el.value = text;
                        el.style.position = 'fixed';
                        el.style.top = '-9999px';
                        el.style.opacity = '0';
                        document.body.appendChild(el);
                        el.focus();
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        showCopied();
                    } catch (e) {
                        prompt(__('payment.copy_text'), text);
                    }
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(showCopied).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }
            }
        }
    }
    </script>
    @endpush

</x-app-layout>
