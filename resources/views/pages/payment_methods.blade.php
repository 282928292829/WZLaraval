<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])

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
        $banks = \App\Models\Setting::get('payment_banks', []) ?: [];
        $paymentCompanyName = trim((string) \App\Models\Setting::get('payment_company_name', '')) ?: config('app.name');
        $whatsappForContact = preg_replace('/\D/', '', \App\Models\Setting::get('whatsapp', '966500000000'));
        $contactEmail = trim((string) \App\Models\Setting::get('contact_email', '')) ?: config('mail.from.address', '');
        @endphp

        <div class="space-y-4 mb-6" x-data="copyHelper()">
            @forelse($banks as $bank)
                <div class="bg-white border border-gray-200 border-r-4 border-r-primary-500 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        @php
                            $bankLogoUrl = !empty($bank['logo']) ? (str_starts_with($bank['logo'], 'http') ? $bank['logo'] : asset(ltrim($bank['logo'], '/'))) : '';
                        @endphp
                        @if($bankLogoUrl)
                            <img src="{{ $bankLogoUrl }}" alt="{{ $bank['name'] }}" class="h-8 w-auto object-contain flex-shrink-0">
                        @endif
                        <h2 class="text-lg font-bold text-gray-900">{{ $bank['name'] }}</h2>
                    </div>

                    @php
                        $beneficiaryName = trim((string) ($bank['beneficiary'] ?? '')) ?: $paymentCompanyName;
                    @endphp
                    @if(empty($bank['account']) || empty($bank['iban']))
                        {{-- Placeholder: account details not yet configured --}}
                        <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg px-4 py-5 text-center">
                            <p class="text-gray-500 text-sm mb-3">{{ __('payment.account_details_soon') }}</p>
                            @if($whatsappForContact)
                            <a href="https://wa.me/{{ $whatsappForContact }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-lg hover:bg-green-600 transition" target="_blank" rel="noopener">
                                📱 {{ __('payment.contact_whatsapp') }}
                            </a>
                            @endif
                        </div>
                    @else
                        <div class="space-y-3">
                            {{-- Beneficiary --}}
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 pb-3 border-b border-gray-100">
                                <span class="text-sm font-semibold text-gray-500 sm:w-40 flex-shrink-0">{{ __('payment.beneficiary_name') }}</span>
                                <div class="flex items-center gap-3 flex-1">
                                    <span class="font-semibold text-gray-900 text-sm flex-1">{{ $beneficiaryName }}</span>
                                    <button
                                        @click="copy(@js($beneficiaryName), $el)"
                                        class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-150 ease-out flex-shrink-0"
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
                                        class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-150 ease-out flex-shrink-0"
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
                                        class="px-3 py-1 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-150 ease-out flex-shrink-0"
                                    >{{ __('payment.copy') }}</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl px-6 py-10 text-center">
                    <p class="text-gray-500">{{ __('payment.account_details_soon') }}</p>
                    <p class="text-gray-400 text-sm mt-2">{{ __('payment.configure_banks_hint') }}</p>
                </div>
            @endforelse
        </div>

        {{-- International customers --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-3">{{ __('payment.intl_options_title') }}</h3>
            <p class="text-gray-700 text-sm mb-2">💳 {{ __('payment.intl_credit_card') }}</p>
            <p class="text-gray-700 text-sm mb-2">💰 {{ __('payment.intl_paypal') }}</p>
            <p class="text-gray-700 text-sm mb-3">🏦 {{ __('payment.intl_bank_transfer') }}</p>
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
                @if($whatsappForContact)
                <a href="https://wa.me/{{ $whatsappForContact }}" class="px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition">
                    📱 {{ __('payment.whatsapp') }}
                </a>
                @endif
                @if($contactEmail)
                <a href="mailto:{{ $contactEmail }}" class="px-5 py-2.5 bg-gray-800 text-white text-sm font-semibold rounded-lg hover:bg-gray-900 transition">
                    📧 {{ __('payment.email') }}
                </a>
                @endif
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    function copyHelper() {
        return {
            copy(text, btn) {
                const orig = btn.textContent;
                const copiedText = '✓ {{ __("payment.copied") }}';
                // Instant feedback — update button first, before any async work
                btn.textContent = copiedText;
                btn.classList.remove('bg-primary-600');
                btn.classList.add('bg-green-600');
                btn.style.setProperty('background-color', 'rgb(22, 163, 74)', 'important');

                const revert = () => {
                    btn.textContent = orig;
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-primary-600');
                    btn.style.removeProperty('background-color');
                };
                setTimeout(revert, 500);
                const showCopied = () => {
                    btn.textContent = copiedText;
                    btn.classList.remove('bg-primary-600');
                    btn.classList.add('bg-green-600');
                    btn.style.setProperty('background-color', 'rgb(22, 163, 74)', 'important');
                    setTimeout(revert, 500);
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
                        revert();
                        prompt('{{ __("payment.copy_text") }}', text);
                    }
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).catch(() => { revert(); fallbackCopy(); });
                } else {
                    fallbackCopy();
                }
            }
        }
    }
    </script>
    @endpush

</x-app-layout>
