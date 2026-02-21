{{--
    /new-order — Option 1: Responsive
    Mobile  → stacked cards (expand/collapse). Fields driven by $fieldConfig from settings.
    Desktop → flex row (all fields in one line).
--}}

@php
    $urlField      = collect($fieldConfig)->firstWhere('key', 'url');
    $locale        = app()->getLocale();
@endphp

<div
    x-data="newOrderForm(
        @js($exchangeRates),
        {{ $commissionThreshold }},
        {{ $commissionPct }},
        {{ $commissionFlat }},
        @js($currencies),
        {{ $maxProducts }},
        @js($defaultCurrency)
    )"
    x-init="init()"
    @notify.window="showNotify($event.detail.type, $event.detail.message)"
    class="min-h-screen bg-gray-50"
>

    {{-- ================================================================
         Toast notification
    ================================================================ --}}
    <div
        x-show="notification.visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-4 py-3 rounded-xl
               shadow-lg text-sm font-medium max-w-sm w-full"
        :class="notification.type === 'error'
            ? 'bg-red-50 text-red-700 border border-red-200'
            : 'bg-green-50 text-green-700 border border-green-200'"
        style="display:none"
    >
        <span x-show="notification.type === 'error'">⚠️</span>
        <span x-show="notification.type !== 'error'">✓</span>
        <span x-text="notification.message"></span>
    </div>

    {{-- ================================================================
         Sticky page header
    ================================================================ --}}
    <div class="bg-white border-b border-gray-100 sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('New Order') }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    <span x-text="filledCount"></span>
                    {{ __('order.products_added') }}
                    <span class="mx-1">·</span>
                    {{ __('order.max_products_label', ['max' => $maxProducts]) }}
                </p>
            </div>
            <button
                wire:click="submitOrder"
                wire:loading.attr="disabled"
                wire:target="submitOrder"
                class="inline-flex items-center gap-2 bg-primary-600 text-white text-sm font-semibold
                       px-4 py-2 rounded-lg hover:bg-primary-700 active:scale-95 transition-all disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="submitOrder">{{ __('order.submit_order') }}</span>
                <span wire:loading wire:target="submitOrder">{{ __('order.submitting') }}…</span>
            </button>
        </div>
    </div>

    {{-- ================================================================
         Tips box — collapsible, 30-day "don't show" localStorage
    ================================================================ --}}
    <div x-show="showTips" class="max-w-5xl mx-auto px-4 pt-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
             style="border-inline-start: 4px solid #f97316;">
            {{-- Header (always visible, click to expand/collapse) --}}
            <button
                type="button"
                @click="tipsExpanded = !tipsExpanded"
                class="w-full flex items-center justify-between px-4 py-3 text-start hover:bg-gray-50 transition-colors"
            >
                <h2 class="text-sm font-semibold text-gray-900">{{ __('order.tips_title') }}</h2>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
                     :class="tipsExpanded ? 'rotate-180' : ''"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Expandable body (collapsed by default) --}}
            <div x-show="tipsExpanded" x-collapse class="border-t border-gray-100">
                <div class="px-4 py-4">
                    <ul class="space-y-2.5 text-sm text-gray-600 leading-relaxed">
                        <li>{{ __('order.tips_tip1') }}</li>
                        <li>{{ __('order.tips_tip2') }}</li>
                        <li>{{ __('order.tips_tip3') }}</li>
                        <li>{{ __('order.tips_tip4') }}</li>
                        <li>{{ __('order.tips_tip5') }}</li>
                        <li>{{ __('order.tips_tip6') }}</li>
                        <li>{{ __('order.tips_tip7') }}</li>
                    </ul>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer select-none">
                            <input type="checkbox" @change="dismissTips()" class="rounded border-gray-300 text-primary-600 cursor-pointer">
                            <span>{{ __('order.tips_dont_show') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================
         DESKTOP TABLE HEADER (lg+)
         Uses the same flex classes as item rows so columns align perfectly.
    ================================================================ --}}
    <div class="max-w-5xl mx-auto px-4 pt-4">
        <div class="hidden lg:flex items-center gap-2 px-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wide">
            @foreach ($fieldConfig as $field)
                <div class="{{ $desktopWidths[$field['key']] ?? 'flex-1' }}">
                    {{ $field['label_' . $locale] ?? $field['label_en'] }}
                </div>
            @endforeach
            {{-- delete button column placeholder --}}
            <div class="w-6 shrink-0"></div>
        </div>
    </div>

    {{-- ================================================================
         ITEMS LIST
    ================================================================ --}}
    <div class="max-w-5xl mx-auto px-4 pb-36 space-y-2" id="items-container">

        @foreach ($items as $index => $item)
            <div
                wire:key="item-{{ $index }}"
                x-data="{ expanded: {{ $index === 0 ? 'true' : 'false' }}, showOptional: false }"
                class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm"
                :class="expanded ? 'ring-1 ring-primary-200' : ''"
            >

                {{-- =====================================================
                     MOBILE CARD (hidden on lg+)
                ===================================================== --}}
                <div class="lg:hidden">

                    {{-- Summary bar --}}
                    <button
                        type="button"
                        @click="expanded = !expanded"
                        class="w-full flex items-center justify-between px-4 py-3 text-start"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="shrink-0 flex items-center justify-center w-6 h-6 rounded-full
                                         bg-gray-100 text-xs font-bold text-gray-500">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-sm text-gray-700 min-w-0 flex-1 break-words line-clamp-2">
                                @if ($item['url'])
                                    {{ Str::limit($item['url'], 38) }}
                                @else
                                    <span class="text-gray-400">{{ __('order.new_product') }}</span>
                                @endif
                            </span>
                            @if ($item['price'] && $item['qty'])
                                <span class="text-xs text-gray-500 shrink-0">
                                    {{ $item['qty'] }}×{{ $item['price'] }} {{ $item['currency'] }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if (count($items) > 1)
                                <button
                                    type="button"
                                    @click.stop="$wire.removeItem({{ $index }})"
                                    class="p-1 text-gray-300 hover:text-red-400 transition-colors rounded"
                                    wire:loading.attr="disabled"
                                    aria-label="{{ __('order.remove_item') }}"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                 :class="expanded ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    {{-- Expanded body --}}
                    <div x-show="expanded" x-collapse class="border-t border-gray-100">
                        <div class="px-4 pt-3 pb-4 space-y-3">

                            {{-- URL — always full width and first --}}
                            @if ($urlField)
                                @include('livewire.partials.order-field', [
                                    'field'  => $urlField,
                                    'index'  => $index,
                                    'mobile' => true,
                                ])
                            @endif

                            {{-- Required non-URL fields in 2-col grid --}}
                            @if (count($requiredFields) > 0)
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($requiredFields as $field)
                                        <div class="{{ in_array($field['key'], ['notes']) ? 'col-span-2' : 'col-span-1' }}">
                                            @include('livewire.partials.order-field', [
                                                'field'  => $field,
                                                'index'  => $index,
                                                'mobile' => true,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Optional toggle (only if there are optional fields) --}}
                            @if (count($optionalFields) > 0)
                                @php
                                    $locale = app()->getLocale();
                                    $optionalLabels = collect($optionalFields)
                                        ->pluck('label_' . $locale)
                                        ->filter()
                                        ->implode('، ');
                                @endphp
                                <button
                                    type="button"
                                    @click="showOptional = !showOptional"
                                    class="flex items-center gap-1.5 text-xs text-primary-700 font-medium hover:text-primary-800"
                                >
                                    <svg class="w-3.5 h-3.5 transition-transform"
                                         :class="showOptional ? 'rotate-90' : ''"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span x-show="!showOptional">+ {{ $optionalLabels }}</span>
                                    <span x-show="showOptional">{{ __('order.hide_optional') }}</span>
                                </button>

                                <div x-show="showOptional" x-collapse class="space-y-3">
                                    @foreach ($optionalFields as $field)
                                        @include('livewire.partials.order-field', [
                                            'field'  => $field,
                                            'index'  => $index,
                                            'mobile' => true,
                                        ])
                                    @endforeach
                                </div>
                            @endif

                        </div>
                    </div>
                </div>

                {{-- =====================================================
                     DESKTOP ROW (hidden on mobile, shown on lg+)
                     All fields in one flex row, no labels (header row above handles that).
                ===================================================== --}}
                <div class="hidden lg:flex items-center gap-2 px-3 py-2.5">

                    @foreach ($fieldConfig as $field)
                        <div class="{{ $desktopWidths[$field['key']] ?? 'flex-1' }}">
                            @include('livewire.partials.order-field', [
                                'field'  => $field,
                                'index'  => $index,
                                'mobile' => false,
                            ])
                        </div>
                    @endforeach

                    {{-- Remove button --}}
                    @if (count($items) > 1)
                        <button
                            type="button"
                            wire:click="removeItem({{ $index }})"
                            wire:loading.attr="disabled"
                            class="w-6 h-6 shrink-0 flex items-center justify-center rounded
                                   text-gray-300 hover:text-red-400 hover:bg-red-50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @else
                        <div class="w-6 shrink-0"></div>
                    @endif

                </div>

            </div>
        @endforeach

        {{-- Add product button --}}
        <button
            type="button"
            @click="addItemWithCurrency()"
            wire:loading.attr="disabled"
            wire:target="addItem"
            :disabled="{{ $maxProducts }} <= filledCount"
            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl
                   border-2 border-dashed border-gray-200 text-sm font-medium text-gray-500
                   hover:border-primary-400 hover:text-primary-600 hover:bg-primary-50/50
                   transition-all disabled:opacity-40 disabled:cursor-not-allowed"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('order.add_product') }}
        </button>

        <div class="flex justify-end mt-1 px-1">
            <button
                type="button"
                @click="clearDraft(); $wire.set('items', [@js($this->emptyItem($defaultCurrency))]); $wire.set('orderNotes', ''); recalculate()"
                x-show="filledCount > 0"
                class="text-xs text-gray-300 hover:text-gray-400 transition-colors"
            >
                {{ __('order.clear_all') }}
            </button>
        </div>
    </div>

    {{-- ================================================================
         General notes
    ================================================================ --}}
    <div class="max-w-5xl mx-auto px-4 pb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ __('order.general_notes') }}
            </label>
            <textarea
                wire:model.blur="orderNotes"
                rows="3"
                placeholder="{{ __('order.general_notes_placeholder') }}"
                class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm resize-none
                       focus:outline-none focus:ring-2 focus:ring-primary-300 bg-gray-50"
                @input="saveNotesDraft()"
            ></textarea>
        </div>
    </div>

    {{-- ================================================================
         FIXED FOOTER — product count + estimated total + submit
    ================================================================ --}}
    <div class="fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200
                shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-gray-900">
                    <span x-text="filledCount"></span>
                    <span>{{ __('order.products_unit') }}</span>
                </div>
                <div class="text-xs text-gray-500 mt-0.5" x-show="estimatedTotal > 0">
                    {{ __('order.estimated_total') }}:
                    <span class="font-semibold text-gray-700" x-text="formatSAR(estimatedTotal)"></span>
                    <span class="text-gray-400">({{ __('order.approximate') }})</span>
                </div>
                <div class="text-xs text-gray-400 mt-0.5" x-show="estimatedTotal <= 0">
                    {{ __('order.enter_prices_for_estimate') }}
                </div>
            </div>
            <button
                wire:click="submitOrder"
                wire:loading.attr="disabled"
                wire:target="submitOrder"
                class="shrink-0 inline-flex items-center gap-2 bg-primary-600 text-white font-semibold
                       px-6 py-2.5 rounded-xl hover:bg-primary-700 active:scale-95 transition-all
                       disabled:opacity-60 text-sm shadow-sm"
            >
                <span wire:loading.remove wire:target="submitOrder">{{ __('order.submit_order') }}</span>
                <span wire:loading wire:target="submitOrder">{{ __('order.submitting') }}…</span>
            </button>
        </div>
    </div>

    {{-- ================================================================
         GUEST LOGIN MODAL
    ================================================================ --}}
    @if ($showLoginModal)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeModal"></div>
            <div
                class="relative bg-white w-full max-w-sm rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-900">
                        @if ($modalStep === 'email')   {{ __('order.modal_signin_title') }}
                        @elseif ($modalStep === 'login') {{ __('auth.welcome_back') }}
                        @elseif ($modalStep === 'register') {{ __('auth.create_your_account') }}
                        @else {{ __('Reset Password') }}
                        @endif
                    </h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 rounded-lg p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="bg-primary-50 px-5 py-2.5 text-xs text-primary-700 border-b border-primary-100">
                    {{ __('order.modal_info') }}
                </div>
                @if ($modalError)
                    <div class="mx-5 mt-4 px-3 py-2.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        {{ $modalError }}
                    </div>
                @endif
                @if ($modalSuccess)
                    <div class="mx-5 mt-4 px-3 py-2.5 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                        {{ $modalSuccess }}
                    </div>
                @endif
                <div class="px-5 py-4 space-y-3">
                    @if ($modalStep === 'email')
                        <p class="text-sm text-gray-600">{{ __('order.modal_email_prompt') }}</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Email') }}</label>
                            <input type="email" wire:model="modalEmail" wire:keydown.enter="checkModalEmail"
                                   class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
                                   autofocus>
                            @error('modalEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="checkModalEmail" wire:loading.attr="disabled" wire:target="checkModalEmail"
                                class="w-full bg-primary-600 text-white font-semibold py-2.5 rounded-xl hover:bg-primary-700 transition-colors text-sm">
                            {{ __('order.modal_continue') }}
                        </button>
                    @endif
                    @if ($modalStep === 'login')
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Password') }}</label>
                            <div class="relative" x-data="{ show: false }">
                                <input :type="show ? 'text' : 'password'" wire:model="modalPassword"
                                       wire:keydown.enter="loginFromModal"
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm pe-10 focus:outline-none focus:ring-2 focus:ring-primary-300"
                                       autofocus>
                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 end-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            @error('modalPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="loginFromModal" wire:loading.attr="disabled" wire:target="loginFromModal"
                                class="w-full bg-primary-600 text-white font-semibold py-2.5 rounded-xl hover:bg-primary-700 transition-colors text-sm">
                            <span wire:loading.remove wire:target="loginFromModal">{{ __('Log in') }}</span>
                            <span wire:loading wire:target="loginFromModal">{{ __('order.logging_in') }}…</span>
                        </button>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <button wire:click="setModalStep('email')" class="hover:text-primary-600 underline">{{ __('order.change_email') }}</button>
                            <button wire:click="setModalStep('reset')" class="hover:text-primary-600 underline">{{ __('Forgot your password?') }}</button>
                        </div>
                    @endif
                    @if ($modalStep === 'register')
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Name') }}</label>
                                <input type="text" wire:model="modalName"
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
                                       autofocus>
                                @error('modalName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    {{ __('Phone') }} <span class="text-gray-400">({{ __('order.optional') }})</span>
                                </label>
                                <input type="tel" wire:model="modalPhone"
                                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('Password') }}</label>
                                <div class="relative" x-data="{ show: false }">
                                    <input :type="show ? 'text' : 'password'" wire:model="modalPassword"
                                           wire:keydown.enter="registerFromModal"
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm pe-10 focus:outline-none focus:ring-2 focus:ring-primary-300">
                                    <button type="button" @click="show = !show"
                                            class="absolute inset-y-0 end-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('modalPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <button wire:click="registerFromModal" wire:loading.attr="disabled" wire:target="registerFromModal"
                                class="w-full bg-primary-600 text-white font-semibold py-2.5 rounded-xl hover:bg-primary-700 transition-colors text-sm">
                            <span wire:loading.remove wire:target="registerFromModal">{{ __('Register') }}</span>
                            <span wire:loading wire:target="registerFromModal">{{ __('order.creating_account') }}…</span>
                        </button>
                        <button wire:click="setModalStep('email')" class="block text-center text-xs text-gray-500 hover:text-primary-600 underline w-full">
                            {{ __('order.change_email') }}
                        </button>
                    @endif
                    @if ($modalStep === 'reset')
                        <p class="text-sm text-gray-600">{{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}</p>
                        <a href="{{ route('password.request') }}"
                           class="block text-center w-full bg-gray-100 text-gray-700 font-semibold py-2.5 rounded-xl hover:bg-gray-200 transition-colors text-sm">
                            {{ __('Email Password Reset Link') }}
                        </a>
                        <button wire:click="setModalStep('login')" class="block text-center text-xs text-gray-500 hover:text-primary-600 underline w-full">
                            {{ __('order.back_to_login') }}
                        </button>
                    @endif
                </div>
                @if ($modalStep === 'email')
                    <div class="px-5 pb-4 text-center text-xs text-gray-500">
                        {{ __('Already registered?') }}
                        <button wire:click="setModalStep('login')" class="text-primary-600 hover:underline font-medium">
                            {{ __('Log in') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ================================================================
         Alpine.js component logic
    ================================================================ --}}
    <script>
    function newOrderForm(rates, commissionThreshold, commissionPct, commissionFlat, currencies, maxProducts, defaultCurrency) {
        return {
            rates, currencies, maxProducts, commissionThreshold, commissionPct, commissionFlat,
            preferredCurrency: localStorage.getItem('wz_preferred_currency') || defaultCurrency,
            estimatedTotal: 0,
            notification: { visible: false, type: 'success', message: '' },
            showTips: true,
            tipsExpanded: false,
            notifyTimer: null,

            init() {
                this.checkTipsVisibility();
                this.loadDraft();

                if (window.innerWidth >= 1024 && this.$wire.items.length < 5) {
                    const filled  = (this.$wire.items || []).filter(i => (i.url || '').trim() !== '').length;
                    const canAdd  = Math.max(0, maxProducts - filled);
                    const toAdd   = Math.min(5 - this.$wire.items.length, canAdd);
                    for (let i = 0; i < toAdd; i++) {
                        this.$wire.addItem(this.preferredCurrency);
                    }
                    if (filled >= maxProducts) {
                        this.showNotify('error', {{ Js::from(__('order.max_products_reached', ['max' => $maxProducts])) }});
                    }
                }

                this.recalculate();

                this.$wire.$on('order-created', () => this.clearDraft());
            },

            addItemWithCurrency() {
                this.$wire.addItem(this.preferredCurrency);
            },

            recalculate() {
                this.$nextTick(() => {
                    const items = this.$wire.items || [];
                    let rawTotal = 0;
                    items.forEach(item => {
                        if (!item.price || !item.qty) return;
                        // Use global toEnglishDigits() for consistency (handles both Arabic-Indic and Persian digits)
                        const price = parseFloat(window.toEnglishDigits(String(item.price))) || 0;
                        const qty   = parseInt(window.toEnglishDigits(String(item.qty))) || 1;
                        const rate  = rates[item.currency || 'USD'] || 0;
                        if (price > 0 && rate > 0) rawTotal += price * qty * rate;
                    });
                    // Tiered commission: % if rawTotal >= threshold, else flat fee
                    const commission = rawTotal <= 0 ? 0
                        : (rawTotal >= commissionThreshold
                            ? rawTotal * commissionPct
                            : commissionFlat);
                    this.estimatedTotal = Math.round((rawTotal + commission) * 100) / 100;
                });
            },

            get filledCount() {
                return (this.$wire.items || []).filter(i => (i.url || '').trim() !== '').length;
            },

            formatSAR(amount) {
                return amount.toLocaleString('ar-SA', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' ر.س';
            },

            convertArabicNumerals(event) {
                // Use global toEnglishDigits() for consistency (handles both Arabic-Indic and Persian digits)
                // Note: This is redundant since global handler converts automatically, but kept as safety net
                const el = event.target;
                const val = window.toEnglishDigits(el.value);
                if (el.value !== val) el.value = val;
            },

            saveDraft() {
                try {
                    localStorage.setItem('wz_order_draft', JSON.stringify(this.$wire.items || []));
                    localStorage.setItem('wz_preferred_currency', this.preferredCurrency);
                } catch (e) {}
            },

            saveNotesDraft() {
                try { localStorage.setItem('wz_order_notes', this.$wire.orderNotes || ''); } catch (e) {}
            },

            loadDraft() {
                try {
                    const raw = localStorage.getItem('wz_order_draft');
                    if (!raw) return;
                    const draft = JSON.parse(raw);
                    if (!Array.isArray(draft) || !draft.length) return;
                    const hasContent = (this.$wire.items || []).some(i => (i.url || '').trim() !== '');
                    if (hasContent) return;
                    const setAll = draft.map((item, i) => {
                        if (i === 0) {
                            return this.$wire.set('items.0', item);
                        } else {
                            return this.$wire.addItem(item.currency || this.preferredCurrency)
                                .then(() => this.$wire.set(`items.${i}`, item));
                        }
                    });
                    Promise.all(setAll).then(() => this.recalculate());
                    const notes = localStorage.getItem('wz_order_notes');
                    if (notes) this.$wire.set('orderNotes', notes);
                } catch (e) {}
            },

            clearDraft() {
                localStorage.removeItem('wz_order_draft');
                localStorage.removeItem('wz_order_notes');
            },

            checkTipsVisibility() {
                try {
                    const hideUntil = localStorage.getItem('wz_hide_tips_until');
                    if (hideUntil && Date.now() < parseInt(hideUntil)) this.showTips = false;
                } catch (e) {}
            },

            dismissTips() {
                this.showTips = false;
                try {
                    localStorage.setItem('wz_hide_tips_until', (Date.now() + 30 * 24 * 60 * 60 * 1000).toString());
                } catch (e) {}
            },

            showNotify(type, message) {
                clearTimeout(this.notifyTimer);
                this.notification = { visible: true, type, message };
                this.notifyTimer = setTimeout(() => { this.notification.visible = false; }, 4000);
            },
        };
    }
    </script>
</div>
