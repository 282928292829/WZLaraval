<x-app-layout :minimal-footer="true">
    @include('components.page-seo-slots', ['page' => $page])

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border-t-4 border-primary-500 p-6 mb-8 text-center">
            <div class="text-5xl mb-3">⭐</div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                {{ __('membership.wasetzon_membership', ['site_name' => $site_name ?? config('app.name')]) }}
            </h1>
            <p class="text-gray-500 text-sm sm:text-base">
                {{ __('membership.enjoy_exclusive_benefits_and_faster', ['site_name' => $site_name ?? config('app.name')]) }}
            </p>
        </div>

        {{-- Plans --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">

            {{-- Monthly Plan --}}
            <div class="bg-white rounded-xl border-2 border-gray-200 p-6 flex flex-col">
                <div class="text-center mb-5">
                    <div class="text-2xl font-bold text-gray-800 mb-1">
                        {{ __('membership.monthly_plan') }}
                    </div>
                    <div class="mt-3">
                        <span class="text-4xl font-bold text-primary-600">99</span>
                        <span class="text-gray-500 text-sm"> {{ __('membership.sar_month') }}</span>
                    </div>
                </div>
                <ul class="space-y-3 flex-1 mb-6">
                    @foreach([
                        __('membership.benefit_5_discount'),
                        __('membership.benefit_priority'),
                        __('membership.benefit_whatsapp'),
                        __('membership.benefit_monthly_report'),
                    ] as $benefit
                    )
                    <li class="flex gap-2.5 text-sm text-gray-700">
                        <span class="text-green-500 font-bold flex-shrink-0">✓</span>
                        <span>{{ $benefit }}</span>
                    </li>
                    @endforeach
                </ul>
                <a
                    href="https://wa.me/00966556063500?text={{ urlencode(__('membership.hello_i_would_like_to')) }}"
                    class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-3 rounded-lg transition"
                    target="_blank" rel="noopener"
                >
                    {{ __('membership.subscribe_now') }}
                </a>
            </div>

            {{-- Yearly Plan (featured) --}}
            <div class="bg-white rounded-xl border-2 border-primary-500 p-6 flex flex-col relative overflow-hidden">
                <div class="absolute top-4 left-4 bg-primary-500 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                    {{ __('membership.best_value') }}
                </div>
                <div class="text-center mb-5 mt-4">
                    <div class="text-2xl font-bold text-gray-800 mb-1">
                        {{ __('membership.yearly_plan') }}
                    </div>
                    <div class="mt-3">
                        <span class="text-4xl font-bold text-primary-600">799</span>
                        <span class="text-gray-500 text-sm"> {{ __('membership.sar_year') }}</span>
                    </div>
                    <p class="text-xs text-green-600 font-semibold mt-1">
                        {{ __('membership.save_389_sar_vs_monthly') }}
                    </p>
                </div>
                <ul class="space-y-3 flex-1 mb-6">
                    @foreach([
                        __('membership.benefit_8_discount'),
                        __('membership.benefit_highest_priority'),
                        __('membership.benefit_vip_whatsapp'),
                        __('membership.benefit_reports'),
                        __('membership.benefit_free_month'),
                    ] as $benefit
                    )
                    <li class="flex gap-2.5 text-sm text-gray-700">
                        <span class="text-green-500 font-bold flex-shrink-0">✓</span>
                        <span>{{ $benefit }}</span>
                    </li>
                    @endforeach
                </ul>
                <a
                    href="https://wa.me/00966556063500?text={{ urlencode(__('membership.hello_i_would_like_to_2')) }}"
                    class="block text-center bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-lg transition"
                    target="_blank" rel="noopener"
                >
                    {{ __('membership.subscribe_now') }}
                </a>
            </div>

        </div>

        {{-- Features Comparison --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-bold text-gray-900">
                    {{ __('membership.feature_comparison') }}
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-right px-5 py-3 text-gray-500 font-semibold w-1/2">
                                {{ __('membership.feature') }}
                            </th>
                            <th class="text-center px-4 py-3 text-gray-500 font-semibold">
                                {{ __('membership.monthly') }}
                            </th>
                            <th class="text-center px-4 py-3 text-primary-600 font-semibold">
                                {{ __('membership.yearly') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                                [__('membership.feat_commission'),     '5%',   '8%'],
                                [__('membership.feat_priority'),       '✓',    '✓✓'],
                                [__('membership.feat_whatsapp'),       '✓',    '✓'],
                                [__('membership.feat_reports'),        '✓',    '✓'],
                                [__('membership.feat_free_month'),      '—',    '✓'],
                                [__('membership.feat_account_manager'), '—',    '✓'],
                            ] as [$feature, $monthly, $yearly]
                        )
                        <tr>
                            <td class="px-5 py-3 text-gray-700">{{ $feature }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $monthly }}</td>
                            <td class="px-4 py-3 text-center font-semibold {{ $yearly === '—' ? 'text-gray-300' : 'text-primary-600' }}">{{ $yearly }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- FAQ --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" x-data="{}">
            <h2 class="font-bold text-gray-900 mb-5">
                {{ __('membership.membership_faq') }}
            </h2>
            <div class="space-y-3">
                @foreach([
                        [__('membership.faq_how_subscribe_q'), __('membership.faq_how_subscribe_a')],
                        [__('membership.faq_cancel_q'), __('membership.faq_cancel_a')],
                        [__('membership.faq_discount_q'), __('membership.faq_discount_a')],
                        [__('membership.faq_business_q'), __('membership.faq_business_a')],
                    ] as [$q, $a]
                )
                <div class="border border-gray-200 rounded-xl overflow-hidden" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-right bg-white hover:bg-gray-50 transition focus:outline-none"
                    >
                        <span class="text-sm font-semibold text-gray-900 text-right flex-1">{{ $q }}</span>
                        <span class="text-primary-500 text-xl font-bold transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-45' : ''">+</span>
                    </button>
                    <div x-show="open" x-collapse class="px-5 pb-4 text-sm text-gray-600 border-t border-gray-100 pt-3">
                        {{ $a }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- CTA --}}
        <div class="bg-primary-600 text-white rounded-xl p-8 text-center">
            <h3 class="text-xl font-bold mb-2">
                {{ __('membership.start_your_membership_today') }}
            </h3>
            <p class="text-sm opacity-90 mb-5">
                {{ __('membership.to_inquire_or_subscribe_contact') }}
            </p>
            <div class="flex gap-3 justify-center flex-wrap">
                <a
                    href="https://wa.me/00966556063500?text={{ urlencode(__('membership.hello_i_would_like_to_3')) }}"
                    class="bg-green-500 hover:bg-green-600 text-white font-bold px-6 py-3 rounded-lg transition"
                    target="_blank" rel="noopener"
                >
                    {{ __('membership.whatsapp') }}
                </a>
                <a
                    href="mailto:info@wasetzon.com"
                    class="bg-white text-primary-600 font-bold px-6 py-3 rounded-lg hover:bg-gray-50 transition"
                >
                    {{ __('membership.email_us') }}
                </a>
            </div>
        </div>

    </div>

</x-app-layout>
