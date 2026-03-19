{{-- Layout: Wizard — One item per step. /new-order-wizard --}}
{{-- Phases: item → review. Own Alpine component: newOrderFormWizard(). --}}

@php
    $isLoggedIn = auth()->check();
@endphp

<div>
<div
    data-guest="{{ auth()->guest() ? 'true' : 'false' }}"
    x-data="newOrderFormWizard(
        @js($exchangeRates),
        @js($currencies),
        {{ $maxProducts }},
        @js($defaultCurrency),
        {{ $isLoggedIn ? 'true' : 'false' }},
        @js($commissionSettings),
        @js(($editingOrderId || $productUrl || $duplicateFrom) ? $items : null),
        @js(($editingOrderId || $duplicateFrom) ? $orderNotes : null),
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
    @keydown.escape.window="closeZoom(); if (showDraftPrompt) { showDraftPrompt = false; }"
    @open-login-modal-attach.window="$wire.openLoginModalForAttach()"
    @user-logged-in.window="isLoggedIn = true"
    class="bg-slate-50 text-slate-800 font-[family-name:var(--font-family-arabic)] min-h-screen"
>

{{-- Toast container --}}
<div x-ref="toasts" id="toast-container"></div>

<div class="max-w-2xl mx-auto px-4 pt-4 pb-28">

    {{-- ─── Edit mode banner ──────────────────────────────────────────────── --}}
    @if ($editingOrderId)
    <section class="p-3 mb-4 bg-amber-50 border border-amber-200 rounded-xl">
        <p class="text-sm font-semibold text-amber-800 m-0">{{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}</p>
        <p class="text-xs text-amber-700 mt-1 mb-0">{{ __('orders.edit_resubmit_deadline_hint') }}</p>
    </section>
    @endif

    {{-- ─── Tips box (phase: item only, hidden on review to keep focus) ── --}}
    <div x-show="phase === 'item'" x-cloak>
        @include('livewire.partials._order-tips')
    </div>

    {{-- ─── Draft restore prompt ──────────────────────────────────────────── --}}
    <div x-show="showDraftPrompt" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-4 bg-white border border-primary-200 rounded-xl p-4 shadow-sm">
        <p class="text-sm font-semibold text-slate-800 m-0 mb-1">{{ __('order_form.draft_restore_title') }}</p>
        <p class="text-xs text-slate-500 mb-3"
           x-text="'{{ __('order_form.draft_restore_desc') }}'.replace(':count', pendingDraftItems ? pendingDraftItems.length : '')"></p>
        <div class="flex gap-2">
            <button type="button"
                    @click="restoreDraft()"
                    class="flex-1 py-2 px-4 rounded-lg text-sm font-semibold bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-sm hover:from-primary-600 hover:to-primary-500 transition-colors">
                {{ __('order_form.draft_restore') }}
            </button>
            <button type="button"
                    @click="discardDraft()"
                    class="flex-1 py-2 px-4 rounded-lg text-sm font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors border border-slate-200">
                {{ __('order_form.draft_start_fresh') }}
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         PHASE: ITEM — fill one item at a time
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="phase === 'item'" x-cloak>

        {{-- Page title row --}}
        <div class="flex flex-nowrap items-center justify-between gap-2 mb-4">
            <div class="shrink-0 flex items-center gap-2">
                {{-- Back to previous item (only when not on first) --}}
                <button type="button"
                        x-show="currentItemIdx > 0"
                        x-cloak
                        @click="backToPreviousItem()"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-colors"
                        :aria-label="'{{ __('order_form.wizard_back') }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <span class="text-lg font-bold text-slate-800 leading-tight"
                      x-text="'{{ __('order_form.wizard_item_step') }}'.replace(':n', currentItemIdx + 1)"></span>
                {{-- Next item (when more items exist) --}}
                <button type="button"
                        x-show="currentItemIdx < items.length - 1"
                        x-cloak
                        @click="nextItem()"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-colors"
                        :aria-label="'{{ __('order_form.wizard_next') }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
            </div>
            <span class="flex-1 min-w-0 text-center text-xs text-slate-400 truncate"
                  x-text="'{{ __('order_form.wizard_items_added') }}'.replace(':count', filledCount)"></span>
            <div class="flex items-center gap-2 shrink-0">
                @if ($showAddTestItems ?? false)
                <button type="button"
                        @click="addFiveTestItems()"
                        class="text-xs text-slate-400 underline bg-transparent border-none cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                    {{ __('order.dev_add_5_test_items') }}
                </button>
                @endif
            </div>
        </div>

        {{-- Item card with fields — x-for over all items, only active shown --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <template x-for="(item, idx) in items" :key="item._id || idx">
                <div x-show="idx === currentItemIdx"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-x-2"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     class="p-4 grid grid-cols-6 gap-x-3 gap-y-2.5">
                    @include('livewire.partials._item-fields', ['showUrlPasteOpen' => true])
                </div>
            </template>
        </div>

        {{-- Action buttons row --}}
        <div class="mt-3 flex flex-col gap-2">

            {{-- "Add Another Item" — disabled at max --}}
            <button type="button"
                    @click="addAnotherItem()"
                    x-show="!editingFromReview"
                    x-cloak
                    :disabled="items.length >= maxProducts"
                    class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-primary-600 bg-white border-2 border-dashed border-primary-200 hover:border-primary-400 hover:bg-primary-50 transition-all disabled:opacity-50 disabled:pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('order_form.wizard_add_another_item') }}
            </button>

            {{-- "Done Adding Items" → go to Review --}}
            <button type="button"
                    @click="doneAddingItems()"
                    x-show="!editingFromReview"
                    x-cloak
                    class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-400 shadow-md shadow-primary-500/20 hover:from-primary-600 hover:to-primary-500 transition-all">
                {{ __('order_form.wizard_done_adding') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>

            {{-- "Save & Back to Review" — shown only when editing from Review --}}
            <button type="button"
                    @click="saveAndBackToReview()"
                    x-show="editingFromReview"
                    x-cloak
                    class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-400 shadow-md shadow-primary-500/20 hover:from-primary-600 hover:to-primary-500 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ __('order_form.wizard_save_back_to_review') }}
            </button>

            {{-- Remove current item (visible only when there is more than 1 item) --}}
            <button type="button"
                    x-show="items.length > 1"
                    x-cloak
                    @click="removeCurrentItem()"
                    class="w-full min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl text-sm font-medium text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all border border-transparent hover:border-red-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                {{ __('order_form.wizard_remove_this_item') }}
            </button>

        </div>
    </div>{{-- /phase item --}}

    {{-- ══════════════════════════════════════════════════════════════════════
         PHASE: REVIEW — compact item list, editable notes, submit
    ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="phase === 'review'" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-x-2"
         x-transition:enter-end="opacity-100 translate-x-0">

        {{-- Title + back (to last item) --}}
        <div class="flex items-center gap-2 mb-4">
            <button type="button"
                    @click="backFromReview()"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-colors"
                    aria-label="{{ __('order_form.wizard_back') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <h2 class="text-lg font-bold text-slate-800 m-0">{{ __('order_form.wizard_review_title') }}</h2>
        </div>

        {{-- Items list --}}
        <div class="flex flex-col gap-2 mb-3">
            <template x-for="(item, idx) in items" :key="item._id || idx">
                <div class="bg-white rounded-xl border border-slate-200 px-3.5 py-3 flex items-center gap-3">

                    {{-- Number badge --}}
                    <span class="shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold flex items-center justify-center leading-none"
                          x-text="idx + 1"></span>

                    {{-- Item summary --}}
                    <div class="flex-1 min-w-0">
                        {{-- URL / site — font-arabic so "بدون رابط" uses IBM Plex Sans Arabic; dir=auto for correct flow --}}
                        <p class="text-sm font-medium text-slate-700 m-0 truncate font-arabic"
                           x-text="reviewItemLine1(idx)"
                           dir="auto"></p>
                        {{-- Qty × Price Currency --}}
                        <p class="text-xs text-slate-400 m-0 mt-0.5"
                           x-text="reviewItemLine2(idx)"></p>
                    </div>

                    {{-- Edit button --}}
                    <button type="button"
                            @click="editItemFromReview(idx)"
                            class="shrink-0 min-h-[44px] inline-flex items-center justify-center py-1 px-2.5 rounded-md text-xs font-semibold text-primary-600 bg-primary-50 border border-primary-100 hover:bg-primary-100 hover:border-primary-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 transition-colors">
                        {{ __('order_form.show_edit') }}
                    </button>

                    {{-- Remove button --}}
                    <button type="button"
                            @click="removeItemFromReview(idx)"
                            class="shrink-0 inline-flex items-center justify-center min-w-[44px] min-h-[44px] rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300 transition-colors"
                            :aria-label="'{{ __('order_form.remove') }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>

                </div>
            </template>
        </div>

        {{-- "Add another item" link from review (convenience) --}}
        <button type="button"
                @click="addAnotherItemFromReview()"
                :disabled="items.length >= maxProducts"
                class="w-full mb-3 min-h-[40px] inline-flex items-center justify-center gap-2 rounded-xl text-sm font-medium text-primary-600 bg-white border border-dashed border-primary-200 hover:border-primary-400 hover:bg-primary-50 transition-all disabled:opacity-50 disabled:pointer-events-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ __('order_form.wizard_add_another_item') }}
        </button>

        {{-- Order notes (inline editable in review) --}}
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 mb-3">
            <h3 class="text-sm font-semibold text-slate-700 m-0 mb-1.5">
                {{ __('order_form.general_notes') }}
                <span class="text-xs font-normal text-slate-400 ms-1">{{ __('order_form.optional') }}</span>
            </h3>
            <textarea
                x-model="orderNotes"
                @input.debounce.500ms="saveDraft()"
                placeholder="{{ __('order_form.general_notes_ph') }}"
                rows="2"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/10 transition-colors"
            ></textarea>
        </section>

    </div>{{-- /phase review --}}

</div>{{-- /max-w-2xl --}}

{{-- ─── Fixed submit footer (visible only on review) ────────────────────────── --}}
<div class="order-summary-card" x-show="phase === 'review'" x-cloak>
    <div class="flex flex-col gap-0.5 flex-1 min-w-0">
        <span class="text-[0.7rem] font-normal text-stone-400 whitespace-nowrap overflow-hidden text-ellipsis"
              x-text="productCountText()"></span>
        <span class="text-stone-400 font-normal text-[0.7rem] whitespace-nowrap"
              x-text="totalText()"></span>
    </div>
    <button type="button"
            @click="submitOrder()"
            :disabled="submitting"
            class="shrink-0 min-w-[120px] max-w-[180px] lg:min-w-[180px] lg:max-w-none lg:px-6 lg:py-3.5 lg:text-lg w-auto inline-flex items-center justify-center py-3 px-4 rounded-md font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-60 disabled:pointer-events-none">
        @if ($editingOrderId)
        <span x-show="!submitting">{{ __('orders.save_changes') }}</span>
        @else
        <span x-show="!submitting">{{ __('order_form.confirm_order') }}</span>
        @endif
        <span x-show="submitting" x-cloak>{{ __('order_form.submitting') }}...</span>
    </button>
</div>

{{-- ─── Image zoom modal ─────────────────────────────────────────────────────── --}}
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
    <button type="button"
            class="absolute top-4 end-4 w-10 h-10 flex items-center justify-center rounded-full bg-white/20 text-white text-2xl border-none cursor-pointer hover:bg-white/30 z-10"
            @click="closeZoom()"
            aria-label="{{ __('Close') }}">&times;</button>
    <img :src="zoomedImage" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl" @click.stop alt="">
</div>

{{-- ─── Login modal ──────────────────────────────────────────────────────────── --}}
@livewire('guest-login-modal')

</div>
</div>

@push('scripts')
{{-- Shared core logic --}}
<script>
@include('livewire.partials._new-order-form-js')
</script>

<script>
function newOrderFormWizard(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes) {
    return {
        ...newOrderForm(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes),

        // ── Wizard-specific state ──────────────────────────────────────────
        phase: 'item',              // 'item' | 'review'
        currentItemIdx: 0,          // 0-based index of item being filled
        editingFromReview: false,   // true when jumped back from review to edit
        showDraftPrompt: false,
        pendingDraftItems: null,
        pendingDraftNotes: '',

        // ── Override init() ───────────────────────────────────────────────
        init() {
            this.checkTipsHidden();

            if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
                // Pre-filled (duplicate / edit / product_url) — load directly, no prompt
                this.items = initialItems.map((d) => ({
                    _id: Math.random().toString(36).slice(2),
                    url: d.url || '',
                    qty: (d.qty || '1').toString(),
                    color: d.color || '',
                    size: d.size || '',
                    price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                    currency: d.currency || this.defaultCurrency,
                    notes: d.notes || '',
                    _expanded: true, _focused: false, _showOptional: false,
                    _files: []
                }));
                this.orderNotes = initialOrderNotes || '';
                this.currentItemIdx = 0;
                this.phase = 'item';
            } else {
                // Check for saved draft — show prompt, never silently restore
                const draft = this.peekDraft();
                if (draft) {
                    this.pendingDraftItems = draft.items;
                    this.pendingDraftNotes = draft.notes;
                    this.showDraftPrompt = true;
                    this.items = [this.emptyItem()];
                    this.currentItemIdx = 0;
                } else {
                    this.items = [this.emptyItem()];
                    this.currentItemIdx = 0;
                }
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

        // ── Draft: peek without loading ───────────────────────────────────
        peekDraft() {
            try {
                let raw = localStorage.getItem('wz_order_form_draft');
                let notes = localStorage.getItem('wz_order_form_notes');
                // Legacy key migration
                if (!raw && localStorage.getItem('wz_opus46_draft')) {
                    raw = localStorage.getItem('wz_opus46_draft');
                    notes = localStorage.getItem('wz_opus46_notes');
                    localStorage.removeItem('wz_opus46_draft');
                    localStorage.removeItem('wz_opus46_notes');
                    if (raw) localStorage.setItem('wz_order_form_draft', raw);
                    if (notes) localStorage.setItem('wz_order_form_notes', notes);
                }
                if (!raw) return null;
                const data = JSON.parse(raw);
                if (!Array.isArray(data) || data.length === 0) return null;
                const hasMeaningfulContent = data.some(d =>
                    (d.url || '').trim() || (d.color || '').trim() ||
                    (d.size || '').trim() || (d.notes || '').trim() ||
                    (parseFloat(d.price) > 0)
                );
                if (!hasMeaningfulContent) return null;
                return { items: data, notes: notes || '' };
            } catch {
                return null;
            }
        },

        // ── Draft: restore ────────────────────────────────────────────────
        restoreDraft() {
            if (!this.pendingDraftItems) {
                this.showDraftPrompt = false;
                return;
            }
            this.items = this.pendingDraftItems.map((d) => ({
                _id: Math.random().toString(36).slice(2),
                url: d.url || '', qty: d.qty || '1', color: d.color || '',
                size: d.size || '', price: d.price || '',
                currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                _expanded: true, _focused: false, _showOptional: false,
                _files: []
            }));
            this.orderNotes = this.pendingDraftNotes || '';
            this.pendingDraftItems = null;
            this.pendingDraftNotes = '';
            this.showDraftPrompt = false;
            this.currentItemIdx = 0;
            this.phase = 'item';
            this.editingFromReview = false;
            this.calcTotals();
            this.showNotify('success', @js(__('order_form.draft_restored')));
        },

        // ── Draft: discard, start fresh ───────────────────────────────────
        discardDraft() {
            this.clearDraft();
            this.pendingDraftItems = null;
            this.pendingDraftNotes = '';
            this.showDraftPrompt = false;
            this.items = [this.emptyItem()];
            this.orderNotes = '';
            this.currentItemIdx = 0;
            this.phase = 'item';
            this.editingFromReview = false;
            this.calcTotals();
            this.$nextTick(() => this.saveDraft());
        },

        // ── Item phase: "Add Another Item" ────────────────────────────────
        addAnotherItem() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', @js(__('order_form.max_products', ['max' => $maxProducts ?? 30])));
                return;
            }
            const lastCur = this.items.length > 0
                ? this.items[this.items.length - 1].currency
                : this.defaultCurrency;
            this.items.push(this.emptyItem(lastCur));
            this.currentItemIdx = this.items.length - 1;
            this.editingFromReview = false;
            this.saveDraft();
        },

        // ── Item phase: "Done Adding Items" → review ─────────────────────
        doneAddingItems() {
            this.saveDraft();
            this.phase = 'review';
            this.editingFromReview = false;
        },

        // ── Item phase: "Save & Back to Review" (only when editingFromReview) ──
        saveAndBackToReview() {
            this.saveDraft();
            this.phase = 'review';
            this.editingFromReview = false;
        },

        // ── Item phase: back to previous item ────────────────────────────
        backToPreviousItem() {
            if (this.currentItemIdx > 0) {
                this.currentItemIdx--;
                this.editingFromReview = false;
            }
        },

        // ── Item phase: remove the currently displayed item ───────────────
        removeCurrentItem() {
            if (!confirm(@js(__('order_form.remove_item_confirm')))) return;
            const idx = this.currentItemIdx;
            this.$wire.removeItem(idx);
            this.items.splice(idx, 1);
            this.calcTotals();
            this.saveDraft();

            if (this.items.length === 0) {
                // Removed last item — start fresh
                this.items = [this.emptyItem()];
                this.currentItemIdx = 0;
                this.phase = 'item';
                this.editingFromReview = false;
            } else {
                // Move to the previous item (or stay at 0)
                this.currentItemIdx = Math.max(0, idx - 1);
                // If we were editing from review and still have items, go back to review
                if (this.editingFromReview) {
                    this.phase = 'review';
                    this.editingFromReview = false;
                }
            }
        },

        // ── Review phase: back to last item ───────────────────────────────
        backFromReview() {
            this.currentItemIdx = Math.max(0, this.items.length - 1);
            this.phase = 'item';
            this.editingFromReview = false;
        },

        // ── Review phase: edit a specific item ───────────────────────────
        editItemFromReview(idx) {
            this.currentItemIdx = idx;
            this.phase = 'item';
            this.editingFromReview = true;
            this.$nextTick(() => {
                this.showNotify('success', @js(__('order_form.wizard_editing_toast')));
            });
        },

        // ── Review phase: add another item (jumps back to item phase) ────
        addAnotherItemFromReview() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', @js(__('order_form.max_products', ['max' => $maxProducts ?? 30])));
                return;
            }
            const lastCur = this.items.length > 0
                ? this.items[this.items.length - 1].currency
                : this.defaultCurrency;
            this.items.push(this.emptyItem(lastCur));
            this.currentItemIdx = this.items.length - 1;
            this.phase = 'item';
            this.editingFromReview = false;
            this.saveDraft();
        },

        // ── Review phase: remove an item from the list ────────────────────
        removeItemFromReview(idx) {
            if (!confirm(@js(__('order_form.remove_item_confirm')))) return;
            this.$wire.removeItem(idx);
            this.items.splice(idx, 1);
            this.calcTotals();
            this.saveDraft();

            if (this.items.length === 0) {
                // All items removed — back to phase 'item' with one empty
                this.items = [this.emptyItem()];
                this.currentItemIdx = 0;
                this.phase = 'item';
                this.editingFromReview = false;
            }
            // else stay in review — list updates reactively
        },

        // ── Review: line 1 — URL domain or fallback ───────────────────────
        reviewItemLine1(idx) {
            const item = this.items[idx];
            if (!item) return '';
            const noUrl = @js(__('order_form.wizard_no_url'));
            const url = (item.url || '').trim();
            if (!url) return noUrl;
            try {
                const host = new URL(url.startsWith('http') ? url : 'https://' + url)
                    .hostname.replace('www.', '');
                return host.length > 40 ? host.substring(0, 40) + '…' : host;
            } catch {
                return url.length > 40 ? url.substring(0, 40) + '…' : url;
            }
        },

        // ── Review: line 2 — qty × price currency ────────────────────────
        reviewItemLine2(idx) {
            const item = this.items[idx];
            if (!item) return '';
            const dash = '—';
            const qty = item.qty || '1';
            const price = item.price ? item.price : dash;
            const currency = item.currency || '';
            return `${qty} × ${price} ${currency}`.trim();
        },

        // ── Override resetAll ─────────────────────────────────────────────
        resetAll() {
            if (!confirm(@js(__('order_form.reset_confirm')))) return;
            this.items = [this.emptyItem()];
            this.orderNotes = '';
            this.currentItemIdx = 0;
            this.phase = 'item';
            this.editingFromReview = false;
            this.clearDraft();
            this.calcTotals();
            this.showNotify('success', @js(__('order_form.cleared')));
        },

        // ── Override addFiveTestItems — wizard-aware ──────────────────────
        addFiveTestItems() {
            const urls = [
                'https://www.amazon.com/dp/B0BSHF7LLL',
                'https://www.ebay.com/itm/' + Math.floor(100000000 + Math.random() * 900000000),
                'https://www.walmart.com/ip/' + Math.floor(100000 + Math.random() * 900000),
                'https://www.target.com/p/product-' + Math.floor(100 + Math.random() * 900),
                'https://www.aliexpress.com/item/' + Math.floor(1000000000 + Math.random() * 9000000000) + '.html',
            ];
            const sizes = this.testOptions?.sizes || ['S', 'M', 'L', 'XL', 'US 8'];
            const currencies = ['USD', 'EUR', 'GBP'];
            const colors = this.testOptions?.colors || ['White', 'Black', 'Navy', 'Red', 'Beige'];
            const notes = this.testOptions?.notes || ['Same as picture', 'Please send photo', 'Exact match', 'As shown', 'Confirm color'];
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;
            const isEmpty = (item) =>
                !(item.url || '').trim() && !(item.color || '').trim() &&
                !(item.size || '').trim() && !parseFloat(item.price) &&
                !(item.notes || '').trim();

            for (let i = 0; i < 5; i++) {
                const cur = currencies[i % currencies.length] || lastCur;
                const testData = {
                    _id: Math.random().toString(36).slice(2),
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
                } else {
                    break;
                }
            }

            this.currentItemIdx = Math.min(this.currentItemIdx, this.items.length - 1);
            this.editingFromReview = false;
            this.phase = 'item';
            this.calcTotals();
            this.saveDraft();
            this.showNotify('success', @js(__('order.dev_5_items_added')));
        },
    };
}
</script>
@endpush
