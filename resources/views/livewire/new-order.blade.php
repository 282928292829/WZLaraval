{{-- /new-order — Production order form --}}

@php
    $isLoggedIn = auth()->check();
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp

{{-- ============================================================ --}}
{{-- SUCCESS SCREEN — configurable via Settings > Order Success Screen --}}
{{-- ============================================================ --}}
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
        <div class="w-14 h-14 md:w-[72px] md:h-[72px] md:mb-4 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3 animate-[successScale_0.5s_ease-out]">
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
        0.03,
        @js($currencies),
        {{ $maxProducts }},
        @js($defaultCurrency),
        {{ $isLoggedIn ? 'true' : 'false' }},
        @js($commissionSettings),
        @js(($editingOrderId || $productUrl || $duplicateFrom) ? $items : null),
        @js($editingOrderId ? $orderNotes : null)
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
    class="bg-white text-slate-800 font-[family-name:var(--font-family-arabic)]"
>

{{-- Toast Container --}}
<div x-ref="toasts" id="toast-container"></div>

<div class="max-w-7xl mx-auto p-4">
<main id="main-content">

    <h1 class="text-xl font-bold text-slate-800 mb-4">{{ $editingOrderId ? __('orders.edit_order_title', ['number' => $editingOrderNumber]) : __('Create new order') }}</h1>

    {{-- Tips Box --}}
    <section class="bg-white rounded-lg shadow-sm border-s-4 border-primary-500 mb-5 overflow-hidden" x-show="!tipsHidden" x-cloak>
        <div class="px-4 py-3 flex justify-between items-center cursor-pointer border-b border-orange-100" @click="tipsOpen = !tipsOpen">
            <h2 class="text-sm font-semibold text-slate-800 m-0">{{ __('opus46.tips_title') }}</h2>
            <span x-text="tipsOpen ? '▲' : '▼'" class="text-primary-500 text-xs"></span>
        </div>
        <div x-show="tipsOpen" x-collapse class="p-4 text-sm leading-relaxed text-slate-600">
            <ul class="list-none p-0 m-0">
                @for ($i = 1; $i <= 7; $i++)
                    <li class="mb-2.5 relative ps-[18px] before:content-['•'] before:absolute before:start-0 before:text-primary-500 before:font-bold">{{ __("opus46.tip_{$i}") }}</li>
                @endfor
            </ul>
            <div class="mt-4 pt-4 border-t border-orange-100">
                <label class="flex items-center gap-2 text-sm text-slate-500 cursor-pointer">
                    <input type="checkbox" @change="hideTips30Days()" class="cursor-pointer">
                    <span>{{ __('opus46.tips_dont_show') }}</span>
                </label>
            </div>
        </div>
    </section>

    {{-- Order Form --}}
    <div id="order-form">

        @if ($editingOrderId)
        <section class="p-3 mb-3 bg-amber-100 border border-amber-300 rounded-xl">
            <h2 class="text-base font-semibold text-amber-800 m-0">
                {{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}
            </h2>
            <p class="text-sm text-amber-700 mt-1.5 mb-0">{{ __('orders.edit_resubmit_deadline_hint') }}</p>
        </section>
        @endif

        {{-- Products Section --}}
        <section class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 mb-4">

            {{-- Desktop Table Header --}}
            <div class="order-table-header hidden lg:grid order-item-grid-desktop gap-2 p-2.5 font-bold text-xs text-slate-800 bg-orange-50 rounded-md mb-0">
                <div>{{ __('opus46.th_num') }}</div>
                <div>{{ __('opus46.th_url') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
                <div>{{ __('opus46.th_qty') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
                <div>{{ __('opus46.th_color') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
                <div>{{ __('opus46.th_size') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
                <div>{{ __('opus46.th_price') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
                <div>{{ __('opus46.th_currency') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
                <div>{{ __('opus46.th_notes') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
                <div>{{ __('opus46.th_files') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></div>
            </div>

            {{-- Items --}}
            <div id="items-container-wrapper" class="lg:overflow-x-auto lg:[-webkit-overflow-scrolling:touch]">
                <div id="items-container" class="flex flex-col gap-2.5 lg:gap-0 lg:border lg:border-orange-100 lg:rounded-lg lg:relative lg:min-w-[900px]">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="order-item-card group bg-white border border-orange-100 rounded-xl lg:rounded-none lg:border-0 lg:border-b lg:border-orange-100 overflow-hidden shadow-sm transition-all duration-150 relative scroll-mb-[150px] focus-within:shadow-md focus-within:border-primary-400/40 focus-within:-translate-y-0.5 focus-within:z-10"
                             :class="{
                                 'expanded': item._expanded,
                                 'is-valid': item.url.trim().length > 0,
                                 'is-minimized': !item._expanded,
                                 'lg:!bg-orange-50 lg:opacity-90': !item._expanded
                             }">

                            {{-- Mobile Summary Bar --}}
                            <div class="flex items-center justify-between gap-2 px-3 py-3 bg-orange-50 cursor-pointer select-none lg:hidden border-b border-orange-100"
                                 :class="{ 'bg-white border-b border-orange-100': item._expanded }"
                                 @click="toggleItem(idx)">
                                <div class="font-semibold text-sm text-slate-800 truncate flex-1 min-w-0" x-text="itemSummary(idx)"></div>
                                <div class="flex gap-2 items-center" @click.stop>
                                    <button type="button"
                                            class="inline-flex items-center justify-center py-1.5 px-2.5 rounded-md text-xs font-semibold bg-primary-500/10 text-primary-500 border border-primary-500/25 hover:bg-primary-500/20 hover:border-primary-500 transition-colors"
                                            @click="item._expanded = !item._expanded">
                                        {{ __('opus46.show_edit') }}
                                    </button>
                                    <button type="button"
                                            class="inline-flex items-center justify-center py-1.5 px-2.5 rounded-md text-xs font-semibold bg-red-100/30 text-red-600 border border-red-200 hover:bg-red-100 hover:text-red-700 transition-colors"
                                            @click="removeItem(idx)">
                                        {{ __('opus46.remove') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Item Fields Grid --}}
                            <div class="order-item-details p-3 max-lg:hidden max-lg:group-[.expanded]:grid grid-cols-6 gap-2.5 lg:grid lg:gap-2 lg:p-2.5 lg:items-center">
                                {{-- Row number (desktop only) --}}
                                <div class="hidden lg:flex items-center justify-center font-semibold text-sm text-slate-800">
                                    <span x-text="idx + 1"></span>
                                </div>

                                {{-- URL --}}
                                <div class="order-cell-url">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_url') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                    <input type="text"
                                           x-model="item.url"
                                           @blur="calcTotals(); saveDraft()"
                                           :placeholder="idx === 0 ? '{{ __('opus46.url_placeholder') }}' : ''"
                                           class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 sm:h-11 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors">
                                </div>

                                {{-- Qty --}}
                                <div class="order-cell-qty">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_qty') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                    <input type="tel"
                                           x-model="item.qty"
                                           @input="convertArabicNums($event)"
                                           @blur="calcTotals(); saveDraft()"
                                           value="1" placeholder="1"
                                           class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 sm:h-11 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors"
                                           dir="rtl">
                                </div>

                                {{-- Color --}}
                                <div class="order-cell-col">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_color') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                    <input type="text"
                                           x-model="item.color"
                                           @blur="saveDraft()"
                                           class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 sm:h-11 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors">
                                </div>

                                {{-- Size --}}
                                <div class="order-cell-siz">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_size') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                    <input type="text"
                                           x-model="item.size"
                                           @blur="saveDraft()"
                                           class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 sm:h-11 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors">
                                </div>

                                {{-- Price --}}
                                <div class="order-cell-prc">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_price') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                    <input type="text"
                                           x-model="item.price"
                                           @input="convertArabicNums($event)"
                                           @blur="calcTotals(); saveDraft()"
                                           inputmode="decimal" placeholder="{{ __('placeholder.amount') }}"
                                           class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 sm:h-11 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors">
                                </div>

                                {{-- Currency --}}
                                <div class="order-cell-cur">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_currency') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                    <select x-model="item.currency"
                                            @change="onCurrencyChange(idx)"
                                            @blur="calcTotals(); saveDraft()"
                                            class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 sm:h-11 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 px-1 text-xs sm:text-sm">
                                        <template x-for="(cur, code) in currencyList" :key="code">
                                            <option :value="code" x-text="cur.label" :selected="code === item.currency"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Optional Section (notes + file) always visible --}}
                                <div class="order-optional-section">

                                    <div class="order-cell-not">
                                        <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_notes') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                        <input type="text"
                                               x-model="item.notes"
                                               @blur="saveDraft()"
                                               :placeholder="idx === 0 ? '{{ __('opus46.notes_placeholder') }}' : ''"
                                               class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 sm:h-11 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors">
                                    </div>

                                    <div class="order-upload-container flex flex-col gap-1 lg:flex-row lg:items-center lg:gap-2 lg:mt-0">
                                        <span class="block text-xs text-slate-500 mb-0.5 font-medium lg:hidden">{{ __('opus46.th_files') }} <span class="text-[0.65rem] text-slate-400 font-normal">({{ __('opus46.optional') }})</span></span>
                                        <div class="flex items-center gap-2.5 lg:shrink-0">
                                            <template x-if="!item._file">
                                                <button type="button"
                                                        class="border border-dashed border-orange-100 text-slate-500 bg-orange-50 py-2 px-3 rounded-md text-xs font-medium cursor-pointer inline-flex items-center justify-center gap-1.5 hover:border-primary-500 hover:bg-orange-50 hover:text-primary-500 transition-colors lg:py-1.5 lg:px-2 lg:text-[0.75rem]"
                                                        @click.stop="triggerUpload(idx)"
                                                        title="{{ __('opus46.attach') }}">
                                                    <span>📎 {{ __('opus46.attach') }}</span>
                                                </button>
                                            </template>
                                            <template x-if="item._file">
                                                <div class="flex flex-wrap overflow-x-auto gap-2">
                                                    <div class="relative w-11 h-11 shrink-0 rounded-md overflow-hidden border border-orange-100">
                                                        <template x-if="item._preview">
                                                            <img :src="item._preview" class="w-full h-full object-cover">
                                                        </template>
                                                        <template x-if="!item._preview && item._fileType === 'pdf'">
                                                            <div class="w-full h-full flex items-center justify-center bg-red-100 text-red-500 text-[10px] font-bold">PDF</div>
                                                        </template>
                                                        <template x-if="!item._preview && item._fileType === 'xls'">
                                                            <div class="w-full h-full flex items-center justify-center bg-green-100 text-green-600 text-[10px] font-bold">XLS</div>
                                                        </template>
                                                        <button type="button"
                                                                class="absolute top-0 start-0 w-4 h-4 bg-red-500/90 text-white border-none rounded-full text-[10px] cursor-pointer flex items-center justify-center"
                                                                @click="removeFile(idx)">×</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <template x-if="item._uploadProgress !== null">
                                            <div class="w-full h-1 bg-orange-50 rounded-sm overflow-hidden mt-1 lg:mt-0 lg:min-w-[60px] lg:flex-1">
                                                <div class="h-full bg-primary-500 rounded-sm transition-[width] duration-200" :style="'width:' + item._uploadProgress + '%'"></div>
                                            </div>
                                        </template>
                                        <div class="text-start text-[0.7rem] text-stone-400 lg:hidden">{{ __('opus46.file_info') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Add Product Button --}}
            <button type="button" @click="addProduct()"
                    class="w-full mt-4 py-3 inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary-500/10 to-primary-400/5 text-primary-500 border-2 border-primary-500/25 font-semibold rounded-md text-sm sm:text-base sm:py-3 sm:px-4 min-h-11 sm:min-h-11 hover:from-primary-500/20 hover:to-primary-400/10 hover:border-primary-500 hover:-translate-y-px transition-all">
                + {{ __('opus46.add_product') }}
            </button>

            </section>

        {{-- General Notes --}}
        <section class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 mb-4">
            <h3 class="text-base mb-2.5">{{ __('opus46.general_notes') }}</h3>
            <textarea x-model="orderNotes"
                      @input.debounce.500ms="saveDraft()"
                      wire:model.blur="orderNotes"
                      placeholder="{{ __('opus46.general_notes_ph') }}"
                      class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white min-h-20 resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors sm:text-base"></textarea>
        </section>

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
                <span x-show="!submitting">{{ __('opus46.confirm_order') }}</span>
                @endif
                <span x-show="submitting" x-cloak>{{ __('opus46.submitting') }}...</span>
            </button>
        </div>

        {{-- Reset Link --}}
        <div class="text-start mt-2.5 ps-5 flex flex-col gap-1.5">
            <button type="button" @click="resetAll()"
                    class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                {{ __('opus46.reset_all') }}
            </button>
            @if (config('app.env') === 'local')
            <button type="button" @click="addFourTestItems()"
                    class="bg-transparent border-none text-slate-400 text-xs underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                🧪 {{ __('order.dev_add_4_test_items') }}
            </button>
            @endif
        </div>
    </div>
</main>
</div>

{{-- Hidden File Input --}}
<input type="file" x-ref="fileInput" class="hidden"
       accept="image/*,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
       @change="handleFileSelect($event)">

{{-- Login Modal --}}
<div class="order-login-modal-overlay"
     :class="{ 'show': $wire.showLoginModal }"
     @click.self="$wire.closeModal()">
    <div class="order-login-modal">
        <div class="py-8 px-8 pb-5 border-b border-orange-100 relative">
            <button type="button" class="absolute top-5 start-5 w-8 h-8 flex items-center justify-center rounded-full text-slate-400 text-3xl border-none bg-transparent cursor-pointer hover:bg-black/5 hover:text-slate-800 transition-colors" @click="$wire.closeModal()">&times;</button>
            <h2 class="text-2xl font-bold text-slate-800 mb-2.5 text-center">
                <span x-show="$wire.loginModalReason === 'submit'">{{ __('opus46.modal_title') }}</span>
                <span x-show="$wire.loginModalReason === 'attach'" x-cloak>{{ __('opus46.modal_title_attach') }}</span>
            </h2>
            <p class="text-sm text-slate-500 text-center m-0" x-show="$wire.loginModalReason === 'submit'">✅ {{ __('opus46.data_saved') }} {{ __('opus46.modal_email_hint') }}</p>
            <p class="text-sm text-slate-500 text-center m-0" x-show="$wire.loginModalReason === 'attach'" x-cloak>✅ {{ __('opus46.modal_subtitle_attach') }}</p>
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
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('opus46.modal_enter_email') }}</label>
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
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('opus46.welcome_back') }}</label>
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
                                :aria-label="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"
                                class="shrink-0 py-2 px-3 text-xs text-slate-500 bg-slate-100 border-none rounded-lg cursor-pointer">
                            <span x-text="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"></span>
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
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('opus46.no_account') }}</label>
                    <div class="text-sm text-slate-500 mb-2.5">
                        <strong x-text="$wire.modalEmail"></strong>
                        <a href="#" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', '')"
                           class="ms-2.5 text-primary-500 font-medium no-underline hover:text-primary-600 hover:underline">
                            {{ __('Change') }}
                        </a>
                    </div>
                    <p class="text-sm text-slate-500 my-2.5">{{ __('opus46.password_create_hint') }}</p>
                    <label class="block font-semibold text-sm text-slate-800 mb-2">{{ __('Password') }}</label>
                    <div class="flex items-center gap-2">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="modalPassword" required autocomplete="new-password" minlength="4"
                               class="order-form-input flex-1 w-full px-4 py-3 border border-orange-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-3 focus:ring-primary-500/10">
                        <button type="button" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"
                                class="shrink-0 py-2 px-3 text-xs text-slate-500 bg-slate-100 border-none rounded-lg cursor-pointer">
                            <span x-text="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"></span>
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
                    <p class="text-sm text-slate-600 mb-3">{{ __('opus46.reset_desc') }}</p>
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
                    <span wire:loading.remove wire:target="sendModalResetLink">{{ __('opus46.reset_send_link') }}</span>
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

@push('scripts')
<script>
function newOrderForm(rates, margin, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes) {
    return {
        items: [],
        orderNotes: '',
        rates,
        margin,
        currencyList,
        maxProducts,
        defaultCurrency,
        isLoggedIn,
        commissionSettings: commissionSettings || { threshold: 500, below_type: 'flat', below_value: 50, above_type: 'percent', above_value: 8 },
        tipsOpen: false,
        tipsHidden: false,
        totalSar: 0,
        filledCount: 0,
        submitting: false,
        _uploadIdx: null,

        init() {
            this.checkTipsHidden();
            if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
                const isMobile = window.innerWidth < 1024;
                this.items = initialItems.map((d, i) => ({
                    url: d.url || '', qty: (d.qty || '1').toString(), color: d.color || '', size: d.size || '',
                    price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: isMobile ? (i === 0) : true, _focused: false, _showOptional: false,
                    _file: null, _preview: null, _fileType: null, _fileName: null, _uploadProgress: null
                }));
                this.orderNotes = initialOrderNotes || '';
            } else if (!this.loadDraft()) {
                const count = window.innerWidth >= 1024 ? 5 : 1;
                for (let i = 0; i < count; i++) this.items.push(this.emptyItem());
            }
            this.calcTotals();

            window.addEventListener('beforeunload', (e) => {
                if (this.submitting || !this.hasUnsavedData()) return;
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
                _file: null, _preview: null, _fileType: null, _fileName: null,
                _uploadProgress: null
            };
        },

        addProduct() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', this.maxProducts + ' {{ __('opus46.max_limit_suffix') }}');
                return;
            }
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;

            if (window.innerWidth < 1024) {
                const open = this.items.findIndex(i => i._expanded);
                if (open !== -1) {
                    this.items[open]._expanded = false;
                    if (open === 0 && this.items.length === 1) {
                        this.showNotify('success', '{{ __('opus46.item_saved_collapsed_tip') }}', 10000);
                    } else {
                        this.showNotify('success', '{{ __('opus46.item_minimized_prefix') }} ' + (open + 1) + ' {{ __('opus46.item_minimized_suffix') }}');
                    }
                }
            }

            this.items.push(this.emptyItem(lastCur));
            this.saveDraft();
            this.$nextTick(() => {
                setTimeout(() => {
                    const cards = document.querySelectorAll('#items-container > div');
                    if (window.innerWidth < 1024 && cards.length >= 3) {
                        cards[cards.length - 3].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else if (window.innerWidth < 1024 && cards.length >= 2) {
                        cards[cards.length - 2].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        const last = cards[cards.length - 1];
                        if (last) last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }, 150);
            });
        },

        addFourTestItems() {
            const urls = [
                'https://www.amazon.com/dp/B0BSHF7LLL',
                'https://www.ebay.com/itm/' + Math.floor(100000000 + Math.random() * 900000000),
                'https://www.walmart.com/ip/' + Math.floor(100000 + Math.random() * 900000),
                'https://www.target.com/p/product-' + Math.floor(100 + Math.random() * 900),
            ];
            const colors = ['Red', 'Blue', 'Black', 'White', 'Navy', 'Gray', 'Green'];
            const sizes = ['S', 'M', 'L', 'XL', 'US 8', 'US 10', 'One Size'];
            const currencies = ['USD', 'EUR', 'GBP'];
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;
            for (let i = 0; i < 4; i++) {
                if (this.items.length >= this.maxProducts) break;
                const cur = currencies[i % currencies.length] || lastCur;
                this.items.push({
                    url: urls[i],
                    qty: String(Math.floor(Math.random() * 2) + 1),
                    color: colors[Math.floor(Math.random() * colors.length)],
                    size: sizes[Math.floor(Math.random() * sizes.length)],
                    price: String((Math.random() * 80 + 15).toFixed(2)),
                    currency: cur,
                    notes: 'Test item ' + (i + 1),
                    _expanded: true, _focused: false, _showOptional: false,
                    _file: null, _preview: null, _fileType: null, _fileName: null, _uploadProgress: null
                });
            }
            this.calcTotals();
            this.saveDraft();
            this.showNotify('success', '{{ __('order.dev_4_items_added') }}');
        },

        removeItem(idx) {
            this.$wire.shiftFileIndex(idx);
            this.items.splice(idx, 1);
            if (this.items.length === 0) this.items.push(this.emptyItem());
            this.calcTotals();
            this.saveDraft();
        },

        toggleItem(idx) {
            this.items[idx]._expanded = !this.items[idx]._expanded;
        },

        itemSummary(idx) {
            const item = this.items[idx];
            const num = idx + 1;
            const url = (item.url || '').trim();
            if (!url) return '{{ __('opus46.product_num') }} ' + num;
            try {
                const host = new URL(url.startsWith('http') ? url : 'https://' + url).hostname.replace('www.', '');
                return '{{ __('opus46.product_num') }} ' + num + ': ' + host;
            } catch { return '{{ __('opus46.product_num') }} ' + num + ': ' + url.substring(0, 30); }
        },

        onCurrencyChange(idx) {
            if (this.items[idx].currency === 'OTHER') {
                this.showNotify('success', '{{ __('opus46.other_currency_note') }}');
            }
        },

        convertArabicNums(e) {
            const ar = '٠١٢٣٤٥٦٧٨٩';
            let v = e.target.value;
            let changed = false;
            v = v.replace(/[٠-٩]/g, d => { changed = true; return ar.indexOf(d); });
            if (changed) e.target.value = v;
        },

        calcTotals() {
            let total = 0;
            let filled = 0;
            this.items.forEach(item => {
                if (item.url.trim()) filled++;
                const q = Math.max(1, parseFloat(item.qty) || 1);
                const p = parseFloat(item.price) || 0;
                const r = this.rates[item.currency] || 0;
                if (p > 0 && r > 0) total += (p * q * r);
            });
            this.totalSar = Math.floor(total * (1 + this.margin));
            this.filledCount = filled;
        },

        productCountText() {
            return '{{ __('opus46.products_count') }}: ' + this.filledCount;
        },

        totalText() {
            return '{{ __('opus46.products_value') }}: ' + this.totalSar.toLocaleString('en-US') + ' {{ __('SAR') }}';
        },

        saveDraft() {
            const data = this.items.map(i => ({
                url: i.url, qty: i.qty, color: i.color, size: i.size,
                price: i.price, currency: i.currency, notes: i.notes
            }));
            try {
                localStorage.setItem('wz_opus46_draft', JSON.stringify(data));
                localStorage.setItem('wz_opus46_notes', this.orderNotes);
            } catch {}
        },

        loadDraft() {
            try {
                const raw = localStorage.getItem('wz_opus46_draft');
                const notes = localStorage.getItem('wz_opus46_notes');
                if (notes) this.orderNotes = notes;
                if (!raw) return false;
                const data = JSON.parse(raw);
                if (!Array.isArray(data) || data.length === 0) return false;
                this.items = data.map(d => ({
                    url: d.url || '', qty: d.qty || '1', color: d.color || '',
                    size: d.size || '', price: d.price || '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: false, _focused: false, _showOptional: false,
                    _file: null, _preview: null, _fileType: null, _fileName: null,
                    _uploadProgress: null
                }));
                if (this.items.length > 0) this.items[0]._expanded = true;
                return true;
            } catch { return false; }
        },

        clearDraft() {
            try {
                localStorage.removeItem('wz_opus46_draft');
                localStorage.removeItem('wz_opus46_notes');
            } catch {}
        },

        resetAll() {
            if (!confirm('{{ __('opus46.reset_confirm') }}')) return;
            this.items = [];
            this.orderNotes = '';
            this.clearDraft();
            const count = window.innerWidth >= 1024 ? 5 : 1;
            for (let i = 0; i < count; i++) this.items.push(this.emptyItem());
            this.calcTotals();
            this.showNotify('success', '{{ __('opus46.cleared') }}');
        },

        triggerUpload(idx) {
            if (!this.isLoggedIn) {
                this.$wire.openLoginModalForAttach();
                return;
            }
            if (this.items[idx]._file) {
                this.showNotify('error', '{{ __('opus46.one_file') }}');
                return;
            }
            const totalFiles = this.items.filter(i => i._file).length;
            if (totalFiles >= 10) {
                this.showNotify('error', '{{ __('opus46.max_files') }}');
                return;
            }
            this._uploadIdx = idx;
            this.$refs.fileInput.click();
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file || this._uploadIdx === null) return;
            const idx = this._uploadIdx;

            const allowed = ['image/jpeg','image/png','image/gif','application/pdf',
                'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!allowed.includes(file.type)) {
                this.showNotify('error', '{{ __('opus46.invalid_type') }}');
                e.target.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.showNotify('error', '{{ __('opus46.file_too_large') }}');
                e.target.value = '';
                return;
            }

            this.items[idx]._file = file;
            this.items[idx]._fileName = file.name;

            if (file.type === 'application/pdf') {
                this.items[idx]._fileType = 'pdf';
            } else if (file.type.includes('excel') || file.type.includes('spreadsheetml')) {
                this.items[idx]._fileType = 'xls';
            } else {
                this.items[idx]._fileType = 'img';
                const reader = new FileReader();
                reader.onload = (ev) => { this.items[idx]._preview = ev.target.result; };
                reader.readAsDataURL(file);
            }

            this.items[idx]._uploadProgress = 0;
            this.$wire.upload(
                'itemFiles.' + idx,
                file,
                () => {
                    this.items[idx]._uploadProgress = null;
                    this.showNotify('success', '{{ __('opus46.file_attached') }}');
                },
                () => {
                    this.items[idx]._uploadProgress = null;
                    this.showNotify('error', '{{ __('opus46.upload_failed') }}');
                },
                (event) => {
                    this.items[idx]._uploadProgress = event.detail.progress;
                }
            );
            e.target.value = '';
        },

        removeFile(idx) {
            this.items[idx]._file = null;
            this.items[idx]._preview = null;
            this.items[idx]._fileType = null;
            this.items[idx]._fileName = null;
            this.$wire.set('itemFiles.' + idx, null);
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
                const until = localStorage.getItem('wz_opus46_tips_until');
                if (until && Date.now() < parseInt(until)) this.tipsHidden = true;
                else localStorage.removeItem('wz_opus46_tips_until');
            } catch {}
        },

        hideTips30Days() {
            try {
                localStorage.setItem('wz_opus46_tips_until', (Date.now() + 30*24*60*60*1000).toString());
            } catch {}
            this.tipsHidden = true;
            this.showNotify('success', '{{ __('opus46.tips_hidden') }}');
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
