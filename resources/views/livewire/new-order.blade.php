{{-- /new-order — Production order form --}}

@php
    $isLoggedIn = auth()->check();
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp

{{-- ============================================================ --}}
{{-- Single root required for Livewire --}}
{{-- ============================================================ --}}
<div>
{{-- SUCCESS SCREEN — configurable via Settings > Order Success Screen --}}
@if ($showSuccessScreen)
@php
    $seconds = $successRedirectSeconds ?? 45;
    $titleRaw = trim((string) \App\Models\Setting::get('order_success_title_' . $locale, ''));
    $title = $titleRaw !== '' ? $titleRaw : __('order.success_title');
    $subtitleRaw = trim((string) \App\Models\Setting::get('order_success_subtitle_' . $locale, ''));
    $subtitle = $subtitleRaw !== '' ? str_replace(':number', $createdOrderNumber, $subtitleRaw) : __('order.success_subtitle', ['number' => $createdOrderNumber]);
    $messageRaw = trim((string) \App\Models\Setting::get('order_success_message_' . $locale, ''));
    $message = $messageRaw !== '' ? $messageRaw : __('order.success_message');
    $goToOrderRaw = trim((string) \App\Models\Setting::get('order_success_go_to_order_' . $locale, ''));
    $goToOrder = $goToOrderRaw !== '' ? $goToOrderRaw : __('order.success_go_to_order');
    $prefixRaw = trim((string) \App\Models\Setting::get('order_success_redirect_prefix_' . $locale, ''));
    $prefix = $prefixRaw !== '' ? $prefixRaw : __('order.success_redirect_countdown_prefix');
    $suffixRaw = trim((string) \App\Models\Setting::get('order_success_redirect_suffix_' . $locale, ''));
    $suffix = $suffixRaw !== '' ? $suffixRaw : __('order.success_redirect_countdown_suffix');
@endphp
<div class="min-h-dvh min-h-screen flex items-start justify-center bg-gradient-to-br from-orange-50 to-orange-100 p-4 pt-12">
    <div class="text-center max-w-[420px] w-full md:max-w-[560px]">
        {{-- Checkmark --}}
        <div class="w-14 h-14 md:w-[72px] md:h-[72px] md:mb-4 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 md:w-9 md:h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800 mb-1.5 md:mb-2">
            {{ $title }}
        </h1>
        <div class="text-lg md:text-xl font-semibold text-orange-500 mb-2.5 md:mb-3.5">
            {{ $subtitle }}
        </div>
        <p class="text-slate-600 leading-relaxed text-sm md:text-base mb-3.5 md:mb-4 whitespace-pre-line">
            {{ $message }}
        </p>
        <a
            href="{{ route('orders.show', $createdOrderId) }}"
            class="inline-block bg-orange-500 text-white font-semibold px-6 py-3 rounded-lg no-underline text-base md:text-lg md:px-7 md:py-3.5 mb-2.5 md:mb-3.5 hover:bg-orange-600 transition-colors"
        >
            {{ $goToOrder }}
        </a>
        <div class="text-slate-500 text-sm md:text-base">
            {{ $prefix }}<span id="wz-countdown-seconds">{{ $seconds }}</span>{{ $suffix }}
        </div>
    </div>
    @script
    <script>
        (function() {
            const url = @js(route('orders.show', $createdOrderId));
            const seconds = @js($seconds);
            if (seconds <= 0) {
                window.location.href = url;
                return;
            }
            function start() {
                const span = document.getElementById('wz-countdown-seconds');
                if (!span) return setTimeout(start, 50);
                let s = seconds;
                const t = setInterval(function() {
                    s--;
                    span.textContent = s;
                    if (s <= 0) { clearInterval(t); window.location.href = url; }
                }, 1000);
            }
            setTimeout(start, 0);
        })();
    </script>
    @endscript
</div>
@else
{{-- ============================================================ --}}
{{-- ORDER FORM                                                   --}}
{{-- ============================================================ --}}
<div
    x-data="newOrderForm(
        @js($exchangeRates),
        @js($currencies),
        {{ $maxProducts }},
        @js($defaultCurrency),
        {{ $isLoggedIn ? 'true' : 'false' }},
        @js($commissionSettings),
        @js(($editingOrderId || $productUrl || $duplicateFrom) ? $items : null),
        @js($editingOrderId ? $orderNotes : null),
        {{ $maxImagesPerItem }},
        {{ $maxImagesPerOrder }},
        @js(__('order_form.max_per_item_reached', ['max' => $maxImagesPerItem])),
        @js(__('order_form.max_files', ['max' => $maxImagesPerOrder])),
        @js([
            'colors' => [
                __('order_form.test_color_1'),
                __('order_form.test_color_2'),
                __('order_form.test_color_3'),
                __('order_form.test_color_4'),
                __('order_form.test_color_5'),
            ],
            'sizes' => [
                __('order_form.test_size_s'),
                __('order_form.test_size_m'),
                __('order_form.test_size_l'),
                __('order_form.test_size_xl'),
                __('order_form.test_size_us8'),
                __('order_form.test_size_us10'),
                __('order_form.test_size_one'),
            ],
            'notes' => [
                __('order_form.test_note_1'),
                __('order_form.test_note_2'),
                __('order_form.test_note_3'),
                __('order_form.test_note_4'),
                __('order_form.test_note_5'),
            ],
        ]),
        @js($allowedMimeTypes ?? []),
        {{ $maxFileSizeBytes ?? (2 * 1024 * 1024) }}
    )"
    x-init="
        init();
        @if ($duplicateFrom)
        $nextTick(() => showNotify('success', @js(__('order.duplicate_prefilled'))));
        @endif
        @if ($editingOrderId)
        $nextTick(() => showNotify('success', @js(__('orders.edit_prefilled'))));
        @endif
    "
    @notify.window="showNotify($event.detail.type, $event.detail.message)"
    @zoom-image.window="zoomedImage = $event.detail"
    @keydown.escape.window="closeZoom()"
    class="bg-white text-slate-800 font-[family-name:var(--font-family-arabic)]"
>

{{-- Toast Container --}}
<div x-ref="toasts" id="toast-container"></div>

<div class="max-w-7xl mx-auto p-4 lg:flex lg:flex-col lg:h-[calc(100vh-7rem)] lg:overflow-hidden lg:min-h-0">
<main id="main-content" class="lg:flex lg:flex-col lg:min-h-0 lg:flex-1">

    <div class="flex flex-wrap items-center justify-between gap-2 mb-4 lg:shrink-0">
        <h1 class="text-xl font-bold text-slate-800 m-0">{{ $editingOrderId ? __('orders.edit_order_title', ['number' => $editingOrderNumber]) : __('Create new order') }}</h1>
        @if (config('app.env') === 'local')
        <button type="button" @click="addFiveTestItems()" class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
            {{ __('order.dev_add_5_test_items') }}
        </button>
        @endif
    </div>

    {{-- Tips Box --}}
    <section class="bg-white rounded-lg shadow-sm border border-orange-100 mb-5 overflow-hidden lg:shrink-0" x-show="!tipsHidden" x-cloak>
        <div class="px-4 py-3 flex justify-between items-center cursor-pointer border-b border-orange-100" @click="tipsOpen = !tipsOpen">
            <h2 class="text-sm font-semibold text-slate-800 m-0">{{ __('order_form.tips_title') }}</h2>
            <span x-text="tipsOpen ? '▲' : '▼'" class="text-primary-500 text-xs"></span>
        </div>
        <div x-show="tipsOpen" x-collapse class="p-4 text-sm leading-relaxed text-slate-600">
            <ul class="list-none p-0 m-0">
                @for ($i = 1; $i <= 8; $i++)
                    <li class="mb-2.5 relative ps-[18px] before:content-['•'] before:absolute before:start-0 before:text-primary-500 before:font-bold">{{ __("order_form.tip_{$i}") }}</li>
                @endfor
            </ul>
            <div class="mt-4 pt-4 border-t border-orange-100">
                <label class="flex items-center gap-2 text-sm text-slate-500 cursor-pointer">
                    <input type="checkbox" @change="hideTips30Days()" class="cursor-pointer">
                    <span>{{ __('order_form.tips_dont_show') }}</span>
                </label>
            </div>
        </div>
    </section>

    {{-- Order Form --}}
    <div id="order-form" class="lg:flex lg:flex-col lg:flex-1 lg:min-h-0 !pb-[5.5rem] lg:!pb-4">

        @if ($editingOrderId)
        <section class="p-3 mb-3 bg-amber-100 border border-amber-300 rounded-xl">
            <h2 class="text-base font-semibold text-amber-800 m-0">
                {{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}
            </h2>
            <p class="text-sm text-amber-700 mt-1.5 mb-0">{{ __('orders.edit_resubmit_deadline_hint') }}</p>
        </section>
        @endif

        {{-- Products Section — desktop: HTML table (scrolls); mobile: collapsible cards --}}
        <section class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 mb-4 lg:mb-0 lg:flex lg:flex-col lg:flex-1 lg:min-h-0">

            {{-- Desktop: HTML table (only scrollable area) — design-3 layout --}}
            <div x-ref="tableScrollContainer" class="overflow-auto lg:flex-1 lg:min-h-0 lg:min-w-0 hidden lg:block">
                <table class="w-full border-collapse table-fixed min-w-[720px]">
                    <colgroup>
                        <col style="width:2rem">
                        <col style="width:1.75rem">
                        <col style="width:auto">
                        <col style="width:8.25rem">
                        <col style="width:8.25rem">
                        <col style="width:6rem">
                        <col style="width:4.25rem">
                        <col style="width:auto">
                        <col style="width:auto">
                        <col style="width:7rem">
                    </colgroup>
                    <thead class="sticky top-0 z-10 bg-orange-50 shadow-sm">
                        <tr>
                            <th class="p-2 w-9" aria-label="{{ __('order_form.remove_row') }}"></th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">#</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_url') }}</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_color') }}</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_size') }}</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_qty') }}</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_price_per_unit') }}</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_currency') }}</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_notes') }}</th>
                            <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_files') }}</th>
                        </tr>
                    </thead>
                    <tbody class="border-t border-orange-100">
                        <template x-for="(item, idx) in items" :key="idx">
                            @include('livewire.partials._order-item-table-row')
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Desktop: Add Product, General notes, Reset — always under table edge --}}
            <div class="hidden lg:flex lg:flex-col lg:gap-3 lg:shrink-0 pt-3 border-t border-orange-100">
                <button type="button" @click="addProduct()" class="w-full py-3 inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary-500/10 to-primary-400/5 text-primary-500 border-2 border-primary-500/25 font-semibold rounded-md text-sm hover:from-primary-500/20 hover:to-primary-400/10 hover:border-primary-500 transition-all">
                    + {{ __('order_form.add_product') }}
                </button>
                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-semibold text-slate-800 m-0">{{ __('order_form.general_notes') }}</h3>
                        <button type="button" @click="resetAll()" class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                            {{ __('order_form.reset_all') }}
                        </button>
                    </div>
                    <textarea x-model="orderNotes" @input.debounce.500ms="saveDraft()" placeholder="{{ __('order_form.general_notes_ph') }}" rows="2" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"></textarea>
                </div>
            </div>

            {{-- Mobile: collapsible cards --}}
            <div id="items-container-wrapper" class="lg:hidden flex flex-col gap-2.5">
                <div id="items-container" class="flex flex-col gap-2.5">
                    <template x-for="(item, idx) in items" :key="idx">
                        @include('livewire.partials._order-item-mobile-card')
                    </template>
                </div>
            </div>

        </section>

        {{-- Add Product + General Notes — mobile only; desktop has them under table --}}
        <div class="lg:hidden bg-white -mx-4 px-4 pt-2 pb-2 border-t border-orange-100/60">
            <button type="button" @click="addProduct()" class="w-full py-3 inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary-500/10 to-primary-400/5 text-primary-500 border-2 border-primary-500/25 font-semibold rounded-md text-sm hover:from-primary-500/20 hover:to-primary-400/10 hover:border-primary-500 transition-all">
                + {{ __('order_form.add_product') }}
            </button>
            <section class="mt-2">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                    <h3 class="text-base m-0">{{ __('order_form.general_notes') }}</h3>
                    <button type="button" @click="resetAll()" class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                        {{ __('order_form.reset_all') }}
                    </button>
                </div>
                <textarea x-model="orderNotes" @input.debounce.500ms="saveDraft()" placeholder="{{ __('order_form.general_notes_ph') }}" rows="2" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors sm:text-base"></textarea>
            </section>
        </div>

        {{-- Fixed Footer --}}
        <div class="order-summary-card">
            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                <span id="items-count" class="text-[0.7rem] font-normal text-stone-400 whitespace-nowrap overflow-hidden text-ellipsis" x-text="productCountText()"></span>
                <span class="text-stone-400 font-normal text-[0.7rem] whitespace-nowrap summary-total" x-text="totalText()"></span>
            </div>
            <button type="button" @click="submitOrder()" :disabled="submitting"
                    id="submit-order"
                    class="shrink-0 min-w-[120px] max-w-[180px] w-auto inline-flex items-center justify-center py-3 px-4 rounded-md font-semibold text-base w-full bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 hover:shadow-xl hover:shadow-primary-500/30 hover:-translate-y-0.5 transition-all disabled:opacity-60 disabled:pointer-events-none">
                @if ($editingOrderId)
                <span x-show="!submitting">{{ __('orders.save_changes') }}</span>
                @else
                <span x-show="!submitting">{{ __('order_form.confirm_order') }}</span>
                @endif
                <span x-show="submitting" x-cloak>{{ __('order_form.submitting') }}...</span>
            </button>
        </div>

    </div>
</main>
</div>

{{-- Image Zoom Modal --}}
<div class="fixed inset-0 z-[9998] bg-black/90 flex items-center justify-center p-4"
     x-show="zoomedImage"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click.self="closeZoom()">
    <button type="button" class="absolute top-4 end-4 w-10 h-10 flex items-center justify-center rounded-full bg-white/20 text-white text-2xl border-none cursor-pointer hover:bg-white/30 z-10" @click="closeZoom()" aria-label="{{ __('Close') }}">&times;</button>
    <img :src="zoomedImage" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl" @click.stop alt="">
</div>

{{-- Login Modal --}}
<div class="order-login-modal-overlay"
     :class="{ 'show': $wire.showLoginModal }"
     @click.self="$wire.closeModal()">
    <div class="order-login-modal">
        <div class="py-8 px-8 pb-5 border-b border-orange-100 relative">
            <button type="button" class="absolute top-5 start-5 w-8 h-8 flex items-center justify-center rounded-full text-slate-400 text-3xl border-none bg-transparent cursor-pointer hover:bg-black/5 hover:text-slate-800 transition-colors" @click="$wire.closeModal()">&times;</button>
            <h2 class="text-2xl font-bold text-slate-800 mb-2.5 text-center">
                <span x-show="$wire.loginModalReason === 'submit'">{{ __('order_form.modal_title') }}</span>
                <span x-show="$wire.loginModalReason === 'attach'" x-cloak>{{ __('order_form.modal_title_attach') }}</span>
            </h2>
            <p class="text-sm text-slate-500 text-center m-0" x-show="$wire.loginModalReason === 'submit'">✅ {{ __('order_form.data_saved') }} {{ __('order_form.modal_email_hint') }}</p>
            <p class="text-sm text-slate-500 text-center m-0" x-show="$wire.loginModalReason === 'attach'" x-cloak>✅ {{ __('order_form.modal_subtitle_attach') }}</p>
        </div>
        <div class="p-8" x-data="{ showPassword: false }">
            {{-- Error --}}
            <div class="py-3 px-4 rounded-lg mb-5 font-medium text-sm hidden bg-red-100 text-red-900 border border-red-200"
                 :class="{ '!block': $wire.modalError }"
                 x-show="$wire.modalError">
                ❌ <span x-text="$wire.modalError"></span>
            </div>

            {{-- Step: Email --}}
            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'email' }"
                  x-show="$wire.modalStep === 'email'"
                  @submit.prevent="$wire.checkModalEmail()">
                <div class="mb-5">
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('order_form.modal_enter_email') }}</label>
                    <input type="email" wire:model="modalEmail" required autocomplete="email"
                           class="order-form-input w-full px-4 py-3 border border-orange-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10"
                           placeholder="{{ __('Email') }}">
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors">
                    {{ __('Continue') }}
                </button>
            </form>

            {{-- Step: Login --}}
            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'login' }"
                  x-show="$wire.modalStep === 'login'" x-cloak
                  @submit.prevent="$wire.loginFromModal()">
                <div class="mb-5">
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('order_form.welcome_back') }}</label>
                    <div class="text-sm text-slate-500 mb-2.5">
                        <strong x-text="$wire.modalEmail"></strong>
                        <a href="#" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', '')"
                           class="ms-2.5 text-primary-500 font-medium no-underline hover:text-primary-600 hover:underline">
                            {{ __('Change') }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="modalPassword" required autocomplete="current-password"
                               class="order-form-input flex-1 w-full px-4 py-3 border border-orange-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10">
                        <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"
                                class="shrink-0 py-2 px-3 text-xs text-slate-500 bg-slate-100 border-none rounded-lg cursor-pointer">
                            <span x-text="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"></span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors">
                    {{ __('Log in') }}
                </button>
                <div class="text-center mt-4">
                    <a href="#" class="text-primary-500 font-medium text-sm no-underline hover:text-primary-600 hover:underline" @click.prevent="$wire.set('modalStep', 'reset')">{{ __('Forgot password?') }}</a>
                </div>
            </form>

            {{-- Step: Register --}}
            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'register' }"
                  x-show="$wire.modalStep === 'register'" x-cloak
                  @submit.prevent="$wire.registerFromModal()">
                <div class="mb-5">
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('order_form.no_account') }}</label>
                    <div class="text-sm text-slate-500 mb-2.5">
                        <strong x-text="$wire.modalEmail"></strong>
                        <a href="#" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', '')"
                           class="ms-2.5 text-primary-500 font-medium no-underline hover:text-primary-600 hover:underline">
                            {{ __('Change') }}
                        </a>
                    </div>
                    <p class="text-sm text-slate-500 my-2.5">{{ __('order_form.password_create_hint') }}</p>
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('Password') }}</label>
                    <div class="flex items-center gap-2">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="modalPassword" required autocomplete="new-password" minlength="4"
                               class="order-form-input flex-1 w-full px-4 py-3 border border-orange-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10">
                        <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"
                                class="shrink-0 py-2 px-3 text-xs text-slate-500 bg-slate-100 border-none rounded-lg cursor-pointer">
                            <span x-text="showPassword ? '{{ __('order_form.hide_password') }}' : '{{ __('order_form.show_password') }}'"></span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors">
                    {{ __('Create account and continue') }}
                </button>
            </form>

            {{-- Step: Reset --}}
            <form class="order-modal-form" :class="{ 'active': $wire.modalStep === 'reset' }"
                  x-show="$wire.modalStep === 'reset'" x-cloak
                  @submit.prevent="$wire.sendModalResetLink()">
                <div class="mb-5">
                    <p class="text-sm text-slate-600 mb-3">{{ __('order_form.reset_desc') }}</p>
                    <input type="email" wire:model="modalEmail" required autocomplete="email"
                           class="order-form-input w-full px-4 py-3 border border-orange-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10"
                           placeholder="{{ __('Email') }}">
                    @if ($modalError && $modalStep === 'reset')
                        <p class="text-red-500 text-xs mt-1">{{ $modalError }}</p>
                    @endif
                    @if ($modalSuccess)
                        <p class="text-green-600 text-xs mt-1">{{ $modalSuccess }}</p>
                    @endif
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="sendModalResetLink">{{ __('order_form.reset_send_link') }}</span>
                    <span wire:loading wire:target="sendModalResetLink">...</span>
                </button>
                <div class="text-center mt-4">
                    <a href="#" class="text-primary-500 font-medium text-sm no-underline hover:text-primary-600 hover:underline" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', ''); $wire.set('modalSuccess', '')">{{ __('Return') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
@endif
</div>

@push('scripts')
<script>
function newOrderForm(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes) {
    maxImagesPerItem = maxImagesPerItem || 3;
    maxImagesPerOrder = maxImagesPerOrder || 10;
    msgMaxPerItem = msgMaxPerItem || 'Maximum :max images per product.';
    msgMaxOrder = msgMaxOrder || 'Maximum :max images per order. Remove some to submit.';
    testOptions = testOptions || { colors: ['White / Blue if unavailable', 'Black / Gray if unavailable', 'Navy / Blue', 'Red / Maroon', 'Beige / White'], notes: ['Same as picture', 'Please send photo when it arrives', 'Exact match to image', 'I want image when it arrives', 'As shown in listing'] };
    const cs = commissionSettings || { threshold: 500, below_type: 'flat', below_value: 50, above_type: 'percent', above_value: 8 };
    function calcCommission(subtotalSar) {
        if (subtotalSar <= 0) return 0;
        const isAbove = subtotalSar >= (cs.threshold || 500);
        if (isAbove) {
            return cs.above_type === 'percent' ? subtotalSar * (cs.above_value / 100) : cs.above_value;
        }
        return cs.below_type === 'percent' ? subtotalSar * (cs.below_value / 100) : cs.below_value;
    }
    return {
        items: [],
        orderNotes: '',
        rates,
        currencyList,
        maxProducts,
        defaultCurrency,
        isLoggedIn,
        maxImagesPerItem,
        maxImagesPerOrder,
        msgMaxPerItem,
        msgMaxOrder,
        maxCharsMsg: @js(__('order_form.max_2000_chars')),
        testOptions,
        commissionSettings: cs,
        calcCommission,
        allowedMimeTypes: Array.isArray(allowedMimeTypes) && allowedMimeTypes.length > 0 ? allowedMimeTypes : ['image/jpeg','image/png','image/gif','image/webp','image/bmp','image/tiff','image/heic','application/pdf','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        maxFileSizeBytes: maxFileSizeBytes > 0 ? maxFileSizeBytes : (2 * 1024 * 1024),
        tipsOpen: false,
        tipsHidden: false,
        zoomedImage: null,
        totalSar: 0,
        filledCount: 0,
        submitting: false,
        openCurrencyRow: null,
        init() {
            this.checkTipsHidden();
            if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
                const isMobile = window.innerWidth < 1024;
                this.items = initialItems.map((d, i) => ({
                    url: d.url || '', qty: (d.qty || '1').toString(), color: d.color || '', size: d.size || '',
                    price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: isMobile ? (i === 0) : true, _focused: false, _showOptional: false,
                    _files: []
                }));
                this.orderNotes = initialOrderNotes || '';
            } else if (!this.loadDraft()) {
                const count = window.innerWidth >= 1024 ? 5 : 1;
                for (let i = 0; i < count; i++) this.items.push(this.emptyItem());
            }
            this.calcTotals();

            window.addEventListener('beforeunload', (e) => {
                if (this.submitting || !this.hasUnsavedData()) return;
                @if (config('app.env') === 'local')
                return;
                @endif
                e.preventDefault();
            });
        },

        hasUnsavedData() {
            return this.items.some(i =>
                (i.url || '').trim() ||
                (i.color || '').trim() ||
                (i.size || '').trim() ||
                (i.notes || '').trim() ||
                (parseFloat(i.price) > 0)
            ) || (this.orderNotes || '').trim();
        },

        emptyItem(cur) {
            return {
                url: '', qty: '1', color: '', size: '', price: '',
                currency: cur || this.defaultCurrency, notes: '',
                _expanded: true, _focused: false, _showOptional: false,
                _files: []
            };
        },

        totalFileCount() {
            return this.items.reduce((sum, i) => sum + (i._files ? i._files.length : 0), 0);
        },

        addProduct() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', @js(__('order_form.max_products', ['max' => $maxProducts])));
                return;
            }
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;

            if (window.innerWidth < 1024) {
                const open = this.items.findIndex(i => i._expanded);
                if (open !== -1) {
                    this.items[open]._expanded = false;
                    if (open === 0 && this.items.length === 1) {
                        this.showNotify('success', '{{ __('order_form.item_saved_collapsed_tip') }}', 10000);
                    } else {
                        this.showNotify('success', '{{ __('order_form.item_minimized_prefix') }} ' + (open + 1) + ' {{ __('order_form.item_minimized_suffix') }}');
                    }
                }
            }

            this.items.push(this.emptyItem(lastCur));
            this.saveDraft();
            this.$nextTick(() => {
                setTimeout(() => {
                    if (window.innerWidth >= 1024 && this.$refs.tableScrollContainer) {
                        const el = this.$refs.tableScrollContainer;
                        el.scrollTop = el.scrollHeight - el.clientHeight;
                    } else {
                        const cards = document.querySelectorAll('#items-container > div');
                        if (cards.length >= 3) {
                            cards[cards.length - 3].scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else if (cards.length >= 2) {
                            cards[cards.length - 2].scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            const last = cards[cards.length - 1];
                            if (last) last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                }, 150);
            });
        },

        addFiveTestItems() {
            const urls = [
                'https://www.amazon.com/dp/B0BSHF7LLL',
                'https://www.ebay.com/itm/' + Math.floor(100000000 + Math.random() * 900000000),
                'https://www.walmart.com/ip/' + Math.floor(100000 + Math.random() * 900000),
                'https://www.target.com/p/product-' + Math.floor(100 + Math.random() * 900),
                'https://www.aliexpress.com/item/' + Math.floor(1000000000 + Math.random() * 9000000000) + '.html',
            ];
            const sizes = this.testOptions?.sizes || ['S', 'M', 'L', 'XL', 'US 8', 'US 10', 'One Size'];
            const currencies = ['USD', 'EUR', 'GBP'];
            const colors = this.testOptions?.colors || ['White / Blue if unavailable', 'Black / Gray if unavailable', 'Navy / Blue', 'Red / Maroon', 'Beige / White'];
            const notes = this.testOptions?.notes || ['Same as picture', 'Please send photo when it arrives', 'Exact match to image', 'I want image when it arrives', 'As shown in listing'];
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;
            const isEmpty = (item) => !(item.url || '').trim() && !(item.color || '').trim() && !(item.size || '').trim() && !parseFloat(item.price) && !(item.notes || '').trim();
            for (let i = 0; i < 5; i++) {
                const cur = currencies[i % currencies.length] || lastCur;
                const testData = {
                    url: urls[i],
                    qty: String(Math.floor(Math.random() * 2) + 1),
                    color: colors[i % colors.length],
                    size: sizes[Math.floor(Math.random() * sizes.length)],
                    price: String((Math.random() * 80 + 15).toFixed(2)),
                    currency: cur,
                    notes: notes[i % notes.length],
                    _expanded: true, _focused: false, _showOptional: false,
                    _files: []
                };
                const emptyIdx = this.items.findIndex(isEmpty);
                if (emptyIdx !== -1) {
                    Object.assign(this.items[emptyIdx], testData);
                } else if (this.items.length < this.maxProducts) {
                    this.items.push(testData);
                } else break;
            }
            this.calcTotals();
            this.saveDraft();
            this.showNotify('success', '{{ __('order.dev_5_items_added') }}');
        },

        removeItem(idx) {
            this.$wire.shiftFileIndex(idx);
            this.items.splice(idx, 1);
            this.calcTotals();
            this.saveDraft();
        },

        toggleItem(idx) {
            this.items[idx]._expanded = !this.items[idx]._expanded;
        },

        getItemSite(item) {
            const url = (item.url || '').trim();
            if (!url) return '';
            let name = '';
            try {
                const host = new URL(url.startsWith('http') ? url : 'https://' + url).hostname.replace('www.', '');
                name = (host.split('.')[0] || host).replace(/^./, c => c.toUpperCase());
            } catch { name = url.substring(0, 6); }
            name = (name.length > 6 ? name.substring(0, 6) : name) + '..';
            return '(' + name + ')';
        },

        itemSummary(idx, expanded) {
            const item = this.items[idx];
            const num = idx + 1;
            if (expanded) return '{{ __('order_form.product_num') }} ' + num;
            const url = (item.url || '').trim();
            const short = this.getItemSite(item);
            return '{{ __('order_form.product_num') }} ' + num + (short ? '  ·  ' + short : '');
        },

        onCurrencyChange(idx) {
            if (this.items[idx].currency === 'OTHER') {
                this.showNotify('success', '{{ __('order_form.other_currency_note') }}');
            }
        },

        convertArabicNums(e) {
            const ar = '٠١٢٣٤٥٦٧٨٩';
            let v = e.target.value;
            let changed = false;
            v = v.replace(/[٠-٩]/g, (d) => {
                const idx = ar.indexOf(d);
                if (idx >= 0) { changed = true; return String(idx); }
                return d;
            });
            if (changed) e.target.value = v;
        },

        calcTotals() {
            let subtotal = 0;
            let filled = 0;
            this.items.forEach(item => {
                if (item.url.trim()) filled++;
                const q = Math.max(1, parseFloat(item.qty) || 1);
                const p = parseFloat(item.price) || 0;
                const r = this.rates[item.currency] || 0;
                if (p > 0 && r > 0) subtotal += (p * q * r);
            });
            const commission = this.calcCommission ? this.calcCommission(subtotal) : 0;
            this.totalSar = Math.round(subtotal + commission);
            this.filledCount = filled;
        },

        productCountText() {
            return '{{ __('order_form.products_count') }}: ' + this.filledCount;
        },

        totalText() {
            return '{{ __('order_form.products_value') }}: ' + this.totalSar.toLocaleString('en-US') + ' {{ __('SAR') }}';
        },

        saveDraft() {
            const data = this.items.map(i => ({
                url: i.url, qty: i.qty, color: i.color, size: i.size,
                price: i.price, currency: i.currency, notes: i.notes
            }));
            try {
                localStorage.setItem('wz_order_form_draft', JSON.stringify(data));
                localStorage.setItem('wz_order_form_notes', this.orderNotes);
            } catch {}
        },

        loadDraft() {
            try {
                let raw = localStorage.getItem('wz_order_form_draft');
                let notes = localStorage.getItem('wz_order_form_notes');
                if (!raw && localStorage.getItem('wz_opus46_draft')) {
                    raw = localStorage.getItem('wz_opus46_draft');
                    notes = localStorage.getItem('wz_opus46_notes');
                    localStorage.removeItem('wz_opus46_draft');
                    localStorage.removeItem('wz_opus46_notes');
                    if (raw) localStorage.setItem('wz_order_form_draft', raw);
                    if (notes) localStorage.setItem('wz_order_form_notes', notes);
                }
                if (notes) this.orderNotes = notes;
                if (!raw) return false;
                const data = JSON.parse(raw);
                if (!Array.isArray(data) || data.length === 0) return false;
                this.items = data.map(d => ({
                    url: d.url || '', qty: d.qty || '1', color: d.color || '',
                    size: d.size || '', price: d.price || '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: false, _focused: false, _showOptional: false,
                    _files: []
                }));
                if (this.items.length > 0) this.items[0]._expanded = true;
                return true;
            } catch { return false; }
        },

        clearDraft() {
            try {
                localStorage.removeItem('wz_order_form_draft');
                localStorage.removeItem('wz_order_form_notes');
                localStorage.removeItem('wz_opus46_draft');
                localStorage.removeItem('wz_opus46_notes');
            } catch {}
        },

        resetAll() {
            if (!confirm('{{ __('order_form.reset_confirm') }}')) return;
            this.items = [];
            this.orderNotes = '';
            this.clearDraft();
            const count = window.innerWidth >= 1024 ? 5 : 1;
            for (let i = 0; i < count; i++) this.items.push(this.emptyItem());
            this.calcTotals();
            this.showNotify('success', '{{ __('order_form.cleared') }}');
        },

        handleFileSelect(e, idx) {
            const rawFiles = Array.from(e.target.files || []);
            if (!rawFiles.length) return;

            let files = this.items[idx]._files || [];
            if (files.length >= this.maxImagesPerItem) {
                this.showNotify('error', this.msgMaxPerItem);
                e.target.value = '';
                return;
            }
            if (this.totalFileCount() >= this.maxImagesPerOrder) {
                this.showNotify('error', this.msgMaxOrder);
                e.target.value = '';
                return;
            }

            const allowed = this.allowedMimeTypes || [];
            const maxSize = this.maxFileSizeBytes || (2 * 1024 * 1024);
            const toAdd = [];
            let skippedInvalid = 0;
            let canAddItem = this.maxImagesPerItem - files.length;
            let canAddOrder = this.maxImagesPerOrder - this.totalFileCount();

            if (rawFiles.length > canAddItem || rawFiles.length > canAddOrder) {
                this.showNotify('error', '{{ __('order_form.too_many_selected') }}'.replace(':max', this.maxImagesPerItem).replace(':avail', Math.min(canAddItem, canAddOrder)));
                e.target.value = '';
                return;
            }

            for (const file of rawFiles) {
                if (!allowed.includes(file.type)) {
                    skippedInvalid++;
                    continue;
                }
                if (file.size > maxSize) {
                    skippedInvalid++;
                    continue;
                }
                toAdd.push(file);
            }

            if (skippedInvalid > 0) {
                this.showNotify('error', skippedInvalid === 1 ? '{{ __('order_form.invalid_type') }}' : '{{ __('order_form.files_skipped_invalid') }}'.replace(':n', skippedInvalid));
            }

            if (!toAdd.length) {
                e.target.value = '';
                return;
            }

            if (!this.items[idx]._files) this.items[idx]._files = [];
            const totalAdding = toAdd.length;
            let completed = 0;
            const uploadOne = (file, fileIdx) => {
                let fileType = 'img';
                if (file.type === 'application/pdf') fileType = 'pdf';
                else if (file.type.includes('excel') || file.type.includes('spreadsheetml') || file.type === 'text/csv') fileType = 'xls';
                else if (file.type.includes('word') || file.type === 'application/msword') fileType = 'doc';
                else if (file.type.startsWith('image/')) fileType = 'img';

                const entry = { file, preview: null, fileType, fileName: file.name, uploadProgress: 0 };
                this.items[idx]._files.push(entry);
                if (fileType === 'img') {
                    const entryIdx = this.items[idx]._files.length - 1;
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        this.items[idx]._files[entryIdx].preview = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }

                this.$wire.upload(
                    'itemFiles.' + idx + '.' + fileIdx,
                    file,
                    () => {
                        entry.uploadProgress = null;
                        completed++;
                        if (completed === totalAdding) {
                            this.showNotify('success', totalAdding > 1 ? '{{ __('order_form.files_attached') }}'.replace(':n', String(totalAdding)) : '{{ __('order_form.file_attached') }}');
                        }
                    },
                    () => {
                        entry.uploadProgress = null;
                        this.items[idx]._files = this.items[idx]._files.filter(f => f !== entry);
                        this.showNotify('error', '{{ __('order_form.upload_failed') }}');
                    },
                    (event) => {
                        const p = event.detail.progress;
                        entry.uploadProgress = p;
                        if (p >= 100) entry.uploadProgress = null;
                    }
                );
            };

            let fileIdx = files.length;
            toAdd.forEach((file) => {
                uploadOne(file, fileIdx);
                fileIdx++;
            });
            e.target.value = '';
        },

        removeFile(idx, fileIdx) {
            const files = this.items[idx]._files || [];
            if (fileIdx < 0 || fileIdx >= files.length) return;
            this.items[idx]._files.splice(fileIdx, 1);
            this.$wire.removeItemFile(idx, fileIdx);
        },

        zoomImage(src) { this.zoomedImage = src; },
        closeZoom() {
            if (this.zoomedImage && this.zoomedImage.startsWith('blob:')) {
                try { URL.revokeObjectURL(this.zoomedImage); } catch (_) {}
            }
            this.zoomedImage = null;
        },

        openFileOrZoom(f) {
            if (f.fileType === 'img') {
                const src = f.preview || (f.file ? URL.createObjectURL(f.file) : null);
                if (src) this.$dispatch('zoom-image', src);
            } else if ((f.fileType === 'pdf' || f.fileType === 'xls' || f.fileType === 'doc') && f.file) {
                window.open(URL.createObjectURL(f.file), '_blank');
            }
        },

        async submitOrder() {
            if (this.submitting) return;
            const cleanItems = this.items.map(i => ({
                url: i.url, qty: i.qty, color: i.color, size: i.size,
                price: i.price, currency: i.currency, notes: i.notes
            }));
            this.submitting = true;
            try {
                await this.$wire.set('items', cleanItems);
                await this.$wire.set('orderNotes', this.orderNotes);
                await this.$wire.submitOrder();
                if (this.$wire.showLoginModal) {
                    this.submitting = false;
                    return;
                }
                this.clearDraft();
            } catch (_) {
                // validation errors are handled by Livewire
            } finally {
                this.submitting = false;
            }
        },

        checkTipsHidden() {
            try {
                let until = localStorage.getItem('wz_order_form_tips_until');
                if (!until) until = localStorage.getItem('wz_opus46_tips_until');
                if (until && Date.now() < parseInt(until)) this.tipsHidden = true;
                else { localStorage.removeItem('wz_order_form_tips_until'); localStorage.removeItem('wz_opus46_tips_until'); }
            } catch {}
        },

        hideTips30Days() {
            try {
                localStorage.setItem('wz_order_form_tips_until', (Date.now() + 30*24*60*60*1000).toString());
            } catch {}
            this.tipsHidden = true;
            this.showNotify('success', '{{ __('order_form.tips_hidden') }}');
        },

        showNotify(type, msg, duration) {
            const c = this.$refs.toasts;
            if (!c) return;
            const t = document.createElement('div');
            t.className = `toast ${type}`;
            const icon = type === 'error'
                ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:#ef4444;flex-shrink:0"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>'
                : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:#10b981;flex-shrink:0"><path d="M20 6L9 17l-5-5"/></svg>';
            const dur = duration ?? (type === 'error' ? 4000 : 700);
            const closeLabel = '{{ __("Close") }}';
            t.innerHTML = `${icon}<span style="flex:1">${msg}</span><button type="button" class="toast-close" aria-label="${closeLabel}">×</button>`;
            c.appendChild(t);
            const closeToast = () => {
                t.style.animation = 'toastOut 0.4s ease forwards';
                setTimeout(() => t.remove(), 400);
            };
            t.querySelector('.toast-close').addEventListener('click', (e) => { e.stopPropagation(); closeToast(); });
            t.addEventListener('click', closeToast);
            setTimeout(() => {
                if (t.parentElement) closeToast();
            }, dur);
        }
    };
}
</script>
@endpush
