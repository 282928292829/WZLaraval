{{-- Layout: Cart (Add to cart) — Form + Add to Cart, sidebar on desktop, bottom-sheet on mobile. /new-order-cart --}}
<div>
@php
    $isRtl = app()->getLocale() === 'ar';
    $cartSummary = $cartSummary ?? ['subtotal' => 0, 'commission' => 0, 'total' => 0, 'filledCount' => 0];
    $isGuest = auth()->guest();
@endphp

<div class="bg-slate-50 text-slate-800 font-[family-name:var(--font-family-arabic)] min-h-screen"
     data-guest="{{ $isGuest ? 'true' : 'false' }}"
     :data-attach-blocked="attachBlocked"
     x-data="newOrderFormCart({{ $isGuest ? 'true' : 'false' }})"
     x-init="initCartDraft()"
     @notify.window="showNotify($event.detail.type, $event.detail.message)"
     @cart-emptied.window="cartOpen = false"
     @save-cart-draft.window="saveCartDraftToStorage($event.detail.items, $event.detail.notes)"
     @open-login-modal-attach.window="$wire.openLoginModalForAttach()"
     @user-logged-in.window="attachBlocked = false"
     @keydown.escape.window="if (showDraftPrompt) { showDraftPrompt = false; }">
    <div x-ref="toasts" id="toast-container-cart"></div>

    <div class="flex flex-col md:flex-row min-h-[calc(100vh-56px)]">
        {{-- Main: Add-product form (left) --}}
        <div class="flex-1 min-w-0 p-4 md:pr-0 pb-24 md:pb-6">
            <div class="max-w-2xl mx-auto">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5 relative z-10">
                    <div>
                        <h1 class="text-lg md:text-xl font-bold text-slate-800 m-0">{{ __('order_form.create_new_order') }}</h1>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                    @if ($showAddTestItems ?? false)
                    <button type="button" wire:click="addFiveTestItems" wire:loading.attr="disabled" class="inline-flex items-center justify-center min-h-[44px] py-2 px-4 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 hover:text-slate-800 border-none rounded-lg cursor-pointer transition-colors disabled:opacity-60">{{ __('order.dev_add_5_test_items') }}</button>
                    @endif
                    <button type="button"
                            @click="focusCartOnDesktop()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-sm bg-primary-500/10 text-primary-600 border-2 border-primary-500/30 hover:bg-primary-500/20 hover:border-primary-500/50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        {{ __('order_form.cart') }}
                        @if (count($items) > 0)
                        <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-primary-500 text-white text-xs font-bold">{{ count($items) }}</span>
                        @endif
                    </button>
                </div>

                @include('livewire.partials._order-tips')

                {{-- Draft restore prompt — shown instead of silently restoring (guests only) --}}
                @if ($isGuest)
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
                @endif

                <form wire:submit="addToCart" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
                    @include('livewire.partials._current-item-fields', [
                        'labelClass' => 'text-xs text-slate-500 font-medium',
                        'inputPy'    => 'py-2',
                    ])
                    <div class="mb-4" x-data="{ fileName: '' }">
                        <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_files') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                        <div class="flex items-center gap-3">
                            <input type="file" id="new-order-cart-file-input" wire:model="currentItemFiles" accept="{{ implode(',', $allowedMimeTypes ?? allowed_upload_mime_types()) }}" class="sr-only" {{ ($maxImagesPerItem ?? 1) > 1 ? 'multiple' : '' }} @change="fileName = $event.target.files.length ? ($event.target.files.length > 1 ? $event.target.files.length + ' files' : $event.target.files[0].name) : ''">
                            <label for="new-order-cart-file-input"
                                   @click="if ($root.dataset.attachBlocked === 'true') { $event.preventDefault(); $wire.openLoginModalForAttach(); }"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer bg-primary-50 text-primary-600 hover:bg-primary-100 border border-primary-200 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                {{ __('order_form.choose_file') }}
                            </label>
                            <span x-show="fileName" x-text="fileName" class="text-xs text-slate-500 truncate max-w-[12rem]" x-cloak></span>
                            <span x-show="!fileName" class="text-xs text-slate-400">{{ __('order_form.no_file_chosen') }}</span>
                        </div>
                        <p class="text-[0.65rem] text-slate-500 mt-1">{{ ($maxImagesPerItem ?? 1) > 1 ? __('order_form.file_info_bulk', ['max' => $maxImagesPerItem ?? 1, 'size' => $maxFileSizeMb ?? 2]) : __('order_form.file_info_with_size', ['size' => $maxFileSizeMb ?? 2]) }}</p>
                    </div>
                    <button type="submit" wire:loading.attr="disabled" class="w-full min-h-[44px] py-3 px-4 inline-flex items-center justify-center gap-2 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="addToCart"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg> {{ __('order_form.add_to_cart') }}</span>
                        <span wire:loading wire:target="addToCart">...</span>
                    </button>
                </form>

                @if (count($items) > 0)
                <div class="mt-4 bg-primary-50 border border-primary-200 rounded-xl p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-slate-800">{{ __('order_form.items_in_cart', ['count' => count($items)]) }}</span>
                        <button type="button" @click="focusCartOnDesktop()" class="shrink-0 px-3 py-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700 hover:bg-primary-100 rounded-lg transition-colors">{{ __('order_form.review_cart') }}</button>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Desktop: Sidebar cart (always visible on md+) --}}
        <aside class="hidden md:flex md:w-[340px] lg:w-[380px] shrink-0 flex-col bg-white border-s border-slate-200 overflow-hidden transition-shadow duration-300"
               :class="{ 'ring-2 ring-primary-500 ring-offset-2': sidebarHighlight }"
               id="cart-sidebar">
            <div class="p-4 border-b border-slate-200 shrink-0">
                <h2 class="text-lg font-bold text-slate-800 m-0">{{ __('order_form.cart') }} ({{ count($items) }})</h2>
            </div>
            <div class="flex-1 overflow-y-auto overflow-x-hidden p-4 space-y-3 min-h-0">
                @if (count($items) === 0)
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    <p class="text-slate-600 font-medium mb-1">{{ __('order_form.cart_empty') }}</p>
                    <p class="text-sm text-slate-500">{{ __('order_form.cart_add_first') }}</p>
                </div>
                @else
                @foreach ($items as $idx => $item)
                <div wire:key="cart-item-{{ $idx }}" class="bg-slate-50 border border-slate-200 rounded-lg p-3 flex gap-3">
                    <div class="flex-1 min-w-0">
                        @if (!empty(trim($item['url'] ?? '')))
                        <a href="{{ safe_item_url($item['url']) ?? '#' }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-600 hover:underline truncate block" dir="ltr">{{ \Illuminate\Support\Str::limit($item['url'], 45) }}</a>
                        @else
                        <span class="text-sm text-slate-500">{{ __('order_form.product_num') }} {{ $idx + 1 }}</span>
                        @endif
                        <div class="flex flex-wrap gap-2 mt-1 text-xs text-slate-600">
                            @if (!empty(trim($item['color'] ?? '')))<span>{{ __('order_form.th_color') }}: {{ $item['color'] }}</span>@endif
                            @if (!empty(trim($item['size'] ?? '')))<span>{{ __('order_form.th_size') }}: {{ $item['size'] }}</span>@endif
                            <span dir="ltr">{{ $item['qty'] ?? 1 }} × {{ $item['currency'] ?? 'USD' }} {{ $item['price'] ?? __('order_form.no_price') }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 shrink-0">
                        <button type="button" wire:click="editCartItem({{ $idx }})" class="p-2 text-primary-600 hover:bg-primary-50 rounded-lg text-xs font-medium">{{ __('order_form.show_edit') }}</button>
                        <button type="button" wire:click="removeItem({{ $idx }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg text-xs font-medium">{{ __('order_form.remove') }}</button>
                    </div>
                </div>
                @endforeach
                <div class="pt-3 border-t border-slate-200">
                    <div class="flex justify-between items-center gap-2 mb-2">
                        <label class="text-sm font-semibold text-slate-800 m-0">{{ __('order_form.general_notes') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                        @if ($showResetAll ?? true)
                        <button type="button" wire:click="clearAllItems" wire:confirm="{{ __('order_form.reset_confirm') }}" class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">{{ __('order_form.reset_all') }}</button>
                        @endif
                    </div>
                    <textarea wire:model="orderNotes" rows="3" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" placeholder="{{ __('order_form.general_notes_ph') }}"></textarea>
                </div>
                @php $canShowTotal = $cartSummary['filledCount'] > 0 && count($items) > 0 && $cartSummary['total'] > 0; @endphp
                @if ($canShowTotal)
                <div class="bg-primary-50 border border-primary-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-slate-600">{{ __('order_form.products_value') }}</span><span dir="ltr">{{ number_format($cartSummary['subtotal'], 0) }} {{ __('SAR') }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-600">{{ __('order_form.commission_label') }}</span><span dir="ltr">{{ number_format($cartSummary['commission'], 0) }} {{ __('SAR') }}</span></div>
                    <div class="flex justify-between font-bold pt-2 border-t border-primary-200"><span>{{ __('order_form.total_label') }}</span><span dir="ltr">{{ number_format($cartSummary['total'], 0) }} {{ __('SAR') }}</span></div>
                </div>
                @elseif (count($items) > 0)
                <p class="text-sm text-slate-500">{{ __('order_form.cart_price_note') }}</p>
                @endif
                <button type="button" wire:click="submitOrder" wire:loading.attr="disabled" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed mt-2">
                    <span wire:loading.remove wire:target="submitOrder">{{ __('order_form.confirm_order') }}</span>
                    <span wire:loading wire:target="submitOrder">{{ __('order_form.submitting') }}...</span>
                </button>
                @endif
            </div>
        </aside>
    </div>

    {{-- Mobile: Bottom-sheet cart drawer --}}
    <div x-show="cartOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="cartOpen = false"
         class="md:hidden fixed inset-0 z-[3000] flex flex-col">
        <div class="absolute inset-0 bg-black/50" @click="cartOpen = false"></div>
        <div class="relative w-full max-h-[85vh] mt-auto bg-white rounded-t-2xl shadow-2xl flex flex-col" @click.stop style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="flex items-center justify-between p-4 border-b border-slate-200 shrink-0">
                <h2 class="text-lg font-bold text-slate-800 m-0">{{ __('order_form.cart') }} ({{ count($items) }})</h2>
                <button type="button" @click="cartOpen = false" class="w-10 h-10 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-800 transition-colors" aria-label="{{ __('Close') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto overflow-x-hidden p-4 space-y-3 min-h-0">
                @if (count($items) === 0)
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    <p class="text-slate-600 font-medium mb-1">{{ __('order_form.cart_empty') }}</p>
                    <p class="text-sm text-slate-500">{{ __('order_form.cart_add_first') }}</p>
                </div>
                @else
                @foreach ($items as $idx => $item)
                <div wire:key="cart-mobile-item-{{ $idx }}" class="bg-slate-50 border border-slate-200 rounded-lg p-3 flex gap-3">
                    <div class="flex-1 min-w-0">
                        @if (!empty(trim($item['url'] ?? '')))
                        <a href="{{ safe_item_url($item['url']) ?? '#' }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-600 hover:underline truncate block" dir="ltr">{{ \Illuminate\Support\Str::limit($item['url'], 50) }}</a>
                        @else
                        <span class="text-sm text-slate-500">{{ __('order_form.product_num') }} {{ $idx + 1 }}</span>
                        @endif
                        <div class="flex flex-wrap gap-2 mt-1 text-xs text-slate-600">
                            @if (!empty(trim($item['color'] ?? '')))<span>{{ __('order_form.th_color') }}: {{ $item['color'] }}</span>@endif
                            @if (!empty(trim($item['size'] ?? '')))<span>{{ __('order_form.th_size') }}: {{ $item['size'] }}</span>@endif
                            <span dir="ltr">{{ $item['qty'] ?? 1 }} × {{ $item['currency'] ?? 'USD' }} {{ $item['price'] ?? __('order_form.no_price') }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 shrink-0">
                        <button type="button" wire:click="editCartItem({{ $idx }})" @click="cartOpen = false" class="p-2 text-primary-600 hover:bg-primary-50 rounded-lg text-xs font-medium">{{ __('order_form.show_edit') }}</button>
                        <button type="button" wire:click="removeItem({{ $idx }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg text-xs font-medium">{{ __('order_form.remove') }}</button>
                    </div>
                </div>
                @endforeach
                <div class="pt-3 border-t border-slate-200">
                    <div class="flex justify-between items-center gap-2 mb-2">
                        <label class="text-sm font-semibold text-slate-800 m-0">{{ __('order_form.general_notes') }}</label>
                        @if ($showResetAll ?? true)
                        <button type="button" wire:click="clearAllItems" wire:confirm="{{ __('order_form.reset_confirm') }}" class="bg-transparent border-none text-slate-400 text-sm underline cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">{{ __('order_form.reset_all') }}</button>
                        @endif
                    </div>
                    <textarea wire:model="orderNotes" rows="3" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" placeholder="{{ __('order_form.general_notes_ph') }}"></textarea>
                </div>
                @php $canShowTotalMobile = $cartSummary['filledCount'] > 0 && count($items) > 0 && $cartSummary['total'] > 0; @endphp
                @if ($canShowTotalMobile)
                <div class="bg-primary-50 border border-primary-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-slate-600">{{ __('order_form.products_value') }}</span><span dir="ltr">{{ number_format($cartSummary['subtotal'], 0) }} {{ __('SAR') }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-600">{{ __('order_form.commission_label') }}</span><span dir="ltr">{{ number_format($cartSummary['commission'], 0) }} {{ __('SAR') }}</span></div>
                    <div class="flex justify-between font-bold pt-2 border-t border-primary-200"><span>{{ __('order_form.total_label') }}</span><span dir="ltr">{{ number_format($cartSummary['total'], 0) }} {{ __('SAR') }}</span></div>
                </div>
                @elseif (count($items) > 0)
                <p class="text-sm text-slate-500">{{ __('order_form.cart_price_note') }}</p>
                @endif
                <button type="button" wire:click="submitOrder" wire:loading.attr="disabled" class="w-full py-3 px-4 rounded-lg font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed mt-2">
                    <span wire:loading.remove wire:target="submitOrder">{{ __('order_form.confirm_order') }}</span>
                    <span wire:loading wire:target="submitOrder">{{ __('order_form.submitting') }}...</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Mobile: Thumb-reachable FAB to open cart (visible when cart has items) --}}
    @if (count($items) > 0)
    <button type="button"
            x-show="!cartOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="cartOpen = true"
            class="md:hidden fixed z-[2900] w-14 h-14 rounded-full bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/30 flex items-center justify-center hover:from-primary-600 hover:to-primary-500 active:scale-95 transition-all"
            style="bottom: max(1.5rem, env(safe-area-inset-bottom)); right: max(1.5rem, env(safe-area-inset-right)); left: auto;"
            aria-label="{{ __('order_form.cart') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        <span class="absolute -top-1 -end-1 min-w-[1.25rem] h-5 px-1.5 rounded-full bg-white text-primary-600 text-xs font-bold flex items-center justify-center border border-primary-200">{{ count($items) }}</span>
    </button>
    @endif
</div>
    @livewire('guest-login-modal')
</div>

@push('scripts')
<script>
function newOrderFormCart(attachBlocked = false) {
    const orderNotify = (typeof window.orderNotifyMixin === 'function') ? window.orderNotifyMixin({ closeLabel: @js(__('Close')) }) : {};
    const orderDraft = (typeof window.orderDraftMixin === 'function') ? window.orderDraftMixin({ restoreVia: 'wire', draftRestoredMsg: @js(__('order_form.draft_restored')) }) : {};
    return {
        ...orderNotify,
        ...orderDraft,
        attachBlocked: !!attachBlocked,
        cartOpen: false,
        sidebarHighlight: false,
        showDraftPrompt: false,
        pendingDraftItems: null,
        pendingDraftNotes: '',
        tipsOpen: false,
        tipsHidden: false,
        currentItemPasteFeedback: null,
        currentItemPasteField: null,
        noLinkToOpenMsg: @js(__('order_form.no_link_to_open')),
        clipboardEmptyMsg: @js(__('order_form.clipboard_empty')),
        pasteFailedMsg: @js(__('order_form.paste_failed')),
        pasteTooLongMsg: @js(__('order_form.paste_too_long')),
        pasteLabel: @js(__('order_form.paste')),
        pastedLabel: @js(__('order_form.pasted')),
        openLabel: @js(__('order_form.open')),
        openedLabel: @js(__('order_form.opened')),
        doPasteCurrentItem(ev) { this.doPasteCurrentItemField('url', ev); },
        doPasteCurrentItemField(field, ev) {
            if (!navigator.clipboard?.readText) {
                this.showNotify('error', this.pasteFailedMsg);
                return;
            }
            const maxLen = (field === 'qty' || field === 'price') ? 50 : 2000;
            navigator.clipboard.readText().then(t => {
                const text = String(t);
                if (!text.trim()) {
                    this.showNotify('error', this.clipboardEmptyMsg);
                    return;
                }
                const trimmed = text.length > maxLen ? text.slice(0, maxLen) : text;
                if (text.length > maxLen) this.showNotify('error', this.pasteTooLongMsg);
                this.$wire.set('currentItem.' + field, trimmed);
                this.currentItemPasteFeedback = 'pasted';
                this.currentItemPasteField = field;
                setTimeout(() => { this.currentItemPasteFeedback = null; this.currentItemPasteField = null; }, 1500);
            }).catch(() => { this.showNotify('error', this.pasteFailedMsg); });
        },
        doOpenCurrentItem() {
            const v = (this.$wire.get('currentItem.url') || '').trim();
            if (!v) {
                this.showNotify('error', this.noLinkToOpenMsg);
                return;
            }
            const url = v.startsWith('http') ? v : 'https://' + v;
            let isUrl = false;
            try {
                const parsed = new URL(url);
                const host = (parsed.hostname || '').toLowerCase();
                if (host && (host.includes('.') || host === 'localhost') && !host.includes(' ')) {
                    isUrl = true;
                }
            } catch {}
            if (isUrl) {
                window.open(url, '_blank');
            } else {
                window.open('https://www.google.com/search?q=' + encodeURIComponent(v), '_blank');
            }
            this.currentItemPasteFeedback = 'opened';
            setTimeout(() => { this.currentItemPasteFeedback = null; }, 1500);
        },
        focusCartOnDesktop() {
            this.cartOpen = true;
            if (window.innerWidth >= 768) {
                this.sidebarHighlight = true;
                this.$nextTick(() => {
                    const sb = document.getElementById('cart-sidebar');
                    if (sb) sb.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
                setTimeout(() => { this.sidebarHighlight = false; }, 1200);
            }
        },
        discardDraft() {
            try {
                localStorage.removeItem('wz_order_form_draft');
                localStorage.removeItem('wz_order_form_notes');
                localStorage.removeItem('wz_opus46_draft');
                localStorage.removeItem('wz_opus46_notes');
            } catch {}
            this.pendingDraftItems = null;
            this.pendingDraftNotes = '';
            this.showDraftPrompt = false;
        },
        initCartDraft() {
            @if ($isGuest && count($items) === 0)
            const draft = this.peekDraft();
            if (draft) {
                this.pendingDraftItems = draft.items;
                this.pendingDraftNotes = draft.notes;
                this.showDraftPrompt = true;
            }
            @endif
        },
        saveCartDraftToStorage(items, notes) {
            try {
                if (Array.isArray(items) && items.length > 0) {
                    localStorage.setItem('wz_order_form_draft', JSON.stringify(items));
                    localStorage.setItem('wz_order_form_notes', notes || '');
                } else {
                    localStorage.removeItem('wz_order_form_draft');
                    localStorage.removeItem('wz_order_form_notes');
                }
            } catch (_) {}
        },
    };
}
</script>
@endpush
