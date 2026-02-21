@php
    use App\Models\Setting;

    $isAr        = app()->getLocale() === 'ar';
    $gramLabel   = $isAr ? 'جرام' : 'gram';
    $billedLabel = $isAr ? 'يُحسب كـ' : 'billed as';
    $kgLabel     = __('shipping.kg');

    // Load carrier rates from admin settings (fall back to WP-parity defaults)
    $aramexFirst   = (int) Setting::get('aramex_first_half_kg',   119);
    $aramexRest    = (int) Setting::get('aramex_rest_half_kg',    39);
    $aramexOver21  = (int) Setting::get('aramex_over21_per_kg',   59);
    $aramexDays    = Setting::get('aramex_delivery_days', '7-10');

    $dhlFirst      = (int) Setting::get('dhl_first_half_kg',      169);
    $dhlRest       = (int) Setting::get('dhl_rest_half_kg',       43);
    $dhlOver21     = (int) Setting::get('dhl_over21_per_kg',      63);
    $dhlDays       = Setting::get('dhl_delivery_days', '7-10');

    $domFirst      = (int) Setting::get('domestic_first_half_kg', 69);
    $domRest       = (int) Setting::get('domestic_rest_half_kg',  19);
    $domDays       = Setting::get('domestic_delivery_days', '4-7');
@endphp
<x-app-layout>
    <x-slot name="title">{{ $isAr ? $page->seo_title_ar : $page->seo_title_en }}</x-slot>
    <x-slot name="description">{{ $isAr ? $page->seo_description_ar : $page->seo_description_en }}</x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border-t-4 border-primary-500 p-6 mb-6 text-center">
            <div class="text-4xl mb-3">✈️</div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                {{ __('shipping.shipping_price_calculator') }}
            </h1>
            <p class="text-gray-500 text-sm">
                {{ __('shipping.calculate_international_shipping_cost_from') }}
            </p>
        </div>

        {{-- Calculator --}}
        <div
            class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6"
            x-data="shippingCalc()"
            x-init="calculate()"
        >
            <div class="space-y-5">

                {{-- Carrier Selection --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        {{ __('shipping.select_carrier') }}
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="[key, carrier] in Object.entries(carriers)" :key="key">
                            <button
                                @click="selectedCarrier = key; calculate()"
                                :class="selectedCarrier === key
                                    ? 'border-primary-500 bg-primary-50 text-primary-700'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'"
                                class="border-2 rounded-xl p-3 text-center transition cursor-pointer"
                            >
                                <div class="text-2xl mb-1" x-text="carrier.icon"></div>
                                <div class="text-xs font-bold" x-text="carrier.name"></div>
                                <div class="text-xs text-gray-400 mt-0.5" x-text="carrier.note"></div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Weight Input --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('shipping.weight') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            inputmode="decimal"
                            x-model="weightRaw"
                            @input="onWeightInput()"
                            :class="inputError ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300 focus:ring-primary-500 focus:border-primary-500'"
                            class="flex-1 rounded-lg px-4 py-3 text-base text-right border focus:ring-2 transition"
                            placeholder="{{ __('shipping.enter_weight') }}"
                            dir="rtl"
                            autocomplete="off"
                        />
                        {{-- Unit Toggle --}}
                        <div class="flex rounded-lg border border-gray-300 overflow-hidden flex-shrink-0" dir="ltr">
                            <button
                                @click="unit = 'gram'; calculate()"
                                :class="unit === 'gram' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                class="px-3 py-3 text-sm font-semibold transition border-r border-gray-300"
                            >{{ $gramLabel }}</button>
                            <button
                                @click="unit = 'kg'; calculate()"
                                :class="unit === 'kg' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                class="px-3 py-3 text-sm font-semibold transition"
                            >{{ __('shipping.kg') }}</button>
                        </div>
                    </div>
                    <p x-show="inputError" x-transition
                       class="mt-1.5 text-xs text-red-500 font-medium"
                       style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">
                        ⚠️ {{ __('shipping.numbers_only_hint') }}
                    </p>
                    <p x-show="!inputError"
                       class="mt-1.5 text-xs text-gray-400"
                       style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">
                        {{ __('shipping.numbers_format_hint') }}
                    </p>
                </div>

                {{-- Result --}}
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-bold text-gray-800">
                            {{ __('shipping.estimated_shipping_cost') }}
                        </span>
                        <span class="text-2xl font-bold text-primary-600" x-text="fmt(shippingCost) + ' {{ __('shipping.sar') }}'"></span>
                    </div>

                    {{-- Breakdown --}}
                    <div class="space-y-1.5 text-sm text-gray-600 border-t border-gray-200 pt-3">
                        <div class="flex justify-between">
                            <span>{{ __('shipping.weight') }}</span>
                            <span class="font-mono" x-text="weightDisplay()"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ __('shipping.carrier') }}</span>
                            <span class="font-semibold" x-text="carriers[selectedCarrier]?.name"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ __('shipping.estimated_delivery') }}</span>
                            <span x-text="carriers[selectedCarrier]?.delivery"></span>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-gray-400">
                        * {{ __('shipping.this_is_an_estimate_final') }}
                    </p>
                </div>

            </div>
        </div>

        {{-- Pricing Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900">
                    {{ __('shipping.shipping_price_table') }}
                </h2>
            </div>

            {{-- Aramex --}}
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <span class="text-lg">📦</span>
                    {{ __('shipping.aramex_—_economy_shipping') }}
                </h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold">
                            <th class="text-right py-1.5">{{ __('shipping.weight') }}</th>
                            <th class="text-left py-1.5">{{ __('shipping.price') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            [__('shipping.first_half_kg'),        (string) $aramexFirst],
                            [__('shipping.additional_half_kg'),   '+' . $aramexRest],
                            [__('shipping.over_21_kg'),           $aramexOver21 . '/kg'],
                        ] as [$label, $price])
                        <tr>
                            <td class="py-2 text-gray-700">{{ $label }}</td>
                            <td class="py-2 font-semibold text-gray-900 text-left ltr:text-left rtl:text-right" dir="ltr">{{ $price }} SAR</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-gray-400">{{ __('shipping.weight_rounded') }}</p>
            </div>

            {{-- DHL --}}
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <span class="text-lg">🚀</span>
                    {{ __('shipping.dhl_—_express_shipping') }}
                </h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold">
                            <th class="text-right py-1.5">{{ __('shipping.weight') }}</th>
                            <th class="text-left py-1.5">{{ __('shipping.price') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            [__('shipping.first_half_kg'),        (string) $dhlFirst],
                            [__('shipping.additional_half_kg'),   '+' . $dhlRest],
                            [__('shipping.over_21_kg'),           $dhlOver21 . '/kg'],
                        ] as [$label, $price])
                        <tr>
                            <td class="py-2 text-gray-700">{{ $label }}</td>
                            <td class="py-2 font-semibold text-gray-900 text-left ltr:text-left rtl:text-right" dir="ltr">{{ $price }} SAR</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-gray-400">{{ __('shipping.weight_rounded') }}</p>
            </div>

            {{-- US Domestic --}}
            <div class="px-5 py-4">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <span class="text-lg">🏠</span>
                    {{ __('shipping.us_domestic_shipping') }}
                </h3>
                <p class="text-sm text-gray-600 mb-3">
                    {{ __('shipping.for_orders_requiring_domestic_us') }}
                </p>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs font-semibold">
                            <th class="text-right py-1.5">{{ __('shipping.weight') }}</th>
                            <th class="text-left py-1.5">{{ __('shipping.price') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            [__('shipping.first_half_kg'),        (string) $domFirst],
                            [__('shipping.additional_half_kg'),   '+' . $domRest],
                        ] as [$label, $price])
                        <tr>
                            <td class="py-2 text-gray-700">{{ $label }}</td>
                            <td class="py-2 font-semibold text-gray-900 text-left ltr:text-left rtl:text-right" dir="ltr">{{ $price }} SAR</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-gray-400">{{ __('shipping.weight_rounded') }}</p>
            </div>
        </div>

        {{-- Notes --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
            <h3 class="font-bold text-amber-900 mb-3">
                {{ __('shipping.important_notes') }}
            </h3>
            <ul class="space-y-2 text-sm text-amber-800">
                @foreach(
                    [
                        __('shipping.note_1'),
                        __('shipping.note_2'),
                        __('shipping.note_3'),
                        __('shipping.note_4'),
                        __('shipping.note_5'),
                    ] as $note
                )
                <li class="flex gap-2">
                    <span class="text-amber-500 flex-shrink-0">•</span>
                    <span>{{ $note }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- CTA --}}
        <div class="bg-primary-600 text-white rounded-xl p-6 text-center">
            <h3 class="text-lg font-bold mb-2">
                {{ __('shipping.questions_about_shipping') }}
            </h3>
            <p class="text-sm opacity-90 mb-4">
                {{ __('shipping.contact_us_and_our_team') }}
            </p>
            <div class="flex gap-3 justify-center flex-wrap">
                <a
                    href="https://wa.me/00966556063500"
                    class="bg-green-500 text-white font-bold px-6 py-2.5 rounded-lg hover:bg-green-600 transition"
                    target="_blank" rel="noopener"
                >
                    {{ __('shipping.whatsapp') }}
                </a>
                <a
                    href="{{ route('new-order') }}"
                    class="bg-white text-primary-600 font-bold px-6 py-2.5 rounded-lg hover:bg-gray-50 transition"
                >
                    {{ __('shipping.start_order') }}
                </a>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    function shippingCalc() {
        return {
            weightRaw: '',
            unit: 'kg',
            selectedCarrier: 'aramex',
            shippingCost: 0,
            roundedWeight: 0,
            inputError: false,

            carriers: {
                aramex: {
                    name: 'Aramex',
                    icon: '📦',
                    note: '{{ __('shipping.economy') }}',
                    delivery: '{{ $aramexDays }}',
                    firstHalfKg: {{ $aramexFirst }},
                    restHalfKg: {{ $aramexRest }},
                    over21PerKg: {{ $aramexOver21 }},
                },
                dhl: {
                    name: 'DHL',
                    icon: '🚀',
                    note: '{{ __('shipping.express') }}',
                    delivery: '{{ $dhlDays }}',
                    firstHalfKg: {{ $dhlFirst }},
                    restHalfKg: {{ $dhlRest }},
                    over21PerKg: {{ $dhlOver21 }},
                },
                domestic: {
                    name: '{{ __('shipping.us_domestic') }}',
                    icon: '🏠',
                    note: '{{ __('shipping.within_usa') }}',
                    delivery: '{{ $domDays }}',
                    firstHalfKg: {{ $domFirst }},
                    restHalfKg: {{ $domRest }},
                    over21PerKg: null,
                },
            },

            toEnglishDigits(str) {
                let result = window.toEnglishDigits(str || '');
                return result.replace('٫', '.').replace(',', '');
            },

            onWeightInput() {
                const converted = this.toEnglishDigits(this.weightRaw);
                if (converted !== this.weightRaw) {
                    this.weightRaw = converted;
                }
                // Validate: allow empty, digits, single dot/comma for decimals
                const trimmed = this.weightRaw.trim();
                if (trimmed !== '' && !/^\d*\.?\d*$/.test(trimmed)) {
                    this.inputError = true;
                    this.shippingCost = 0;
                    this.roundedWeight = 0;
                    return;
                }
                this.inputError = false;
                this.calculate();
            },

            parseWeight() {
                const cleaned = this.toEnglishDigits(this.weightRaw).trim();
                const w = parseFloat(cleaned);
                if (isNaN(w) || w <= 0) return 0;
                return this.unit === 'gram' ? w / 1000 : w;
            },

            roundWeight(w) {
                const intPart = Math.floor(w);
                const fracPart = w - intPart;
                if (fracPart === 0) return w;
                return fracPart <= 0.5 ? intPart + 0.5 : intPart + 1;
            },

            calculate() {
                const w = this.parseWeight();
                const carrier = this.carriers[this.selectedCarrier];
                if (!carrier || w <= 0) { this.shippingCost = 0; this.roundedWeight = 0; return; }

                const rounded = this.roundWeight(w);
                this.roundedWeight = rounded;

                if (rounded >= 21 && carrier.over21PerKg !== null) {
                    this.shippingCost = Math.round(carrier.over21PerKg * Math.ceil(rounded));
                } else {
                    const additionalHalves = 2 * (rounded - 0.5);
                    this.shippingCost = Math.round(carrier.firstHalfKg + carrier.restHalfKg * additionalHalves);
                }
            },

            weightDisplay() {
                if (!this.roundedWeight) return '\u2014';
                const kg = '{{ $kgLabel }}';
                if (this.unit === 'gram') {
                    return (this.roundedWeight * 1000) + ' {{ $gramLabel }} ({{ $billedLabel }} ' + this.roundedWeight + ' ' + kg + ')';
                }
                return this.roundedWeight + ' ' + kg;
            },

            fmt(val) {
                return (val || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            },
        };
    }
    </script>
    @endpush

</x-app-layout>
