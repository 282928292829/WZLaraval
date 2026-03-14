{{-- Layout: Cart (Add to cart) — Form + Add to Cart, sidebar on desktop, bottom-sheet on mobile. /new-order-cart --}}
<div>
@php
    $isRtl = app()->getLocale() === 'ar';
    $cartSummary = $cartSummary ?? ['subtotal' => 0, 'commission' => 0, 'total' => 0, 'filledCount' => 0];
    $isGuest = auth()->guest();
@endphp

<div class="bg-slate-50 text-slate-800 font-[family-name:var(--font-family-arabic)] min-h-screen"
     x-data="cartPageNotify()"
     x-init="initCartDraft()"
     @notify.window="showNotify($event.detail.type, $event.detail.message)"
     @cart-emptied.window="cartOpen = false"
     @save-cart-draft.window="saveCartDraftToStorage($event.detail.items, $event.detail.notes)">
    <div x-ref="toasts" id="toast-container-cart"></div>

    <div class="flex flex-col md:flex-row min-h-[calc(100vh-56px)]">
        {{-- Main: Add-product form (left) --}}
        <div class="flex-1 min-w-0 p-4 md:pr-0 pb-24 md:pb-6">
            <div class="max-w-2xl mx-auto">
                <div class="flex flex-nowrap items-center justify-between gap-3 mb-5">
                    <div>
                        <h1 class="text-lg md:text-xl font-bold text-slate-800 m-0">{{ __('Create new order') }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5 mb-0">{{ __('order_form.add_products_one_by_one') }}</p>
                    </div>
                    @if ($showAddTestItems ?? false)
                    <button type="button" wire:click="addFiveTestItems" class="shrink-0 text-xs text-slate-400 underline bg-transparent border-none cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">{{ __('order.dev_add_5_test_items') }}</button>
                    @endif
                    <button type="button"
                            @click="cartOpen = true"
                            class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-sm bg-primary-500/10 text-primary-600 border-2 border-primary-500/30 hover:bg-primary-500/20 hover:border-primary-500/50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        {{ __('order_form.cart') }}
                        @if (count($items) > 0)
                        <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-primary-500 text-white text-xs font-bold">{{ count($items) }}</span>
                        @endif
                    </button>
                </div>

                @include('livewire.partials._order-tips')

                <form wire:submit="addToCart" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-5">
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">{{ __('order_form.th_url') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                        <div dir="ltr">
                            <input type="text" wire:model="currentItem.url" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 text-left" placeholder="{{ __('order_form.url_placeholder') }}" dir="ltr">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_qty') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                            <input type="text" wire:model="currentItem.qty" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" placeholder="1" dir="ltr">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_color') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                            <input type="text" wire:model="currentItem.color" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_size') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                            <input type="text" wire:model="currentItem.size" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_price_per_unit') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                            <input type="text" wire:model="currentItem.price" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" placeholder="0" dir="ltr">
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_currency') }}</label>
                            <button type="button" @click="open = !open" class="order-form-input w-full h-10 px-3 py-2 rounded-lg text-sm text-start bg-white border border-primary-100 hover:border-primary-200 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 inline-flex items-center justify-between gap-1.5">
                                <span class="truncate">{{ ($currentItem['currency'] ?? 'USD') }} — {{ ($currencies[$currentItem['currency'] ?? 'USD'] ?? [])['label'] ?? ($currentItem['currency'] ?? 'USD') }}</span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-collapse x-cloak @click.outside="open = false" class="absolute top-full mt-1 z-30 w-full max-w-[14rem] bg-white rounded-lg shadow-lg border border-slate-200 py-1 max-h-56 overflow-y-auto scrollbar-hide {{ $isRtl ? 'right-0 left-auto' : 'left-0 right-auto' }}">
                                @foreach ($currencies ?? [] as $code => $data)
                                <button type="button" data-code="{{ $code }}" @click="$wire.set('currentItem.currency', $event.currentTarget.dataset.code); open = false" class="w-full px-3 py-2 text-start text-sm hover:bg-primary-50 focus:bg-primary-50 focus:outline-none transition-colors whitespace-nowrap {{ ($currentItem['currency'] ?? 'USD') === $code ? 'bg-primary-50 text-primary-700 font-medium' : '' }}">{{ $code }} — {{ $data['label'] ?? $code }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_notes') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                        <textarea wire:model="currentItem.notes" rows="2" class="order-form-input w-full px-3 py-2.5 border border-primary-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" placeholder="{{ __('order_form.notes_placeholder') }}"></textarea>
                    </div>
                    <div class="mb-4" x-data="{ fileName: '' }">
                        <label class="block text-sm font-semibold text-slate-800 mb-1">{{ __('order_form.th_files') }} <span class="order-field-optional">{{ __('order_form.optional') }}</span></label>
                        <div class="flex items-center gap-3">
                            <input type="file" id="new-order-cart-file-input" wire:model="currentItemFile" accept="{{ implode(',', $allowedMimeTypes ?? allowed_upload_mime_types()) }}" class="sr-only" @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''">
                            <label for="new-order-cart-file-input" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer bg-primary-50 text-primary-600 hover:bg-primary-100 border border-primary-200 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                {{ __('order_form.choose_file') }}
                            </label>
                            <span x-show="fileName" x-text="fileName" class="text-xs text-slate-500 truncate max-w-[12rem]" x-cloak></span>
                            @if (!$currentItemFile)
                            <span x-show="!fileName" class="text-xs text-slate-400">{{ __('order_form.no_file_chosen') }}</span>
                            @endif
                        </div>
                        <p class="text-[0.65rem] text-slate-500 mt-1">{{ __('order_form.file_info') }}</p>
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
                        <button type="button" @click="cartOpen = true" class="shrink-0 px-3 py-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700 hover:bg-primary-100 rounded-lg transition-colors">{{ __('order_form.review_cart') }}</button>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Desktop: Sidebar cart (always visible on md+) --}}
        <aside class="hidden md:flex md:w-[340px] lg:w-[380px] shrink-0 flex-col bg-white border-s border-slate-200 overflow-hidden" id="cart-sidebar">
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
</div>
    @include('livewire.partials._order-login-modal')
</div>

@push('scripts')
<script>
function cartPageNotify() {
    return {
        cartOpen: false,
        initCartDraft() {
            @if ($isGuest && count($items) === 0)
            try {
                const raw = localStorage.getItem('wz_order_form_draft');
                const notes = localStorage.getItem('wz_order_form_notes') || '';
                if (raw) {
                    const data = JSON.parse(raw);
                    if (Array.isArray(data) && data.length > 0) {
                        this.$wire.loadGuestDraftFromStorage(data, notes);
                    }
                }
            } catch (_) {}
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
        showNotify(type, msg, duration) {
            const c = this.$refs.toasts;
            if (!c) return;
            const t = document.createElement('div');
            t.className = `toast ${type}`;
            const icon = type === 'error'
                ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:#ef4444;flex-shrink:0"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>'
                : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:#10b981;flex-shrink:0"><path d="M20 6L9 17l-5-5"/></svg>';
            const dur = duration ?? (type === 'error' ? 4000 : 700);
            const closeLabel = @js(__('Close'));
            t.innerHTML = `${icon}<span style="flex:1">${msg}</span><button type="button" class="toast-close" aria-label="${closeLabel}">×</button>`;
            c.appendChild(t);
            const closeToast = () => {
                t.style.animation = 'toastOut 0.4s ease forwards';
                setTimeout(() => t.remove(), 400);
            };
            t.querySelector('.toast-close').addEventListener('click', (e) => { e.stopPropagation(); closeToast(); });
            t.addEventListener('click', closeToast);
            setTimeout(() => { if (t.parentElement) closeToast(); }, dur);
        }
    };
}
</script>
@endpush
