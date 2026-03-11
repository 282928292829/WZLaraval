{{-- /new-order — Wizard (Option 4) — Rebuilt from scratch. Mobile-first. --}}
{{-- Step 1: Products only (one at a time). Step 2: Notes + Review + Submit. --}}
@php
    $isLoggedIn = auth()->check();
@endphp
<div>
<div
    x-data="newOrderWizardForm(
        @js($exchangeRates),
        @js($currencies),
        {{ $maxProducts }},
        @js($defaultCurrency),
        {{ $isLoggedIn ? 'true' : 'false' }},
        @js($commissionSettings),
        @js(($productUrl || $duplicateFrom) ? $items : null),
        @js($duplicateFrom ? $orderNotes : null),
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
        {{ $maxFileSizeBytes ?? (2 * 1024 * 1024) }},
        {{ $maxProducts }}
    )"
    x-init="init()"
    @notify.window="showNotify($event.detail.type, $event.detail.message)"
    @zoom-image.window="zoomedImage = $event.detail"
    @keydown.escape.window="closeZoom()"
    class="bg-white text-slate-800 font-[family-name:var(--font-family-arabic)]"
>
    <div x-ref="toasts" id="toast-container"></div>

    <div class="max-w-2xl mx-auto p-4 min-h-0 flex flex-col" style="min-height: calc(100vh - 8rem);">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3 shrink-0">
            <h1 class="text-xl font-bold text-slate-800 m-0">{{ __('Create new order') }}</h1>
            @if ($showAddTestItems ?? false)
            <button type="button" @click="addFiveTestItems(); activeItemIndex = Math.max(0, items.length - 1)" class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                {{ __('order.dev_add_5_test_items') }}
            </button>
            @endif
        </div>

        {{-- Progress: 2 steps --}}
        <div class="flex items-center gap-2 mb-4 shrink-0">
            <span class="text-sm font-semibold text-slate-600" x-text="'{{ __('order_form.wizard_step_of') }}'.replace(':n', currentStep).replace(':total', totalSteps)"></span>
            <div class="flex-1 flex gap-1">
                <div class="flex-1 h-1.5 rounded-full transition-colors" :class="currentStep >= 1 ? 'bg-primary-500' : 'bg-primary-100'"></div>
                <div class="flex-1 h-1.5 rounded-full transition-colors" :class="currentStep >= 2 ? 'bg-primary-500' : 'bg-primary-100'"></div>
            </div>
        </div>

        {{-- Step content --}}
        <div class="flex-1 min-h-0 flex flex-col gap-3 overflow-y-auto">
            {{-- Step 1: Products only (one at a time on mobile) --}}
            <section x-show="currentStep === 1" x-cloak class="flex flex-col gap-3 min-h-0">
                {{-- Tips box --}}
                <div class="bg-white rounded-lg shadow-sm border border-primary-100 overflow-hidden shrink-0" x-show="!tipsHidden" x-cloak>
                    <div class="px-4 py-3 flex justify-between items-center cursor-pointer border-b border-primary-100" @click="tipsOpen = !tipsOpen">
                        <h2 class="text-sm font-semibold text-slate-800 m-0">{{ __('order_form.tips_title') }}</h2>
                        <span x-text="tipsOpen ? '▲' : '▼'" class="text-primary-500 text-xs"></span>
                    </div>
                    <div x-show="tipsOpen" x-collapse class="p-4 text-sm leading-relaxed text-slate-600">
                        <ul class="list-none p-0 m-0">
                            @for ($i = 1; $i <= 8; $i++)
                                <li class="mb-2.5 relative ps-[18px] before:content-['•'] before:absolute before:start-0 before:text-primary-500 before:font-bold">{{ __("order_form.tip_{$i}") }}</li>
                            @endfor
                        </ul>
                        <div class="mt-4 pt-4 border-t border-primary-100">
                            <label class="flex items-center gap-2 text-sm text-slate-500 cursor-pointer">
                                <input type="checkbox" @change="hideTips30Days()" class="cursor-pointer">
                                <span>{{ __('order_form.tips_dont_show') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-primary-100 p-4 flex flex-col gap-3 flex-1 min-h-0">
                    {{-- Product indicator: Product X of Y (with prev/next when multiple) --}}
                    <div class="flex items-center justify-between gap-2 shrink-0">
                        <span class="text-sm font-semibold text-slate-700" x-text="'{{ __('order_form.wizard_product_of') }}'.replace(':n', activeItemIndex + 1).replace(':total', items.length)"></span>
                        <div class="flex items-center gap-1" x-show="items.length > 1">
                            <button type="button" @click="activeItemIndex = Math.max(0, activeItemIndex - 1)"
                                    :disabled="activeItemIndex <= 0"
                                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-primary-200 bg-white text-slate-600 hover:bg-primary-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                    aria-label="{{ __('order_form.wizard_back') }}">‹</button>
                            <button type="button" @click="activeItemIndex = Math.min(items.length - 1, activeItemIndex + 1)"
                                    :disabled="activeItemIndex >= items.length - 1"
                                    class="w-9 h-9 flex items-center justify-center rounded-lg border border-primary-200 bg-white text-slate-600 hover:bg-primary-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                    aria-label="{{ __('order_form.wizard_next') }}">›</button>
                        </div>
                    </div>

                    {{-- One product form at a time --}}
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <template x-for="(item, idx) in items" :key="idx">
                            <div x-show="activeItemIndex === idx" x-cloak class="py-1">
                                <div class="flex items-center justify-between gap-2 mb-2" x-show="items.length > 1">
                                    <span class="text-xs text-slate-500">{{ __('order_form.product_num') }} <span x-text="idx + 1"></span></span>
                                    <button type="button" @click="removeItem(idx)"
                                            class="text-red-600 text-xs font-medium hover:text-red-700 py-1 px-2">{{ __('order_form.remove') }}</button>
                                </div>
                                @include('livewire.partials._wizard-item-form')
                            </div>
                        </template>
                    </div>

                    {{-- Add another / Continue to submit --}}
                    <div class="flex flex-col gap-2 shrink-0 pt-2 border-t border-primary-100">
                        <button type="button" x-show="items.length < maxProducts"
                                @click="addProductWizard()"
                                class="w-full py-2.5 inline-flex items-center justify-center gap-2 bg-primary-50 text-primary-500 border border-primary-200 font-medium rounded-lg text-sm hover:bg-primary-100 transition-colors">
                            + {{ __('order_form.wizard_add_another') }}
                        </button>
                        <button type="button" @click="goToSubmitStep()"
                                class="w-full py-3 px-4 rounded-lg font-semibold bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors">
                            {{ __('order_form.wizard_continue_to_submit') }}
                        </button>
                    </div>
                </div>
            </section>

            {{-- Step 2: Notes + Review + Submit --}}
            <section x-show="currentStep === 2" x-cloak class="flex flex-col gap-3 min-h-0">
                <div class="bg-white rounded-xl shadow-sm border border-primary-100 p-4 flex flex-col gap-4">
                    {{-- Review list (items first) --}}
                    <div>
                        <h2 class="text-base font-semibold text-slate-800 m-0 mb-2">{{ __('order_form.wizard_step_2_title') }}</h2>
                        <div class="space-y-2 max-h-[30vh] overflow-y-auto">
                            <template x-for="(item, idx) in items" :key="idx">
                                <div class="flex items-start justify-between gap-2 p-2.5 rounded-lg bg-primary-50/50 border border-primary-100">
                                    <div class="min-w-0 flex-1">
                                        <span class="font-medium text-sm text-slate-800" x-text="'{{ __('order_form.product_num') }} ' + (idx + 1)"></span>
                                        <span class="text-slate-500 text-sm ms-1" dir="ltr" x-text="getItemSite(item) || ((item.url || '').substring(0, 40) + ((item.url || '').length > 40 ? '...' : '')) || '{{ __('common.dash') }}'"></span>
                                        <div class="text-xs text-slate-500 mt-0.5" x-text="(item.qty || 1) + ' × ' + (item.price || '{{ __('common.dash') }}') + ' ' + (item.currency || '')"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- General notes (below items) --}}
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="block text-sm font-semibold text-slate-800">{{ __('order_form.general_notes') }} <span class="text-slate-400 font-normal">{{ __('order_form.optional') }}</span></label>
                            @if ($showResetAll ?? true)
                            <button type="button" @click="resetAll()"
                                    class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                                {{ __('order_form.reset_all') }}
                            </button>
                            @endif
                        </div>
                        <textarea x-model="orderNotes" @input.debounce.500ms="saveDraft()"
                                  placeholder="{{ __('order_form.general_notes_ph') }}"
                                  rows="2"
                                  class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"></textarea>
                    </div>

                    {{-- Total --}}
                    <div class="pt-3 border-t border-primary-100">
                        <span class="text-sm font-semibold text-slate-700" x-text="totalText()"></span>
                    </div>
                </div>
            </section>
        </div>

        {{-- Navigation footer --}}
        <div class="flex gap-3 mt-4 pt-4 border-t border-primary-100 shrink-0">
            <button type="button" x-show="currentStep > 1" @click="prevStep()"
                    class="flex-1 py-3 px-4 rounded-lg font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                {{ __('order_form.wizard_back') }}
            </button>
            <template x-if="currentStep === 2">
                <button type="button" @click="submitOrder()" :disabled="submitting"
                        class="flex-1 py-3 px-4 rounded-lg font-semibold bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60 disabled:pointer-events-none">
                    <span x-show="!submitting">{{ __('order_form.confirm_order') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('order_form.submitting') }}...</span>
                </button>
            </template>
        </div>
    </div>
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

@include('livewire.partials._order-login-modal')
</div>

@push('scripts')
<script>
@include('livewire.partials._new-order-form-js')

function newOrderWizardForm(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes, maxProductsArg) {
    const maxP = maxProductsArg ?? maxProducts;
    const base = newOrderForm(rates, currencyList, maxP, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes);

    base.currentStep = 1;
    base.totalSteps = 2;
    base.activeItemIndex = 0;

    base.init = function() {
        this.checkTipsHidden();
        if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
            this.items = initialItems.map((d, i) => ({
                url: d.url || '', qty: (d.qty || '1').toString(), color: d.color || '', size: d.size || '',
                price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                _expanded: true, _focused: false, _showOptional: false,
                _files: []
            }));
            this.orderNotes = initialOrderNotes || '';
            this.activeItemIndex = 0;
        } else if (!this.loadDraft()) {
            this.items = [this.emptyItem()];
            this.activeItemIndex = 0;
        } else {
            this.activeItemIndex = 0;
        }
        this.calcTotals();

        window.addEventListener('beforeunload', (e) => {
            if (this.submitting || !this.hasUnsavedData()) return;
            @if (config('app.env') === 'local')
            return;
            @endif
            e.preventDefault();
        });
    };

    const origLoadDraft = base.loadDraft;
    base.loadDraft = function() {
        const ok = origLoadDraft.call(this);
        if (ok) this.activeItemIndex = 0;
        return ok;
    };

    base.addProductWizard = function() {
        if (this.items.length >= this.maxProducts) {
            this.showNotify('error', @js(__('order_form.max_products', ['max' => $maxProducts ?? 30])));
            return;
        }
        const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;
        this.items.push(this.emptyItem(lastCur));
        this.activeItemIndex = this.items.length - 1;
        this.saveDraft();
    };

    base.removeItem = function(idx) {
        this.$wire.removeItem(idx);
        this.items.splice(idx, 1);
        if (this.items.length === 0) {
            this.items.push(this.emptyItem());
            this.activeItemIndex = 0;
        } else {
            if (idx < this.activeItemIndex) this.activeItemIndex--;
            else if (this.activeItemIndex >= this.items.length) this.activeItemIndex = Math.max(0, this.items.length - 1);
        }
        this.calcTotals();
        this.saveDraft();
    };

    base.hasAtLeastOneFilledItem = function() {
        return this.items.some(i =>
            (i.url || '').trim() ||
            (i.color || '').trim() ||
            (i.size || '').trim() ||
            (i.notes || '').trim() ||
            (parseFloat(i.price) > 0) ||
            ((i._files || []).length > 0)
        );
    };

    base.goToSubmitStep = function() {
        this.currentStep = 2;
    };

    base.nextStep = function() {
        if (this.currentStep < this.totalSteps) this.currentStep++;
    };

    base.prevStep = function() {
        if (this.currentStep > 1) this.currentStep--;
    };

    base.resetAll = function() {
        if (!confirm('{{ __('order_form.reset_confirm') }}')) return;
        this.items = [this.emptyItem()];
        this.orderNotes = '';
        this.activeItemIndex = 0;
        this.currentStep = 1;
        this.clearDraft();
        this.calcTotals();
        this.showNotify('success', '{{ __('order_form.cleared') }}');
    };

    base.hideTips30Days = function() {
        try {
            localStorage.setItem('wz_order_form_tips_until', (Date.now() + 30*24*60*60*1000).toString());
        } catch {}
        this.tipsHidden = true;
        this.showNotify('success', '{{ __('order_form.tips_hidden') }}');
    };

    base.addFiveTestItems = function() {
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
    };

    return base;
}
</script>
@endpush
