@php
    $currencies    = $currencies ?? [];
    $expandedIndex = $expandedIndex ?? 0;
    $isExpanded    = $index === $expandedIndex;
@endphp

<div
    data-item-index="{{ $index }}"
    x-data="czzItem(@js($item['url']), {{ $index }}, {{ $isExpanded ? 'true' : 'false' }})"
    class="czz-item bg-white rounded-xl border border-gray-200 overflow-hidden"
>
    {{-- Mobile summary bar (hidden on desktop via CSS) --}}
    <div class="czz-item-summary flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
        <span class="text-sm font-semibold text-gray-700 truncate flex-1 me-3" x-text="label"></span>
        <div class="flex items-center gap-2 shrink-0">
            <button
                type="button"
                @click="expanded = !expanded"
                class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 bg-white hover:bg-gray-100 transition-colors"
                x-text="expanded ? '{{ __('Hide') }}' : '{{ __('Show / Edit') }}'"
            ></button>
            <button
                type="button"
                wire:click="removeItem({{ $index }})"
                class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-500 bg-white hover:bg-red-50 transition-colors"
            >{{ __('Remove product') }}</button>
        </div>
    </div>

    {{-- Fields: mobile = collapsible block, desktop = CSS grid row --}}
    <div
        :class="expanded ? '' : 'czz-collapsed'"
        class="czz-item-fields p-4 space-y-3 lg:p-0 lg:space-y-0"
    >
        {{-- # (desktop only) --}}
        <div class="czz-col-num hidden lg:flex">{{ $index + 1 }}</div>

        {{-- URL --}}
        <div>
            <label class="czz-label block text-xs font-semibold text-gray-500 mb-1">{{ __('Product link') }}</label>
            <input
                type="text"
                wire:model.live.debounce.400ms="items.{{ $index }}.url"
                x-model="urlValue"
                class="item-url w-full rounded-lg border border-gray-200 px-3 py-2.5 lg:px-2 lg:py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 placeholder-gray-300"
                placeholder="{{ __('Product link or description') }}"
                inputmode="url"
                autocomplete="off"
            >
        </div>

        {{-- Qty --}}
        <div>
            <label class="czz-label block text-xs font-semibold text-gray-500 mb-1">{{ __('Quantity') }}</label>
            <input
                type="number"
                wire:model.live.debounce.400ms="items.{{ $index }}.qty"
                class="item-qty w-full rounded-lg border border-gray-200 px-3 py-2.5 lg:px-2 lg:py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400"
                value="{{ $item['qty'] ?: '1' }}"
                inputmode="numeric"
                min="1"
            >
        </div>

        {{-- Color --}}
        <div class="lg:block">
            <label class="czz-label block text-xs font-semibold text-gray-500 mb-1">{{ __('Color') }}</label>
            <input
                type="text"
                wire:model.live.debounce.400ms="items.{{ $index }}.color"
                class="item-color w-full rounded-lg border border-gray-200 px-3 py-2.5 lg:px-2 lg:py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400"
                placeholder="{{ __('Color') }}"
            >
        </div>

        {{-- Size --}}
        <div class="lg:block">
            <label class="czz-label block text-xs font-semibold text-gray-500 mb-1">{{ __('Size') }}</label>
            <input
                type="text"
                wire:model.live.debounce.400ms="items.{{ $index }}.size"
                class="item-size w-full rounded-lg border border-gray-200 px-3 py-2.5 lg:px-2 lg:py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400"
                placeholder="{{ __('Size') }}"
            >
        </div>

        {{-- Price --}}
        <div class="lg:block">
            <label class="czz-label block text-xs font-semibold text-gray-500 mb-1">{{ __('Price') }}</label>
            <input
                type="text"
                wire:model.live.debounce.400ms="items.{{ $index }}.price"
                class="item-price w-full rounded-lg border border-gray-200 px-3 py-2.5 lg:px-2 lg:py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400"
                placeholder="{{ __('placeholder.amount') }}"
                inputmode="decimal"
            >
        </div>

        {{-- Currency --}}
        <div class="lg:block">
            <label class="czz-label block text-xs font-semibold text-gray-500 mb-1">{{ __('Currency') }}</label>
            <select
                wire:model.live="items.{{ $index }}.currency"
                class="item-currency w-full rounded-lg border border-gray-200 px-3 py-2.5 lg:px-2 lg:py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 bg-white"
            >
                @foreach($currencies as $code => $meta)
                    <option value="{{ $code }}" {{ ($item['currency'] ?? $defaultCurrency) === $code ? 'selected' : '' }}>
                        {{ $code }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Notes --}}
        <div class="lg:block">
            <label class="czz-label block text-xs font-semibold text-gray-500 mb-1">{{ __('Notes') }}</label>
            <input
                type="text"
                wire:model.live.debounce.400ms="items.{{ $index }}.notes"
                class="item-notes w-full rounded-lg border border-gray-200 px-3 py-2.5 lg:px-2 lg:py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400"
                placeholder="{{ __('e.g. if blue not available, choose green') }}"
            >
        </div>

        {{-- File --}}
        <div class="lg:flex lg:items-center lg:justify-center">
            <input
                type="file"
                class="hidden czz-file-input"
                accept="image/*,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                @change="handleFile($event)"
                wire:model="itemFiles.{{ $index }}"
            >
            <div class="flex items-center gap-2 flex-wrap">
                <button
                    type="button"
                    @click="triggerFile({{ auth()->check() ? 'true' : 'false' }})"
                    class="flex items-center gap-1 px-3 py-2 lg:px-2 lg:py-1.5 rounded-lg border border-dashed border-gray-300 text-gray-500 hover:border-orange-400 hover:text-orange-500 text-sm transition-colors"
                    title="{{ __('Attach file') }}"
                >📎 <span class="lg:hidden">{{ __('Attach file') }}</span></button>
                <div x-ref="previewBox" class="flex flex-wrap gap-1"></div>
            </div>
            <p class="czz-label text-xs text-gray-400 mt-1">{{ __('One file per product') }}</p>
        </div>

        {{-- Delete (desktop inline) --}}
        <div class="hidden lg:flex items-center justify-center">
            <button
                type="button"
                wire:click="removeItem({{ $index }})"
                class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                title="{{ __('Remove product') }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>

    </div>
</div>
