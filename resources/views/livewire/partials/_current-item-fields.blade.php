{{--
  Shared current-item fields for cart-style layouts (currentItem model).

  Use inside a <form wire:submit="addToCart"> block. Renders URL → Notes with paste buttons.
  File upload and submit button are layout-specific — add them after this include.

  Alpine context required (on the ancestor x-data element):
    doPasteCurrentItemField(field, ev) — generalized paste for any currentItem field
    doOpenCurrentItem()                — open/search the URL field
    currentItemPasteFeedback           — 'pasted' | 'opened' | null
    currentItemPasteField              — 'url' | 'color' | 'size' | 'qty' | 'price' | 'notes' | null
    pasteLabel, pastedLabel, openLabel, openedLabel — translated strings

  PHP context (available from the component render scope):
    $currentItem  — current item array (for currency display)
    $currencies   — currency list array
    $isRtl        — RTL flag (bool)

  @param string $labelClass   CSS classes for field labels (excluding 'block').
                              Default: 'text-xs text-slate-500 font-medium' (Cart compact style).
                              Cart Next uses: 'text-sm font-semibold text-slate-800'
  @param string $inputPy      Vertical padding class for text inputs.
                              Default: 'py-2'. Cart Next uses: 'py-2.5'
--}}
@php
    $labelClass = $labelClass ?? 'text-xs text-slate-500 font-medium';
    $inputPy    = $inputPy    ?? 'py-2';
    $rtl        = $isRtl ?? (app()->getLocale() === 'ar');
@endphp

<div class="grid grid-cols-6 gap-x-3 gap-y-2.5">

    {{-- URL — full width --}}
    <div class="col-span-6">
        <div class="flex flex-wrap items-center gap-2 mb-0.5" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
            <span class="{{ $labelClass }}">
                {{ __('order_form.th_url') }}
                <span class="order-field-optional">{{ __('order_form.optional') }}</span>
            </span>
            @include('livewire.partials._url-paste-open', ['mode' => 'current'])
        </div>
        <div dir="{{ $rtl ? 'rtl' : 'ltr' }}">
            <input type="text"
                   wire:model="currentItem.url"
                   class="order-form-input w-full px-3 {{ $inputPy }} border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 text-start min-h-[2.5rem] placeholder:text-slate-500 placeholder:opacity-100"
                   placeholder="{{ __('order_form.url_placeholder') }}"
                   dir="{{ $rtl ? 'rtl' : 'ltr' }}">
        </div>
    </div>

    {{-- Color — half width --}}
    <div class="col-span-3">
        <div class="flex flex-wrap items-center gap-2 mb-0.5">
            <span class="{{ $labelClass }}">
                {{ __('order_form.th_color') }}
                <span class="order-field-optional">{{ __('order_form.optional') }}</span>
            </span>
            <button type="button" @click="doPasteCurrentItemField('color', $event)"
                :aria-label="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'color' ? pastedLabel : pasteLabel"
                class="text-[11px] text-slate-400 hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
                <span x-text="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'color' ? pastedLabel : pasteLabel"></span>
            </button>
        </div>
        <input type="text"
               wire:model="currentItem.color"
               class="order-form-input w-full px-3 {{ $inputPy }} border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-h-[2.5rem]">
    </div>

    {{-- Size — half width --}}
    <div class="col-span-3">
        <div class="flex flex-wrap items-center gap-2 mb-0.5">
            <span class="{{ $labelClass }}">
                {{ __('order_form.th_size') }}
                <span class="order-field-optional">{{ __('order_form.optional') }}</span>
            </span>
            <button type="button" @click="doPasteCurrentItemField('size', $event)"
                :aria-label="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'size' ? pastedLabel : pasteLabel"
                class="text-[11px] text-slate-400 hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
                <span x-text="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'size' ? pastedLabel : pasteLabel"></span>
            </button>
        </div>
        <input type="text"
               wire:model="currentItem.size"
               class="order-form-input w-full px-3 {{ $inputPy }} border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-h-[2.5rem]">
    </div>

    {{-- Qty + Price + Currency — qty narrow; price (+ paste) flexes --}}
    <div class="col-span-6 flex flex-nowrap items-end gap-2">

        {{-- Qty — fixed narrow width (digits only) --}}
        <div class="shrink-0 w-[3.75rem] sm:w-20">
            <span class="{{ $labelClass }} block mb-0.5">
                {{ __('order_form.th_qty') }}
                <span class="order-field-optional">{{ __('order_form.optional') }}</span>
            </span>
            <input type="text"
                   wire:model="currentItem.qty"
                   class="order-form-input w-full px-2 sm:px-3 {{ $inputPy }} border border-primary-100 rounded-lg text-sm bg-white h-10 text-center sm:text-start focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"
                   placeholder="1"
                   dir="ltr">
        </div>

        {{-- Price --}}
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                <span class="{{ $labelClass }}">
                    {{ __('order_form.th_price_per_unit') }}
                    <span class="order-field-optional">{{ __('order_form.optional') }}</span>
                </span>
                <button type="button" @click="doPasteCurrentItemField('price', $event)"
                    :aria-label="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'price' ? pastedLabel : pasteLabel"
                    class="text-[11px] text-slate-400 hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
                    <span x-text="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'price' ? pastedLabel : pasteLabel"></span>
                </button>
            </div>
            <input type="text"
                   wire:model="currentItem.price"
                   class="order-form-input w-full px-3 {{ $inputPy }} min-w-[4ch] border border-primary-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"
                   placeholder="{{ __('placeholder.amount') }}"
                   dir="ltr">
        </div>

        {{-- Currency — label-only (same as Cards / _currency-dropdown) --}}
        <div x-data="{ open: false }" class="relative min-w-0 flex-1 min-w-[5rem]">
            <span class="block {{ $labelClass }} mb-0.5">
                {{ __('order_form.th_currency') }}
                <span class="order-field-optional">{{ __('order_form.optional') }}</span>
            </span>
            @php
                $curBtnCode = $currentItem['currency'] ?? 'USD';
                $curBtn = $currencies[$curBtnCode] ?? [];
                $curBtnLabel = $curBtn['label'] ?? $curBtnCode;
            @endphp
            <button type="button" @click="open = !open"
                class="order-form-input w-full h-10 px-3 py-2 rounded-lg text-sm text-start bg-white border border-primary-100 hover:border-primary-200 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 inline-flex items-center justify-between gap-1.5"
                title="{{ $curBtnLabel }}">
                <span class="truncate">{{ $curBtnLabel }}</span>
                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse x-cloak @click.outside="open = false"
                class="absolute top-full mt-1 z-30 w-full max-w-[14rem] bg-white rounded-lg shadow-lg border border-slate-200 py-1 max-h-56 overflow-y-auto scrollbar-hide {{ $rtl ? 'right-0 left-auto' : 'left-0 right-auto' }}">
                @foreach ($currencies ?? [] as $code => $data)
                @php
                    $rowLabel = $data['label'] ?? $code;
                    $rowSym = $data['symbol'] ?? '';
                    $rowTitle = $rowSym !== '' ? $rowLabel.' ('.$rowSym.')' : $rowLabel;
                @endphp
                <button type="button"
                        data-code="{{ $code }}"
                        title="{{ $rowTitle }}"
                        @click="$wire.set('currentItem.currency', $event.currentTarget.dataset.code); open = false"
                        class="w-full px-3 py-2 text-start text-sm hover:bg-primary-50 focus:bg-primary-50 focus:outline-none transition-colors whitespace-nowrap {{ ($currentItem['currency'] ?? 'USD') === $code ? 'bg-primary-50 text-primary-700 font-medium' : '' }}">
                    {{ $rowLabel }}
                </button>
                @endforeach
            </div>
        </div>

    </div>{{-- /qty-price-currency row --}}

    {{-- Notes — full width --}}
    <div class="col-span-6">
        <div class="flex flex-wrap items-center gap-2 mb-0.5">
            <span class="{{ $labelClass }}">
                {{ __('order_form.th_notes') }}
                <span class="order-field-optional">{{ __('order_form.optional') }}</span>
            </span>
            <button type="button" @click="doPasteCurrentItemField('notes', $event)"
                :aria-label="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'notes' ? pastedLabel : pasteLabel"
                class="text-[11px] text-slate-400 hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
                <span x-text="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'notes' ? pastedLabel : pasteLabel"></span>
            </button>
        </div>
        <textarea wire:model="currentItem.notes"
                  rows="2"
                  class="order-form-input w-full px-3 {{ $inputPy }} border border-primary-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-h-[2.5rem]"
                  placeholder="{{ __('order_form.notes_placeholder') }}"></textarea>
    </div>

</div>{{-- /grid --}}
