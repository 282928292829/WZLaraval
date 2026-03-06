@php
    $minimalFooter = $minimalFooter ?? false;
    $hideFooter = $hideFooter ?? false;
@endphp
@php
    $yearInitiated = trim((string) \App\Models\Setting::get('copyright_year_initiated', ''));
    $currentYear = date('Y');
    $copyrightYear = ($yearInitiated !== '' && preg_match('/^\d{4}$/', $yearInitiated) && (int) $yearInitiated <= (int) $currentYear)
        ? ((int) $yearInitiated === (int) $currentYear ? $currentYear : $yearInitiated . '–' . $currentYear)
        : $currentYear;
@endphp
@if ($minimalFooter)
<footer id="page-bottom" class="bg-gray-50 border-t border-gray-100 mt-auto py-4 scroll-mt-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-gray-400">
        <span>&copy; {{ $copyrightYear }} {{ __('app.name') }}. {{ __('footer.all_rights') }}.</span>
        <a href="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
           class="ml-2 text-gray-400 hover:text-gray-500"
           style="{{ app()->getLocale() === 'ar' ? '' : "font-family: 'IBM Plex Sans Arabic', sans-serif;" }}">
            ({{ app()->getLocale() === 'ar' ? __('English') : __('Arabic') }})
        </a>
    </div>
</footer>
@elseif(! $hideFooter)
@php
    $footerWhatsapp = \App\Models\Setting::get('whatsapp', '');
    $footerEmail    = \App\Models\Setting::get('contact_email', '');
    $commercialReg  = \App\Models\Setting::get('commercial_registration', '');
    $footerInfoSlugs = ['how-to-order', 'shipping-calculator', 'payment-methods', 'faq'];
    $footerServicesSlugs = ['calculator', 'membership'];
    $footerPoliciesSlugs = ['terms-and-conditions', 'privacy-policy', 'refund-policy'];
    $footerPages = \App\Models\Page::where('show_in_footer', true)->where('is_published', true)->orderBy('menu_order')->get();
    $footerInfoPages = $footerPages->filter(fn ($p) => in_array($p->slug, $footerInfoSlugs));
    $footerServicesPages = $footerPages->filter(fn ($p) => in_array($p->slug, $footerServicesSlugs));
    $footerPoliciesPages = $footerPages->filter(fn ($p) => in_array($p->slug, $footerPoliciesSlugs));
    $footerOtherPages = $footerPages->filter(fn ($p) => ! in_array($p->slug, array_merge($footerInfoSlugs, $footerServicesSlugs, $footerPoliciesSlugs)));
@endphp
<footer class="bg-gray-50 border-t border-gray-100 mt-auto">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-8">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-8">

            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.information') }}</h4>
                <ul class="space-y-2.5">
                    @foreach($footerInfoPages->merge($footerOtherPages) as $p)
                        <li><a href="{{ url('/pages/' . $p->slug) }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ $p->getTitle() }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.services') }}</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ url('/new-order') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.new_order') }}</a></li>
                    <li><a href="{{ url('/orders') }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ __('footer.my_orders') }}</a></li>
                    @foreach($footerServicesPages as $p)
                        <li><a href="{{ url('/pages/' . $p->slug) }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ $p->getTitle() }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.policies') }}</h4>
                <ul class="space-y-2.5">
                    @foreach($footerPoliciesPages as $p)
                        <li><a href="{{ url('/pages/' . $p->slug) }}" class="text-sm text-gray-500 hover:text-gray-800 transition-colors">{{ $p->getTitle() }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-4">{{ __('footer.contact') }}</h4>
                <ul class="space-y-2.5">
                    @if ($footerWhatsapp)
                        <li>
                            <span class="text-xs text-gray-400 block">{{ __('footer.whatsapp') }}</span>
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $footerWhatsapp) }}"
                               target="_blank" rel="noopener"
                               class="text-sm text-gray-600 hover:text-primary-600 transition-colors">
                                {{ $footerWhatsapp }}
                            </a>
                        </li>
                    @endif
                    @if ($footerEmail)
                        <li>
                            <span class="text-xs text-gray-400 block">{{ __('footer.email') }}</span>
                            <a href="mailto:{{ $footerEmail }}"
                               class="text-sm text-gray-600 hover:text-primary-600 transition-colors">
                                {{ $footerEmail }}
                            </a>
                        </li>
                    @endif
                    <li class="text-sm text-gray-500">{{ __('footer.support_hours') }}</li>
                </ul>
            </div>

        </div>
    </div>

    @php $showPartners = \App\Models\Setting::get('show_partners', true); @endphp
    @if ($showPartners)
        <div class="border-t border-gray-100">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col items-center gap-6">
                <h4 class="text-sm font-semibold text-gray-500">{{ __('footer.partners') }}</h4>
                <div class="flex flex-wrap items-center justify-center gap-5 opacity-70" dir="ltr">
                    @foreach([
                        ['src' => 'images/banks/saib.svg',    'alt' => 'SAIB'],
                        ['src' => 'images/banks/riyad.svg',   'alt' => 'Riyad Bank'],
                        ['src' => 'images/banks/sab.svg',     'alt' => 'SAB'],
                        ['src' => 'images/banks/alinma.svg',  'alt' => 'Alinma Bank'],
                        ['src' => 'images/banks/albilad.svg', 'alt' => 'Bank Albilad'],
                        ['src' => 'images/banks/snb.svg',     'alt' => 'NCB / Ahli'],
                        ['src' => 'images/banks/rajhi.svg',   'alt' => 'Al Rajhi Bank'],
                    ] as $bank)
                        <img src="{{ asset($bank['src']) }}" alt="{{ $bank['alt'] }}" class="h-8 w-auto object-contain" loading="lazy">
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center justify-center gap-5 opacity-70">
                    @foreach([
                        ['src' => 'images/payment/visa.svg',          'alt' => 'Visa',          'h' => 'h-8'],
                        ['src' => 'images/payment/mastercard.svg',     'alt' => 'Mastercard',    'h' => 'h-8'],
                        ['src' => 'images/payment/paypal.svg',         'alt' => 'PayPal',        'h' => 'h-8'],
                        ['src' => 'images/payment/western-union.svg',  'alt' => 'Western Union', 'h' => 'h-8'],
                        ['src' => 'images/payment/moneygram.svg',      'alt' => 'MoneyGram',     'h' => 'h-8'],
                    ] as $pm)
                        <img src="{{ asset($pm['src']) }}" alt="{{ $pm['alt'] }}" class="{{ $pm['h'] }} w-auto object-contain" loading="lazy">
                    @endforeach
                </div>
                <img src="{{ asset('images/shipping-line.svg') }}" alt="{{ __('footer.shipping_alt') }}" class="w-full max-w-2xl object-contain opacity-70" loading="lazy" height="18">
            </div>
        </div>
    @endif

    <div class="border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-4 text-center text-xs text-gray-400">

            @if ($commercialReg)
                <div><span>{{ __('footer.commercial_reg') }}: {{ $commercialReg }}</span></div>
            @endif
            @php
                $certLogo = \App\Models\Setting::get('certification_logo', '');
                $certUrl = \App\Models\Setting::get('certification_url', '');
            @endphp
            @if ($certLogo || $certUrl)
                @php
                    $logoUrl = $certLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($certLogo)
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($certLogo)
                        : null;
                @endphp
                <div>
                    <a href="{{ $certUrl ?: '#' }}"
                       @if($certUrl) target="_blank" rel="noopener noreferrer" @endif
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:border-gray-300 hover:text-gray-600 transition-colors text-xs text-gray-500">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ __('footer.certified_by') }}" class="w-6 h-6 object-contain" loading="lazy">
                        @endif
                        {{ __('footer.certified_by') }}
                    </a>
                </div>
            @endif

            @php
                $manifestPath = public_path('build/manifest.json');
                $lastUpdated  = file_exists($manifestPath) ? filemtime($manifestPath) : null;
            @endphp
            @if ($lastUpdated)
                <div><strong>{{ __('footer.last_updated') }}:</strong> {{ date('Y/m/d', $lastUpdated) }} - {{ date('H:i', $lastUpdated) }}</div>
            @endif

            <p>
                &copy; {{ $copyrightYear }} {{ __('app.name') }}. {{ __('footer.all_rights') }}.
                <a href="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                   class="ml-2 text-gray-400 hover:text-gray-500"
                   style="{{ app()->getLocale() === 'ar' ? '' : "font-family: 'IBM Plex Sans Arabic', sans-serif;" }}">
                    ({{ app()->getLocale() === 'ar' ? __('English') : __('Arabic') }})
                </a>
            </p>

        </div>
    </div>

</footer>
@endif
