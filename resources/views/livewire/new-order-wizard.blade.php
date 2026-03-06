{{-- /new-order — Wizard-style (Option 4) — 2-step flow: Products+Notes → Review+Submit --}}
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
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h1 class="text-xl font-bold text-slate-800 m-0">{{ __('Create new order') }}</h1>
            @if ($showAddTestItems ?? false)
            <button type="button" @click="addFiveTestItems(); items.forEach((it,i) => it._expanded = i === items.length - 1)" class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
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
            {{-- Step 1: Your products + general notes --}}
            <section x-show="currentStep === 1" x-cloak class="flex flex-col gap-3 min-h-0">
                <div class="bg-white rounded-xl shadow-sm border border-primary-100 p-4 flex flex-col gap-3">
                    {{-- Vertical product cards (no tabs) --}}
                    <div class="space-y-3">
                        <template x-for="(item, idx) in items" :key="idx">
                            <div class="rounded-lg border border-primary-100 overflow-hidden"
                                 :class="item._expanded ? 'bg-white' : 'bg-primary-50/50'">
                                <div class="flex items-center justify-between gap-2 px-3 py-2 cursor-pointer select-none border-b border-primary-100"
                                     :class="item._expanded ? 'bg-white' : 'bg-primary-50'"
                                     @click="item._expanded = !item._expanded">
                                    <span class="text-sm font-semibold text-slate-800" x-text="'{{ __('order_form.product_num') }} ' + (idx + 1) + (getItemSite(item) ? ' — ' + getItemSite(item) : '')"></span>
                                    <div class="flex items-center gap-2 shrink-0" @click.stop>
                                        <button type="button" x-show="items.length > 1"
                                                @click="removeItem(idx); items.forEach((it,i) => { if (i === Math.min(idx, items.length - 1)) it._expanded = true; else it._expanded = false; })"
                                                class="text-red-600 text-xs font-medium hover:text-red-700 py-1 px-2">{{ __('order_form.remove') }}</button>
                                        <span class="text-primary-500 text-xs" x-text="item._expanded ? '▼' : '▶'"></span>
                                    </div>
                                </div>
                                <div x-show="item._expanded" x-collapse class="p-3">
                                    @include('livewire.partials._wizard-item-form')
                                </div>
                            </div>
                        </template>
                    </div>
                    <button type="button" x-show="items.length < maxProducts"
                            @click="addProduct(); const last = items[items.length - 1]; last._expanded = true; items.forEach((it,i) => { if (it !== last) it._expanded = false; })"
                            class="w-full py-2.5 inline-flex items-center justify-center gap-2 bg-primary-50 text-primary-500 border border-primary-200 font-medium rounded-lg text-sm hover:bg-primary-100 transition-colors">
                        + {{ __('order_form.wizard_add_another') }}
                    </button>
                    {{-- General notes (in Step 1) --}}
                    <div class="pt-2 border-t border-primary-100">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ __('order_form.general_notes') }} <span class="text-slate-400 font-normal">{{ __('order_form.optional') }}</span></label>
                        <textarea x-model="orderNotes" @input.debounce.500ms="saveDraft()"
                                  placeholder="{{ __('order_form.general_notes_ph') }}"
                                  rows="2"
                                  class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"></textarea>
                    </div>
                </div>
            </section>

            {{-- Step 2: Review & submit --}}
            <section x-show="currentStep === 2" x-cloak class="flex flex-col gap-3 min-h-0">
                <div class="bg-white rounded-xl shadow-sm border border-primary-100 p-4">
                    <div class="flex justify-between items-center gap-2 mb-3">
                        <h2 class="text-base font-semibold text-slate-800 m-0">{{ __('order_form.wizard_step_2_title') }}</h2>
                        @if ($showResetAll ?? true)
                        <button type="button" @click="resetAll(); currentStep = 1"
                                class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                            {{ __('order_form.reset_all') }}
                        </button>
                        @endif
                    </div>
                    <div class="space-y-2 max-h-[40vh] overflow-y-auto">
                        <template x-for="(item, idx) in items" :key="idx">
                            <div class="flex items-start justify-between gap-2 p-2.5 rounded-lg bg-primary-50/50 border border-primary-100">
                                <div class="min-w-0 flex-1">
                                    <span class="font-medium text-sm text-slate-800" x-text="'{{ __('order_form.product_num') }} ' + (idx + 1)"></span>
                                    <span class="text-slate-500 text-sm ms-1" dir="ltr" x-text="getItemSite(item) || ((item.url || '').substring(0, 40) + ((item.url || '').length > 40 ? '...' : '')) || '—'"></span>
                                    <div class="text-xs text-slate-500 mt-0.5" x-text="(item.qty || 1) + ' × ' + (item.price || '—') + ' ' + (item.currency || '')"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <template x-if="(orderNotes || '').trim()">
                        <div class="mt-3 pt-3 border-t border-primary-100">
                            <span class="text-xs text-slate-500 font-medium">{{ __('order_form.general_notes') }}:</span>
                            <p class="text-sm text-slate-700 mt-0.5 whitespace-pre-wrap" x-text="orderNotes"></p>
                        </div>
                    </template>
                    <div class="mt-3 pt-3 border-t border-primary-100 flex justify-between items-center">
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
            <template x-if="currentStep === 1">
                <button type="button" @click="nextStep()"
                        :disabled="!hasAtLeastOneFilledItem()"
                        class="flex-1 py-3 px-4 rounded-lg font-semibold bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('order_form.wizard_next') }}
                </button>
            </template>
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
    const base = newOrderForm(rates, currencyList, maxProductsArg ?? maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes);
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
                _expanded: i === 0, _focused: false, _showOptional: false,
                _files: []
            }));
            this.orderNotes = initialOrderNotes || '';
        } else if (!this.loadDraft()) {
            this.items = [this.emptyItem()];
        }
        if (this.items.length > 0) this.items[0]._expanded = true;
        this.calcTotals();

        window.addEventListener('beforeunload', (e) => {
            if (this.submitting || !this.hasUnsavedData()) return;
            @if (config('app.env') === 'local')
            return;
            @endif
            e.preventDefault();
        });
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
    base.nextStep = function() {
        if (this.currentStep === 1 && !this.hasAtLeastOneFilledItem()) {
            this.showNotify('error', @js(__('order_form.wizard_review_empty')));
            return;
        }
        if (this.currentStep < this.totalSteps) this.currentStep++;
    };
    base.prevStep = function() {
        if (this.currentStep > 1) this.currentStep--;
    };
    return base;
}
</script>
@endpush
