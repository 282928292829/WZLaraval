@php
    use App\Models\Setting;
    use App\Services\CommissionCalculator;

    // Load exchange rates JSON from settings; fall back to WP-parity defaults
    $erJson = Setting::get('exchange_rates', []) ?: [];
    $storedRates = $erJson['rates'] ?? [];

    $defaultRates = [
        'USD' => 3.86,
        'EUR' => 4.22,
        'GBP' => 4.89,
        'CNY' => 0.55,
        'JPY' => 0.025,
        'KRW' => 0.0027,
        'TRY' => 0.11,
        'SAR' => 1.0,
        'AED' => 1.05,
    ];

    $rates = [];
    foreach ($defaultRates as $cur => $fallback) {
        $rates[$cur] = isset($storedRates[$cur]['final']) && $storedRates[$cur]['final'] > 0
            ? (float) $storedRates[$cur]['final']
            : $fallback;
    }

    // Commission settings (flexible below/above threshold)
    $commissionSettings = CommissionCalculator::getSettings();
    $commissionThreshold = $commissionSettings['threshold'];
    $belowType = $commissionSettings['below_type'];
    $belowValue = $commissionSettings['below_value'];
    $aboveType = $commissionSettings['above_type'];
    $aboveValue = $commissionSettings['above_value'];

    // For the info card — localise numbers for display
    $displayThreshold = number_format($commissionThreshold, 0);
    $displayUsdRate      = number_format($rates['USD'], 2);
@endphp

<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Hero --}}
        <div class="text-center mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                {{ __('calc.cost_commission_calculator') }}
            </h1>
            <p class="text-sm text-gray-500">
                {{ __('calc.subtitle') }}
            </p>
        </div>

        {{-- Calculator Card --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-5 sm:p-7 mb-5"
             x-data="calculator()"
             x-init="init()">

            {{-- Input row: price + currency --}}
            <div class="mb-1">
                <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <span
                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-primary-500 text-white text-xs font-bold"
                        x-text="currencySymbol"
                    >$</span>
                    <span>{{ __('calc.enter_product_value') }}</span>
                </label>

                <div class="flex gap-2">
                    {{-- Price input --}}
                    <div class="flex-1">
                        <input
                            type="text"
                            inputmode="decimal"
                            autocomplete="off"
                            x-model="rawInput"
                            @input="onInput()"
                            @focus="$event.target.select()"
                            placeholder="{{ __('calc.example_100') }}"
                            class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-center text-base font-semibold focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 focus:outline-none transition"
                            dir="ltr"
                        />
                    </div>

                    {{-- Currency select --}}
                    <div class="flex-none w-44 sm:w-52">
                        <select
                            x-model="currency"
                            @change="calculate()"
                            class="w-full border-2 border-gray-200 rounded-xl px-3 py-3 text-center text-sm font-semibold focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 focus:outline-none transition appearance-none bg-gray-50 cursor-pointer"
                        >
                            <option value="USD">{{ __('calc.usd') }}</option>
                            <option value="EUR">{{ __('calc.eur') }}</option>
                            <option value="GBP">{{ __('calc.gbp') }}</option>
                            <option value="CNY">{{ __('calc.cny') }}</option>
                            <option value="JPY">{{ __('calc.jpy') }}</option>
                            <option value="KRW">{{ __('calc.krw') }}</option>
                            <option value="TRY">{{ __('calc.try') }}</option>
                            <option value="SAR">{{ __('calc.sar') }}</option>
                            <option value="AED">{{ __('calc.aed') }}</option>
                        </select>
                    </div>
                </div>

                <p class="mt-2 text-xs text-center text-gray-400" style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">
                    {{ __('calc.enter_price_hint') }}
                </p>
            </div>

            {{-- Error --}}
            <div
                x-show="error"
                x-text="error"
                x-transition
                class="mt-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm text-center"
            ></div>

            {{-- Results — always visible --}}
            <div class="mt-5 flex flex-col gap-3">
                {{-- Products value --}}
                <div class="flex items-center justify-between px-4 py-3 bg-orange-50 border border-orange-100 rounded-xl">
                    <span class="text-sm text-gray-600 font-medium">{{ __('calc.products_value') }}</span>
                    <span class="flex items-center gap-1.5 font-bold text-primary-600 text-base" dir="ltr">
                        <span x-show="hasValue" class="text-xs text-gray-400 font-normal" style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">{{ __('calc.sar_currency') }}</span>
                        <span x-text="hasValue ? formatNum(convertSAR) : '{{ __('calc.enter_amount_to_calc') }}'"
                              :class="hasValue ? '' : 'text-sm text-gray-400 font-normal'"
                              :style="hasValue ? '' : 'font-family: \'IBM Plex Sans Arabic\', ui-sans-serif, system-ui, sans-serif;'"></span>
                    </span>
                </div>

                {{-- Commission --}}
                <div class="flex items-center justify-between px-4 py-3 bg-orange-50 border border-orange-100 rounded-xl">
                    <span class="text-sm text-gray-600 font-medium">{{ __('calc.wasetzon_commission', ['site_name' => $site_name ?? config('app.name')]) }}</span>
                    <span class="flex items-center gap-1.5 font-bold text-primary-600 text-base" dir="ltr">
                        <span x-show="hasValue" class="text-xs text-gray-400 font-normal" style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">{{ __('calc.sar_currency') }}</span>
                        <span x-text="hasValue ? formatNum(commission) : '{{ __('calc.enter_amount_to_calc') }}'"
                              :class="hasValue ? '' : 'text-sm text-gray-400 font-normal'"
                              :style="hasValue ? '' : 'font-family: \'IBM Plex Sans Arabic\', ui-sans-serif, system-ui, sans-serif;'"></span>
                    </span>
                </div>

                {{-- Total — gradient row --}}
                <div class="flex items-center justify-between px-4 py-4 rounded-xl"
                     style="background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);">
                    <span class="text-base text-white font-semibold">💰 {{ __('calc.total_without_shipping') }}</span>
                    <span class="flex items-center gap-1.5 font-bold text-white text-lg" dir="ltr">
                        <span x-show="hasValue" class="text-sm text-white/80 font-normal" style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">{{ __('calc.sar_currency') }}</span>
                        <span x-text="hasValue ? formatNum(total) : '{{ __('calc.enter_amount_to_calc') }}'"
                              :class="hasValue ? '' : 'text-sm text-white/70 font-normal'"
                              :style="hasValue ? '' : 'font-family: \'IBM Plex Sans Arabic\', ui-sans-serif, system-ui, sans-serif;'"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-5 sm:p-7 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                <span>ℹ️</span>
                {{ __('calc.important_info') }}
            </h2>

            <div class="flex flex-col gap-3">
                {{-- 1: Exchange rate --}}
                <div class="flex gap-3 items-start bg-orange-50 border border-orange-100 rounded-xl p-4">
                    <div class="flex-none w-7 h-7 flex items-center justify-center rounded-lg bg-primary-500 text-white text-xs font-bold">1</div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ __('calc.exchange_rate') }}</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            <strong>{{ __('calc.usd_to_sar', ['rate' => $displayUsdRate]) }}</strong><br>
                            <span class="text-gray-400">إبراء للذمة: {{ __('calc.exchange_rate') }} يُحدَّد تلقائيًا ليشمل رسوم تحويل العملة ورسوم العمليات الدولية والمعالجة البنكية.</span>
                        </p>
                    </div>
                </div>

                {{-- 2: Commission --}}
                <div class="flex gap-3 items-start bg-orange-50 border border-orange-100 rounded-xl p-4">
                    <div class="flex-none w-7 h-7 flex items-center justify-center rounded-lg bg-primary-500 text-white text-xs font-bold">2</div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ __('calc.commission') }}</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            <strong>{{ $belowType === 'flat'
                                ? __('calc.commission_below_flat', ['value' => number_format($belowValue, 0), 'threshold' => $displayThreshold])
                                : __('calc.commission_below_percent', ['value' => number_format($belowValue, 0), 'threshold' => $displayThreshold]) }}</strong><br>
                            <strong>{{ $aboveType === 'flat'
                                ? __('calc.commission_above_flat', ['value' => number_format($aboveValue, 0), 'threshold' => $displayThreshold])
                                : __('calc.commission_above_percent', ['value' => number_format($aboveValue, 0), 'threshold' => $displayThreshold]) }}</strong>
                        </p>
                    </div>
                </div>

                {{-- 3: Shipping separate --}}
                <div class="flex gap-3 items-start bg-orange-50 border border-orange-100 rounded-xl p-4">
                    <div class="flex-none w-7 h-7 flex items-center justify-center rounded-lg bg-primary-500 text-white text-xs font-bold">3</div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ __('calc.shipping_separate') }}</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            {{ __('calc.shipping_separate_desc') }}
                            <a href="{{ route('pages.show', 'shipping-calculator') }}" class="text-primary-600 font-semibold underline hover:no-underline">
                                {{ __('calc.shipping_calc_link') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- CTA --}}
        <div class="rounded-xl p-7 sm:p-10 text-center text-white shadow-lg"
             style="background: linear-gradient(135deg, #f97316 0%, #fb923c 100%); box-shadow: 0 10px 30px rgba(249,115,22,0.3);">
            <h2 class="text-2xl font-bold mb-3">{{ __('calc.ready_to_order') }}</h2>
            <p class="text-base mb-6 opacity-95 leading-relaxed">
                {{ __('calc.start_order_desc') }}
            </p>
            <a
                href="{{ route('new-order') }}"
                class="inline-flex items-center gap-2 bg-white text-primary-600 font-bold px-8 py-4 rounded-xl text-lg hover:-translate-y-0.5 hover:shadow-xl transition-all"
            >
                <span>{{ __('calc.new_order') }}</span>
                <span>🚀</span>
            </a>
        </div>

    </div>

    @push('scripts')
    <script>
    function calculator() {
        return {
            rawInput: '',
            currency: 'USD',
            currencySymbol: '$',
            hasValue: false,
            convertSAR: 0,
            commission: 0,
            total: 0,
            error: '',

            // Rates pulled from settings (SAR per 1 unit of each currency)
            rates: @json($rates),

            // Commission settings (flexible below/above)
            commissionSettings: @json($commissionSettings),

            symbols: {
                USD: '$', EUR: '€', GBP: '£', CNY: '¥',
                JPY: '¥', KRW: '₩', TRY: '₺', SAR: 'ر.س', AED: 'د.إ',
            },

            init() {
                this.calculate();
            },

            toEnglishDigits(str) {
                // Use global function for consistency (handles both Arabic-Indic and Persian digits)
                return window.toEnglishDigits(str);
            },

            onInput() {
                const converted = this.toEnglishDigits(this.rawInput);
                if (converted !== this.rawInput) {
                    this.rawInput = converted;
                }
                this.calculate();
            },

            calculate() {
                this.currencySymbol = this.symbols[this.currency] || '$';
                this.error = '';

                const val   = this.toEnglishDigits(this.rawInput || '').replace(',', '.');
                const price = parseFloat(val);

                if (!val || isNaN(price) || price <= 0) {
                    this.hasValue   = false;
                    this.convertSAR = 0;
                    this.commission = 0;
                    this.total      = 0;
                    return;
                }

                const rate      = this.rates[this.currency] || 1;
                this.convertSAR = price * rate;
                this.commission = this.calcCommission(this.convertSAR);
                this.total    = this.convertSAR + this.commission;
                this.hasValue = true;
            },

            calcCommission(subtotalSAR) {
                const s = this.commissionSettings;
                const isAbove = subtotalSAR >= s.threshold;
                if (isAbove) {
                    return s.above_type === 'percent' ? subtotalSAR * (s.above_value / 100) : s.above_value;
                }
                return s.below_type === 'percent' ? subtotalSAR * (s.below_value / 100) : s.below_value;
            },

            formatNum(num) {
                return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            },
        };
    }
    </script>
    @endpush

</x-app-layout>
