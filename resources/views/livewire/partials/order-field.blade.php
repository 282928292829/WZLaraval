{{--
    Renders one order form field.
    Variables available: $field (array), $index (int), $currencies (array), $mobile (bool, default true)
    $mobile controls label rendering — desktop rows skip labels.
--}}
@php
    $key      = $field['key'];
    $locale   = app()->getLocale();
    $label    = $field['label_' . $locale] ?? $field['label_en'] ?? $key;
    $isMobile = $mobile ?? true;
@endphp

@switch($key)

    {{-- ── URL ──────────────────────────────────────────────── --}}
    @case('url')
        @if ($isMobile)
            <label for="field-url-{{ $index }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
        @endif
        <input
            id="field-url-{{ $index }}"
            type="text"
            wire:model.blur="items.{{ $index }}.url"
            placeholder="{{ $index === 0 ? __('order.url_placeholder') : '' }}"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400 bg-gray-50"
            :class="($wire.items[{{ $index }}]?.url || '').trim() ? 'border-green-300 bg-green-50/30' : ''"
            @input="saveDraft()"
        >
        @error("items.{$index}.url")
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
        @break

    {{-- ── QTY ─────────────────────────────────────────────── --}}
    @case('qty')
        @if ($isMobile)
            <label for="field-qty-{{ $index }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
        @endif
        <input
            id="field-qty-{{ $index }}"
            type="tel"
            wire:model.blur="items.{{ $index }}.qty"
            class="w-full rounded-lg border border-gray-200 px-2 py-2.5 text-sm text-center
                   focus:outline-none focus:ring-2 focus:ring-primary-300 bg-gray-50"
            @input="convertArabicNumerals($event); saveDraft()"
            @blur="recalculate()"
        >
        @error("items.{$index}.qty")
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
        @break

    {{-- ── SIZE ────────────────────────────────────────────── --}}
    @case('size')
        @if ($isMobile)
            <label for="field-size-{{ $index }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
        @endif
        <input
            id="field-size-{{ $index }}"
            type="text"
            wire:model.blur="items.{{ $index }}.size"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-primary-300 bg-gray-50"
            @input="saveDraft()"
        >
        @error("items.{$index}.size")
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
        @break

    {{-- ── COLOR ───────────────────────────────────────────── --}}
    @case('color')
        @if ($isMobile)
            <label for="field-color-{{ $index }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
        @endif
        <input
            id="field-color-{{ $index }}"
            type="text"
            wire:model.blur="items.{{ $index }}.color"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-primary-300 bg-gray-50"
            @input="saveDraft()"
        >
        @error("items.{$index}.color")
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
        @break

    {{-- ── PRICE ───────────────────────────────────────────── --}}
    @case('price')
        @if ($isMobile)
            <label for="field-price-{{ $index }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
        @endif
        <input
            id="field-price-{{ $index }}"
            type="text"
            inputmode="decimal"
            wire:model.blur="items.{{ $index }}.price"
            placeholder="0.00"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-primary-300 bg-gray-50"
            @input="convertArabicNumerals($event); saveDraft()"
            @blur="recalculate()"
        >
        @error("items.{$index}.price")
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
        @break

    {{-- ── CURRENCY ─────────────────────────────────────────── --}}
    @case('currency')
        @if ($isMobile)
            <label for="field-currency-{{ $index }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
        @endif
        <select
            id="field-currency-{{ $index }}"
            wire:model="items.{{ $index }}.currency"
            class="w-full rounded-lg border border-gray-200 px-2 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-primary-300 bg-gray-50"
            @change="preferredCurrency = $event.target.value; saveDraft()"
            @blur="recalculate()"
        >
            @foreach ($currencies as $code => $cur)
                <option value="{{ $code }}" @selected($items[$index]['currency'] === $code)>
                    {{ $cur['symbol'] }} {{ $code }}
                </option>
            @endforeach
        </select>
        @break

    {{-- ── NOTES ───────────────────────────────────────────── --}}
    @case('notes')
        @if ($isMobile)
            <label for="field-notes-{{ $index }}" class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
        @endif
        <input
            id="field-notes-{{ $index }}"
            type="text"
            wire:model.blur="items.{{ $index }}.notes"
            placeholder="{{ $index === 0 ? __('order.notes_placeholder') : '' }}"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-primary-300 bg-gray-50"
            @input="saveDraft()"
        >
        @error("items.{$index}.notes")
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
        @break

    {{-- ── FILE ────────────────────────────────────────────── --}}
    @case('file')
        @if ($isMobile)
            <label class="block text-xs font-medium text-gray-600 mb-1">
                {{ $label }}
                <span class="text-gray-400 font-normal">({{ __('order.file_limit') }})</span>
            </label>
            <div x-data="{ filePreview: null, fileName: null }" class="w-full">
                <template x-if="!fileName">
                    <label class="flex items-center gap-2 cursor-pointer border-2 border-dashed border-gray-200
                                  rounded-lg px-3 py-3 hover:border-primary-300 transition-colors">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span class="text-xs text-gray-500">{{ __('order.upload_file') }}</span>
                        <input
                            type="file"
                            wire:model="itemFiles.{{ $index }}"
                            accept="image/*,application/pdf,.xlsx,.xls"
                            class="sr-only"
                            @change="fileName = $event.target.files[0]?.name || null;
                                     const f = $event.target.files[0];
                                     if (f?.type.startsWith('image/')) { const r = new FileReader(); r.onload = e => filePreview = e.target.result; r.readAsDataURL(f); }"
                        >
                    </label>
                </template>
                <template x-if="fileName">
                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                        <template x-if="filePreview">
                            <img :src="filePreview" class="w-10 h-10 rounded object-cover shrink-0">
                        </template>
                        <template x-if="!filePreview">
                            <div class="w-10 h-10 rounded bg-blue-50 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </template>
                        <span x-text="fileName" class="text-xs text-gray-600 flex-1 truncate"></span>
                        <button type="button"
                                @click="fileName = null; filePreview = null; $wire.set('itemFiles.{{ $index }}', null)"
                                class="text-gray-400 hover:text-red-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        @else
            {{-- Desktop: compact file upload button --}}
            <div x-data="{ fileName: null }" class="flex items-center justify-center">
                <label class="cursor-pointer" :title="fileName || '{{ __('order.upload_file') }}'">
                    <template x-if="!fileName">
                        <div class="w-8 h-8 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center
                                    hover:border-primary-400 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                    </template>
                    <template x-if="fileName">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </template>
                    <input
                        type="file"
                        wire:model="itemFiles.{{ $index }}"
                        accept="image/*,application/pdf,.xlsx,.xls"
                        class="sr-only"
                        @change="fileName = $event.target.files[0]?.name || null"
                    >
                </label>
            </div>
        @endif
        @break

@endswitch
