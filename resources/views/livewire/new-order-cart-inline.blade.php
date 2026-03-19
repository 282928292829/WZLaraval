{{-- Layout: Cart (Summary sidebar) — Cards-style items + sidebar summary. Desktop: sidebar right. Mobile: bottom-sheet. /new-order-cart-inline --}}

@php
    $isLoggedIn = auth()->check();
@endphp

<div>
<div
    data-guest="{{ auth()->guest() ? 'true' : 'false' }}"
    x-data="newOrderFormCartInline(
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
    @keydown.escape.window="closeZoom(); if (showDraftPrompt) { showDraftPrompt = false; } if (cartSheetOpen) { cartSheetOpen = false; }"
    @open-login-modal-attach.window="$wire.openLoginModalForAttach()"
    @user-logged-in.window="isLoggedIn = true"
    class="bg-slate-50 text-slate-800 font-[family-name:var(--font-family-arabic)] min-h-screen w-full overflow-x-hidden"
>

{{-- Toast container --}}
<div x-ref="toasts" id="toast-container-cart-inline"></div>

<div class="flex flex-col md:flex-row min-h-[calc(100vh-56px)] w-full max-w-full min-w-0 md:min-h-0">
    {{-- Main: Cards-style item list (left) --}}
    <div class="flex-1 min-w-0 w-full md:overflow-y-auto px-4 py-5 pb-32 md:pb-6 overflow-x-hidden">
        <div class="max-w-2xl mx-auto">
            {{-- Page header --}}
            <div class="flex flex-nowrap items-center justify-between gap-2 mb-5">
                <span class="shrink-0 text-lg font-bold text-slate-800 leading-tight">
                    @if ($editingOrderId)
                        {{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}
                    @else
                        {{ __('Create new order') }}
                    @endif
                </span>
                <span class="flex-1 min-w-0 text-xs text-slate-400 text-center truncate" x-show="filledCount > 0" x-text="productCountText()" x-cloak></span>
                @if ($showAddTestItems ?? false)
                <button type="button"
                        @click="addFiveTestItems()"
                        class="shrink-0 text-xs text-slate-400 underline bg-transparent border-none cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                    {{ __('order.dev_add_5_test_items') }}
                </button>
                @endif
            </div>

            @if ($editingOrderId)
            <section class="p-3 mb-4 bg-amber-50 border border-amber-200 rounded-xl">
                <p class="text-sm font-semibold text-amber-800 m-0">{{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}</p>
                <p class="text-xs text-amber-700 mt-1 mb-0">{{ __('orders.edit_resubmit_deadline_hint') }}</p>
            </section>
            @endif

            @include('livewire.partials._order-tips')

            {{-- Draft restore prompt --}}
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

            {{-- Items list (cards) --}}
            <div id="items-container" class="flex flex-col gap-2.5">
                <template x-for="(item, idx) in items" :key="item._id || idx">
                    <div
                        class="bg-white rounded-xl border transition-all duration-150"
                        :class="item._expanded ? 'border-primary-200 shadow-sm' : 'border-slate-200 shadow-none'">
                        <div class="flex items-center gap-2 px-3 py-2.5 cursor-pointer" @click="toggleItem(idx)">
                            <div class="shrink-0 flex items-center gap-x-1">
                                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold flex items-center justify-center leading-none" x-text="idx + 1"></span>
                                <span class="text-sm font-semibold text-slate-700" x-text="itemHeaderLabel(idx)"></span>
                            </div>
                            <div class="flex-1 min-w-0 flex justify-center">
                                <span class="text-xs font-medium text-slate-500 truncate max-w-[12ch]"
                                      x-show="!item._expanded" x-text="itemHeaderDomain(idx)" x-cloak></span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0" @click.stop>
                                <button type="button" @click="toggleItem(idx)"
                                        :aria-expanded="item._expanded"
                                        :aria-label="item._expanded ? '{{ __('order_form.hide') }}' : '{{ __('order_form.show_edit') }}'"
                                        class="min-h-[44px] inline-flex items-center justify-center py-1 px-2.5 rounded-md text-xs font-semibold text-primary-600 bg-primary-50 border border-primary-100 hover:bg-primary-100 hover:border-primary-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 transition-colors">
                                    <span x-show="!item._expanded">{{ __('order_form.show_edit') }}</span>
                                    <span x-show="item._expanded" x-cloak>{{ __('order_form.hide') }}</span>
                                </button>
                                <button type="button" @click="removeItem(idx)"
                                        class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300 transition-colors"
                                        :aria-label="'{{ __('order_form.remove') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div x-show="item._expanded" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="p-3 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-x-3 gap-y-2.5">
                            @include('livewire.partials._item-fields', ['showUrlPasteOpen' => true])
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addProduct()"
                    class="w-full mt-3 min-h-[44px] py-3 inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-primary-600 bg-white border-2 border-dashed border-primary-200 hover:border-primary-400 hover:bg-primary-50 transition-all cursor-pointer touch-manipulation select-none relative z-[95] isolate">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('order_form.add_product') }}
            </button>

            <section class="mt-4 bg-white rounded-xl border border-slate-200 p-3">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <h3 class="text-sm font-semibold text-slate-700 m-0">
                        {{ __('order_form.general_notes') }}
                        <span class="text-xs font-normal text-slate-400 ms-1">{{ __('order_form.optional') }}</span>
                    </h3>
                    @if ($showResetAll ?? true)
                    <button type="button" @click="resetAll()"
                            class="text-xs text-slate-400 underline bg-transparent border-none cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
                        {{ __('order_form.reset_all') }}
                    </button>
                    @endif
                </div>
                <textarea x-model="orderNotes" @input.debounce.500ms="saveDraft()"
                          placeholder="{{ __('order_form.general_notes_ph') }}"
                          rows="2"
                          class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/10 transition-colors"></textarea>
            </section>
        </div>
    </div>

    {{-- Desktop: Sidebar summary (always visible) — border-s-2 for clear separation from main content --}}
    <aside class="hidden md:flex md:w-[320px] lg:w-[360px] shrink-0 flex-col bg-white border-s-2 border-slate-200 shadow-[0_0_20px_rgba(0,0,0,0.06)] overflow-hidden" id="cart-inline-sidebar">
        <div class="p-4 border-b border-slate-200 shrink-0">
            <h2 class="text-lg font-bold text-slate-800 m-0" x-text="'{{ __('order_form.cart') }} (' + items.length + ')'"></h2>
        </div>
        <div class="flex-1 overflow-y-auto overflow-x-hidden p-4 space-y-3 min-h-0">
            <template x-if="items.length === 0">
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    <p class="text-slate-600 font-medium mb-1">{{ __('order_form.cart_empty') }}</p>
                    <p class="text-sm text-slate-500">{{ __('order_form.cart_add_first') }}</p>
                </div>
            </template>
            <template x-for="(item, idx) in items" :key="item._id || idx">
                <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                    <div class="text-sm font-medium text-slate-700 truncate" x-text="(item.url || '').trim() ? item.url.substring(0, 50) + (item.url.length > 50 ? '...' : '') : '{{ __('order_form.product_num') }} ' + (idx + 1)" dir="ltr"></div>
                    <div class="text-xs text-slate-500 mt-0.5" x-text="(item.qty || '1') + ' × ' + (item.currency || 'USD') + ' ' + (item.price || '—')" dir="ltr"></div>
                </div>
            </template>
            <div class="pt-3 border-t border-slate-200">
                <p class="text-[0.7rem] font-normal text-stone-400" x-text="productCountText()"></p>
                <p class="text-slate-700 font-semibold text-sm" x-text="totalText()"></p>
                <button type="button" x-show="items.length > 0" @click="submitOrder()" :disabled="submitting"
                        class="w-full mt-3 py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!submitting">{{ __('order_form.confirm_order') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('order_form.submitting') }}...</span>
                </button>
            </div>
        </div>
    </aside>
</div>

{{-- Mobile: Bottom-sheet cart summary --}}
<div class="md:hidden">
    {{-- Floating bar: summary + Review cart button — solid bg so it stays visible; pointer-events-none on summary area so Add product above remains tappable --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white px-4 py-3 flex justify-between items-center gap-4 shadow-[0_-4px_24px_rgba(0,0,0,0.12)] border-t border-slate-200 z-[100] pointer-events-none"
         style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
        <div class="flex flex-col gap-0.5 flex-1 min-w-0 pointer-events-none">
            <span class="text-xs font-medium text-slate-600 whitespace-nowrap overflow-hidden text-ellipsis" x-text="productCountText()"></span>
            <span class="text-sm font-semibold text-slate-800 whitespace-nowrap" x-text="totalText()"></span>
        </div>
        <button type="button" @click="cartSheetOpen = true"
                class="shrink-0 min-w-[120px] py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors pointer-events-auto">
            {{ __('order_form.review_cart') }}
        </button>
    </div>

    {{-- Bottom sheet --}}
    <div x-show="cartSheetOpen" x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="cartSheetOpen = false"
         class="fixed inset-0 z-[3000] flex flex-col">
        <div class="absolute inset-0 bg-black/40" @click="cartSheetOpen = false"></div>
        <div x-show="cartSheetOpen" x-cloak
             class="relative w-full max-h-[88vh] mt-auto bg-white rounded-t-2xl shadow-2xl flex flex-col overflow-hidden overscroll-contain"
             @click.stop
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-full"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-full"
             style="padding-bottom: env(safe-area-inset-bottom);">
            {{-- Drag handle --}}
            <div class="flex justify-center pt-3 pb-1 shrink-0">
                <div class="w-10 h-1 rounded-full bg-slate-200" aria-hidden="true"></div>
            </div>
            <div class="flex items-center justify-between px-4 pb-3 border-b border-slate-200 shrink-0">
                <h2 class="text-lg font-bold text-slate-800 m-0" x-text="'{{ __('order_form.cart') }} (' + items.length + ')'"></h2>
                <button type="button" @click="cartSheetOpen = false" class="w-10 h-10 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-800 transition-colors" aria-label="{{ __('Close') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-3 min-h-0">
                <template x-if="items.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <p class="text-slate-600 font-medium mb-1">{{ __('order_form.cart_empty') }}</p>
                        <p class="text-sm text-slate-500">{{ __('order_form.cart_add_first') }}</p>
                    </div>
                </template>
                <template x-for="(item, idx) in items" :key="item._id || idx">
                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                        <div class="text-sm font-medium text-slate-700 truncate" x-text="(item.url || '').trim() ? item.url.substring(0, 50) + (item.url.length > 50 ? '...' : '') : '{{ __('order_form.product_num') }} ' + (idx + 1)" dir="ltr"></div>
                        <div class="text-xs text-slate-500 mt-0.5" x-text="(item.qty || '1') + ' × ' + (item.currency || 'USD') + ' ' + (item.price || '—')" dir="ltr"></div>
                    </div>
                </template>
                <div class="pt-3 border-t border-slate-200" x-show="items.length > 0">
                    <p class="text-[0.7rem] font-normal text-stone-400" x-text="productCountText()"></p>
                    <p class="text-stone-600 font-medium text-sm" x-text="totalText()"></p>
                    <button type="button" @click="submitOrder(); cartSheetOpen = false" :disabled="submitting"
                            class="w-full mt-3 py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!submitting">{{ __('order_form.confirm_order') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('order_form.submitting') }}...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Image zoom modal --}}
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

{{-- Login modal --}}
@livewire('guest-login-modal')

</div>
</div>

@push('scripts')
{{-- Base + cards logic (cart-inline extends newOrderFormCards) --}}
<script>
@include('livewire.partials._new-order-form-js')
</script>
<script>
@include('livewire.partials._new-order-form-cards-js')
</script>
<script>
function newOrderFormCartInline(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes) {
    return {
        ...newOrderFormCards(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes),
        cartSheetOpen: false,
    };
}
</script>
@endpush
