{{--
    /caldue1 — New Order scratch rewrite (Caldue1.php)
    Mobile  : stacked expandable cards (lg:hidden)
    Desktop : inline flex row with column headers (hidden lg:flex)
    Both in same Livewire component. All product state lives in Alpine JS / localStorage.
--}}
<div
    x-data="caldue1Form(
        @js($exchangeRates),
        {{ $commissionThreshold }},
        {{ $commissionPct }},
        {{ $commissionFlat }},
        @js($currencies),
        {{ $maxProducts }},
        @js($defaultCurrency)
    )"
    x-init="init()"
    @caldue1-notify.window="showToast($event.detail.type, $event.detail.message)"
    @save-draft-before-modal.window="saveDraft()"
    @clear-draft.window="clearDraft()"
    class="min-h-screen"
    style="background: linear-gradient(135deg,#fef3f2 0%,#fef7f5 100%); font-family: 'IBM Plex Sans Arabic', sans-serif; direction: rtl;"
>

{{-- ============================================================
     Toast Container
============================================================ --}}
<div
    id="c1-toast-container"
    class="fixed top-5 left-1/2 z-[2000] flex flex-col gap-2 pointer-events-none"
    style="transform: translateX(-50%); width:90%; max-width:480px;"
    role="status" aria-live="polite"
></div>

{{-- ============================================================
     Sticky Page Header
============================================================ --}}
<div class="bg-white border-b border-gray-100 sticky top-0 z-30 shadow-sm">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-lg font-bold text-gray-900 leading-tight">طلب جديد</h1>
            <p class="text-xs text-gray-400 mt-0.5">
                <span x-text="filledCount"></span> {{ __('order.products_added') }}
                <span class="mx-1">·</span>
                {{ __('order.max_products_label', ['max' => $maxProducts]) }}
            </p>
        </div>
        <button
            wire:click="submitOrder"
            wire:loading.attr="disabled"
            wire:target="submitOrder"
            @click="prepareSubmit()"
            class="shrink-0 inline-flex items-center gap-2 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-all disabled:opacity-60"
            style="background:linear-gradient(135deg,#f97316,#fb923c); box-shadow:0 4px 12px rgba(249,115,22,.25);"
        >
            <span wire:loading.remove wire:target="submitOrder">{{ __('order.submit_order') }}</span>
            <span wire:loading wire:target="submitOrder">{{ __('order.submitting') }}…</span>
        </button>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 pb-44 pt-4">

{{-- ============================================================
     Tips Box — collapsible, collapsed by default, 30-day localStorage
============================================================ --}}
<div x-show="showTips" x-cloak class="mb-4">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden" style="border-right:4px solid #f97316;">
        <button
            type="button"
            class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-800 border-b border-gray-100 hover:bg-gray-50 transition-colors"
            @click="tipsOpen = !tipsOpen"
        >
            <span>{{ __('order.tips_title') }}</span>
            <span x-text="tipsOpen ? '▲' : '▼'" class="text-gray-400 text-xs"></span>
        </button>
        <div x-show="tipsOpen" x-collapse class="px-4 py-3 text-sm text-gray-600 leading-relaxed">
            <ul class="list-none space-y-2">
                @foreach(range(1,7) as $n)
                <li class="relative pr-4 before:content-['•'] before:absolute before:right-0 before:text-orange-500 before:font-bold">
                    {{ __("order.tips_tip{$n}") }}
                </li>
                @endforeach
            </ul>
            <div class="mt-4 pt-3 border-t border-gray-100">
                <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        class="w-4 h-4 accent-orange-500 cursor-pointer"
                        @change="dontShowTips($event)"
                    >
                    <span>{{ __('order.tips_dont_show') }}</span>
                </label>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     MOBILE VIEW — stacked expandable cards (lg:hidden)
============================================================ --}}
<div class="lg:hidden">
    <div id="c1-mobile-list" class="flex flex-col gap-2.5">
        <template x-for="(item, idx) in items" :key="item._id">
            <div
                class="bg-white rounded-xl overflow-hidden transition-all"
                :class="item.expanded
                    ? 'shadow-md border-2 border-orange-400/60'
                    : 'shadow-sm border border-gray-100'"
                :style="!item.expanded && hasContent(item) ? 'background:#fef7f5;' : ''"
            >
                {{-- Card header summary --}}
                <div
                    class="flex items-center gap-2 px-3 py-3 cursor-pointer select-none"
                    :class="item.expanded ? 'border-b border-gray-100 bg-white' : 'bg-inherit'"
                    @click="toggleCard(idx)"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-xs font-bold shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-white"
                                :class="hasContent(item) ? 'bg-green-500' : 'bg-orange-400'"
                                x-text="idx + 1"
                            ></span>
                            <span
                                class="text-sm font-semibold truncate"
                                :class="hasContent(item) ? 'text-green-700' : 'text-gray-700'"
                                x-text="summaryText(item, idx)"
                            ></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button
                            type="button"
                            class="text-xs font-medium px-2.5 py-1 rounded-lg border transition-all"
                            :class="item.expanded
                                ? 'bg-gray-50 border-gray-200 text-gray-600'
                                : 'bg-orange-50 border-orange-200 text-orange-700'"
                            x-text="item.expanded ? 'طي' : 'تعديل'"
                            @click.stop="toggleCard(idx)"
                        ></button>
                        <button
                            type="button"
                            class="text-xs font-medium px-2.5 py-1 rounded-lg border bg-red-50 border-red-200 text-red-600 transition-all hover:bg-red-100"
                            @click.stop="removeItem(idx)"
                        >حذف</button>
                    </div>
                </div>

                {{-- Card body (expanded) --}}
                <div x-show="item.expanded" x-collapse class="px-3 pb-3 pt-2 space-y-2.5">
                    {{-- URL --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_url') }}</label>
                        <input
                            type="text"
                            x-model="item.url"
                            @change="saveDraft()"
                            :placeholder="idx === 0 ? '{{ __('order.url_placeholder') }}' : '{{ __('order.url_placeholder_short') }}'"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all"
                        >
                    </div>

                    {{-- Row: Qty, Color, Size --}}
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_qty') }}</label>
                            <input
                                type="tel"
                                x-model="item.qty"
                                @input="item.qty = toEnNum($event.target.value); saveDraft()"
                                placeholder="1"
                                class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm text-center focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all"
                                style="direction:ltr;"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_color') }}</label>
                            <input
                                type="text"
                                x-model="item.color"
                                @change="saveDraft()"
                                class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_size') }}</label>
                            <input
                                type="text"
                                x-model="item.size"
                                @change="saveDraft()"
                                class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all"
                            >
                        </div>
                    </div>

                    {{-- Row: Price + Currency --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_price') }}</label>
                            <input
                                type="text"
                                x-model="item.price"
                                @input="item.price = toEnNum($event.target.value); saveDraft()"
                                placeholder="0.00"
                                inputmode="decimal"
                                class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all"
                                style="direction:ltr;"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_currency') }}</label>
                            <select
                                x-model="item.currency"
                                @change="onCurrencyChange(item, $event.target.value); saveDraft()"
                                class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all bg-white"
                            >
                                <template x-for="(meta, code) in currencies" :key="code">
                                    <option :value="code" x-text="code === 'OTHER' ? meta.label : code + ' – ' + meta.label.split('–')[1]?.trim() || meta.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    {{-- Optional toggle: Notes + File --}}
                    <button
                        type="button"
                        class="text-xs font-semibold text-orange-600 underline underline-offset-2"
                        @click="item.showOptional = !item.showOptional"
                        x-text="item.showOptional ? '{{ __('order.hide_optional') }}' : '+ {{ __('order.field_notes') }} / {{ __('order.field_file') }}'"
                    ></button>

                    <div x-show="item.showOptional" x-collapse class="space-y-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_notes') }} <span class="text-gray-400">({{ __('order.optional') }})</span></label>
                            <input
                                type="text"
                                x-model="item.notes"
                                @change="saveDraft()"
                                :placeholder="idx === 0 ? '{{ __('order.notes_placeholder') }}' : ''"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all"
                            >
                        </div>

                        {{-- File upload --}}
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">{{ __('order.field_file') }} <span class="text-gray-400">({{ __('order.optional') }})</span></label>
                            <div class="flex items-center gap-2 flex-wrap">
                                {{-- Upload trigger --}}
                                <template x-if="!item.file">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 border border-dashed border-gray-300 text-gray-500 bg-gray-50 text-xs font-medium px-3 py-2 rounded-lg hover:border-orange-400 hover:text-orange-600 transition-all"
                                        @click="triggerFileUpload(idx)"
                                    >
                                        📎 {{ __('order.upload_file') }}
                                    </button>
                                </template>

                                {{-- File preview --}}
                                <template x-if="item.file">
                                    <div class="relative w-11 h-11 rounded-lg overflow-hidden border border-gray-200 group">
                                        <template x-if="item.file.type === 'image'">
                                            <img :src="item.file.preview" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="item.file.type === 'pdf'">
                                            <div class="w-full h-full flex items-center justify-center bg-red-100 text-red-600 text-[10px] font-bold">PDF</div>
                                        </template>
                                        <template x-if="item.file.type === 'xls'">
                                            <div class="w-full h-full flex items-center justify-center bg-green-100 text-green-700 text-[10px] font-bold">XLS</div>
                                        </template>
                                        <button
                                            type="button"
                                            class="absolute top-0 left-0 w-4 h-4 bg-red-500/90 text-white text-[10px] rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                            @click="removeFile(idx)"
                                        >×</button>
                                    </div>
                                </template>

                                <span x-show="item.file" class="text-xs text-gray-400 truncate max-w-[120px]" x-text="item.file?.name"></span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">{{ __('order.file_limit') }} · 2MB</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Hidden file inputs (one per item, rendered to satisfy browser) --}}
    <template x-for="(item, idx) in items" :key="'fi_' + item._id">
        <input
            type="file"
            :id="'c1-file-' + idx"
            class="hidden"
            accept="image/*,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            @change="onFileChosen(idx, $event)"
        >
    </template>
</div>

{{-- ============================================================
     DESKTOP VIEW — inline flex rows with column headers (hidden lg:flex)
============================================================ --}}
<div class="hidden lg:block">
    {{-- Column Headers --}}
    <div
        class="flex items-center gap-2 px-3 py-2 rounded-t-xl text-xs font-bold text-gray-600 mb-0"
        style="background:#f1f5f9; min-width:900px;"
    >
        <div class="w-7 text-center shrink-0 text-gray-400">#</div>
        <div class="flex-[3] min-w-0">{{ __('order.field_url') }}</div>
        <div class="w-14 text-center shrink-0">{{ __('order.field_qty') }}</div>
        <div class="flex-1 min-w-[70px]">{{ __('order.field_color') }}</div>
        <div class="flex-1 min-w-[70px]">{{ __('order.field_size') }}</div>
        <div class="flex-1 min-w-[80px]">{{ __('order.field_price') }}</div>
        <div class="w-28 shrink-0">{{ __('order.field_currency') }}</div>
        <div class="flex-1 min-w-[80px]">{{ __('order.field_notes') }}</div>
        <div class="w-16 text-center shrink-0">{{ __('order.field_file') }}</div>
        <div class="w-8 shrink-0"></div>
    </div>

    {{-- Desktop rows --}}
    <div
        class="border border-gray-200 rounded-b-xl overflow-hidden"
        style="min-width:900px;"
    >
        <template x-for="(item, idx) in items" :key="item._id">
            <div
                class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 last:border-b-0 hover:bg-orange-50/40 transition-colors relative"
                :class="hasContent(item) ? 'border-l-4 border-l-green-400' : ''"
            >
                {{-- # --}}
                <div class="w-7 text-center shrink-0 text-sm font-semibold text-gray-500" x-text="idx + 1"></div>

                {{-- URL --}}
                <div class="flex-[3] min-w-0">
                    <input
                        type="text"
                        x-model="item.url"
                        @change="saveDraft()"
                        :placeholder="idx === 0 ? '{{ __('order.url_placeholder_short') }}' : ''"
                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all"
                    >
                </div>

                {{-- Qty --}}
                <div class="w-14 shrink-0">
                    <input
                        type="tel"
                        x-model="item.qty"
                        @input="item.qty = toEnNum($event.target.value); saveDraft()"
                        placeholder="1"
                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:border-orange-400 transition-all"
                        style="direction:ltr;"
                    >
                </div>

                {{-- Color --}}
                <div class="flex-1 min-w-[70px]">
                    <input
                        type="text"
                        x-model="item.color"
                        @change="saveDraft()"
                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-orange-400 transition-all"
                    >
                </div>

                {{-- Size --}}
                <div class="flex-1 min-w-[70px]">
                    <input
                        type="text"
                        x-model="item.size"
                        @change="saveDraft()"
                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-orange-400 transition-all"
                    >
                </div>

                {{-- Price --}}
                <div class="flex-1 min-w-[80px]">
                    <input
                        type="text"
                        x-model="item.price"
                        @input="item.price = toEnNum($event.target.value); saveDraft()"
                        placeholder="0.00"
                        inputmode="decimal"
                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-orange-400 transition-all"
                        style="direction:ltr;"
                    >
                </div>

                {{-- Currency --}}
                <div class="w-28 shrink-0">
                    <select
                        x-model="item.currency"
                        @change="onCurrencyChange(item, $event.target.value); saveDraft()"
                        class="w-full border border-gray-200 rounded-lg px-1.5 py-1.5 text-sm focus:outline-none focus:border-orange-400 transition-all bg-white"
                        style="font-size:0.78rem;"
                    >
                        <template x-for="(meta, code) in currencies" :key="code">
                            <option :value="code" x-text="code"></option>
                        </template>
                    </select>
                </div>

                {{-- Notes --}}
                <div class="flex-1 min-w-[80px]">
                    <input
                        type="text"
                        x-model="item.notes"
                        @change="saveDraft()"
                        class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:border-orange-400 transition-all"
                    >
                </div>

                {{-- File --}}
                <div class="w-16 shrink-0 flex flex-col items-center gap-1">
                    <template x-if="!item.file">
                        <button
                            type="button"
                            class="w-8 h-8 flex items-center justify-center border border-dashed border-gray-300 rounded-lg text-gray-400 hover:border-orange-400 hover:text-orange-500 transition-all text-base"
                            @click="triggerFileUpload(idx)"
                            title="{{ __('order.upload_file') }}"
                        >📎</button>
                    </template>
                    <template x-if="item.file">
                        <div class="relative w-8 h-8 rounded-lg overflow-hidden border border-gray-200 group cursor-pointer" @click="removeFile(idx)">
                            <template x-if="item.file.type === 'image'">
                                <img :src="item.file.preview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="item.file.type !== 'image'">
                                <div
                                    class="w-full h-full flex items-center justify-center text-[9px] font-bold"
                                    :class="item.file.type === 'pdf' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700'"
                                    x-text="item.file.type.toUpperCase()"
                                ></div>
                            </template>
                            <div class="absolute inset-0 bg-red-500/70 text-white text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">×</div>
                        </div>
                    </template>
                    {{-- hidden file input --}}
                    <input
                        type="file"
                        :id="'c1-file-' + idx"
                        class="hidden"
                        accept="image/*,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        @change="onFileChosen(idx, $event)"
                    >
                </div>

                {{-- Remove --}}
                <div class="w-8 shrink-0">
                    <button
                        type="button"
                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all text-lg"
                        @click="removeItem(idx)"
                        title="{{ __('order.remove_item') }}"
                    >×</button>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- ============================================================
     Add Product Button (both views)
============================================================ --}}
<button
    type="button"
    @click="addItem()"
    class="w-full mt-3 flex items-center justify-center gap-2 border-2 border-dashed border-orange-200 text-orange-600 font-semibold text-sm rounded-xl py-3 hover:bg-orange-50 hover:border-orange-400 transition-all"
>
    {{ __('order.add_product') }}
</button>

{{-- Clear all link --}}
<div class="text-left mt-2" x-show="filledCount > 0">
    <button
        type="button"
        class="text-xs text-gray-400 underline hover:text-red-500 transition-colors"
        @click="clearAll()"
    >{{ __('order.clear_all') }}</button>
</div>

{{-- ============================================================
     General Notes
============================================================ --}}
<div class="mt-4 bg-white rounded-xl p-4 shadow-sm border border-gray-100">
    <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ __('order.general_notes') }}</h3>
    <textarea
        wire:model="orderNotes"
        rows="3"
        placeholder="{{ __('order.general_notes_placeholder') }}"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 transition-all resize-y"
    ></textarea>
</div>

</div>{{-- end max-w-5xl --}}

{{-- ============================================================
     Sticky Footer
============================================================ --}}
<div
    class="fixed bottom-0 left-0 right-0 z-[100]"
    style="background:rgba(255,255,255,.97); backdrop-filter:blur(20px); border-top:1px solid rgba(226,232,240,.6); box-shadow:0 -4px 16px rgba(0,0,0,.06);"
>
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3"
         style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom,0px));">
        <div class="flex-1 min-w-0">
            <div class="text-xs text-gray-500">
                <span x-text="filledCount"></span> {{ __('order.products_unit') }}
            </div>
            {{-- Estimated total — only shows when at least one item has price + qty --}}
            <template x-if="estimatedTotal > 0">
                <div class="text-sm font-bold text-gray-800">
                    {{ __('order.estimated_total') }}:
                    <span x-text="Math.floor(estimatedTotal).toLocaleString('en-US')"></span> ر.س
                    <span class="text-xs font-normal text-gray-400">({{ __('order.approximate') }})</span>
                </div>
            </template>
            <template x-if="estimatedTotal === 0">
                <div class="text-xs text-gray-400">{{ __('order.enter_prices_for_estimate') }}</div>
            </template>
        </div>
        <button
            wire:click="submitOrder"
            wire:loading.attr="disabled"
            wire:target="submitOrder"
            @click="prepareSubmit()"
            class="shrink-0 inline-flex items-center gap-2 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all disabled:opacity-60"
            style="background:linear-gradient(135deg,#f97316,#fb923c); box-shadow:0 4px 12px rgba(249,115,22,.25); min-width:120px;"
        >
            <span wire:loading.remove wire:target="submitOrder">{{ __('order.submit_order') }}</span>
            <span wire:loading wire:target="submitOrder">{{ __('order.submitting') }}…</span>
        </button>
    </div>
</div>

{{-- ============================================================
     Guest Auth Modal
============================================================ --}}
@if($showLoginModal)
<div
    class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center p-0 sm:p-4"
    style="background:rgba(0,0,0,.65);"
    wire:click.self="closeModal"
>
    <div
        class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-y-auto"
        style="max-height:90vh; animation: slideUpModal .3s ease;"
    >
        {{-- Modal Header --}}
        <div class="px-6 py-5 border-b border-gray-100 text-center relative">
            <button
                wire:click="closeModal"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl leading-none"
            >&times;</button>
            <h2 class="text-lg font-bold text-gray-900">
                @if($modalStep === 'email')   {{ __('order.modal_signin_title') }}
                @elseif($modalStep === 'login')    {{ __('Log in') }}
                @elseif($modalStep === 'register') {{ __('Register') }}
                @else                              {{ __('Reset Password') }}
                @endif
            </h2>
            <p class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2 mt-2 font-medium">
                ✅ {{ __('order.modal_info') }}
            </p>
        </div>

        <div class="px-6 py-5">

            {{-- Error / Success alerts --}}
            @if($modalError)
            <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl font-medium">
                ❌ {{ $modalError }}
            </div>
            @endif

            @if($modalSuccess)
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl font-medium">
                ✅ {{ $modalSuccess }}
            </div>
            @endif

            {{-- Step: Email --}}
            @if($modalStep === 'email')
            <form wire:submit.prevent="checkModalEmail" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Email') }}</label>
                    <input
                        type="email"
                        wire:model="modalEmail"
                        required autocomplete="email"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                    >
                    @error('modalEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <button
                    type="submit"
                    class="w-full text-white font-semibold py-3 rounded-xl transition-all"
                    style="background:linear-gradient(135deg,#f97316,#fb923c);"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ __('order.modal_continue') }}</span>
                    <span wire:loading>…</span>
                </button>
            </form>
            @endif

            {{-- Step: Login --}}
            @if($modalStep === 'login')
            <form wire:submit.prevent="loginFromModal" class="space-y-4">
                <div class="text-sm text-gray-600 mb-2">
                    {{ __('Already registered?') }} —
                    <strong>{{ $modalEmail }}</strong>
                    <button type="button" wire:click="setModalStep('email')" class="text-orange-500 underline text-xs mr-1">{{ __('order.change_email') }}</button>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Password') }}</label>
                    <input
                        type="password"
                        wire:model="modalPassword"
                        required autocomplete="current-password"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                    >
                    @error('modalPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <button
                    type="submit"
                    class="w-full text-white font-semibold py-3 rounded-xl transition-all"
                    style="background:linear-gradient(135deg,#f97316,#fb923c);"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ __('Log in') }}</span>
                    <span wire:loading>{{ __('order.logging_in') }}…</span>
                </button>
                <div class="text-center">
                    <button type="button" wire:click="setModalStep('reset')" class="text-sm text-orange-500 underline">{{ __('Forgot your password?') }}</button>
                </div>
            </form>
            @endif

            {{-- Step: Register --}}
            @if($modalStep === 'register')
            <form wire:submit.prevent="registerFromModal" class="space-y-4">
                <div class="text-sm text-gray-600 mb-1">
                    {{ __("Don't have an account?") }} —
                    <strong>{{ $modalEmail }}</strong>
                    <button type="button" wire:click="setModalStep('email')" class="text-orange-500 underline text-xs mr-1">{{ __('order.change_email') }}</button>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Name') }}</label>
                    <input
                        type="text"
                        wire:model="modalName"
                        required
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                    >
                    @error('modalName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        {{ __('Phone') }} <span class="text-gray-400 font-normal text-xs">({{ __('order.optional') }})</span>
                    </label>
                    <input
                        type="tel"
                        wire:model="modalPhone"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                        style="direction:ltr;"
                    >
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Password') }}</label>
                    <input
                        type="password"
                        wire:model="modalPassword"
                        required minlength="4"
                        autocomplete="new-password"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                    >
                    @error('modalPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <button
                    type="submit"
                    class="w-full text-white font-semibold py-3 rounded-xl transition-all"
                    style="background:linear-gradient(135deg,#f97316,#fb923c);"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ __('Register') }}</span>
                    <span wire:loading>{{ __('order.creating_account') }}…</span>
                </button>
                <div class="text-center">
                    <button type="button" wire:click="setModalStep('login')" class="text-sm text-orange-500 underline">{{ __('order.back_to_login') }}</button>
                </div>
            </form>
            @endif

            {{-- Step: Reset --}}
            @if($modalStep === 'reset')
            <form wire:submit.prevent="sendResetLink" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Email') }}</label>
                    <input
                        type="email"
                        wire:model="modalEmail"
                        required autocomplete="email"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                    >
                    @error('modalEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <button
                    type="submit"
                    class="w-full text-white font-semibold py-3 rounded-xl transition-all"
                    style="background:linear-gradient(135deg,#f97316,#fb923c);"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>{{ __('Email Password Reset Link') }}</span>
                    <span wire:loading>…</span>
                </button>
                <div class="text-center">
                    <button type="button" wire:click="setModalStep('login')" class="text-sm text-orange-500 underline">{{ __('order.back_to_login') }}</button>
                </div>
            </form>
            @endif

        </div>
    </div>
</div>
@endif

{{-- ============================================================
     Alpine JS
============================================================ --}}
<style>
@keyframes slideUpModal {
    from { opacity: 0; transform: translateY(40px); }
    to   { opacity: 1; transform: translateY(0); }
}
.c1-toast {
    background: rgba(255,255,255,.98);
    backdrop-filter: blur(10px);
    padding: 12px 15px;
    border-radius: 12px;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,.10), 0 4px 6px -2px rgba(0,0,0,.05);
    font-weight: 600;
    font-size: .875rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    animation: c1ToastIn .4s cubic-bezier(.18,.89,.32,1.28) forwards;
    opacity: 0;
    pointer-events: auto;
    cursor: pointer;
}
.c1-toast.success { border-right: 5px solid #10b981; }
.c1-toast.error   { border-right: 5px solid #ef4444; }
@keyframes c1ToastIn {
    from { opacity:0; transform: translateY(-14px) scale(.9); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}
@keyframes c1ToastOut {
    from { opacity:1; transform: translateY(0) scale(1); }
    to   { opacity:0; transform: translateY(-14px) scale(.8); }
}
[x-cloak] { display: none !important; }
</style>

<script>
function caldue1Form(rates, commThreshold, commPct, commFlat, currencies, maxProducts, defaultCurrency) {
    return {
        // ---- State ----
        rates,
        commThreshold,
        commPct,
        commFlat,
        currencies,
        maxProducts,
        defaultCurrency,

        items: [],
        preferredCurrency: defaultCurrency,

        showTips: true,
        tipsOpen: false,

        _uid: 0,

        // ---- Computed ----
        get filledCount() {
            return this.items.filter(i => (i.url || '').trim() !== '' || (i.price || '').trim() !== '').length;
        },

        get estimatedTotal() {
            let subtotal = 0;
            for (const item of this.items) {
                const qty   = Math.max(1, parseFloat(this.toEnNum(item.qty)) || 1);
                const price = parseFloat(this.toEnNum(item.price)) || 0;
                const rate  = this.rates[item.currency] || 0;
                if (price > 0 && rate > 0) {
                    subtotal += qty * price * rate;
                }
            }
            if (subtotal === 0) return 0;
            const commission = subtotal >= this.commThreshold
                ? subtotal * this.commPct
                : this.commFlat;
            return subtotal + commission;
        },

        // ---- Lifecycle ----
        init() {
            this.checkTipsVisibility();
            const isDesktop = window.innerWidth >= 1024;

            // Restore draft or init with default rows
            if (!this.loadDraft()) {
                const count = isDesktop ? 5 : 1;
                for (let i = 0; i < count; i++) this.addItem(false);
                if (this.items.length > 0) this.items[0].expanded = true;

                // Pre-fill first item if ?product_url= present
                const params  = new URLSearchParams(window.location.search);
                const pUrl    = params.get('product_url');
                if (pUrl && this.items[0]) {
                    this.items[0].url = pUrl.substring(0, 2000);
                }
            }

            // Guest file upload block listener
            document.addEventListener('click', (e) => {
                const isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
                if (!isLoggedIn && e.target.closest('[data-upload-trigger]')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.showToast('error', 'لإرفاق الملفات يرجى تسجيل الدخول');
                }
            }, true);

            // Watch items for auto-save on any change
            this.$watch('items', () => this.saveDraft(), { deep: true });
        },

        // ---- Item Management ----
        newItem(currency = null) {
            return {
                _id:         ++this._uid,
                _idx:        0,           // set after push
                url:         '',
                qty:         '1',
                color:       '',
                size:        '',
                price:       '',
                currency:    currency || this.preferredCurrency,
                notes:       '',
                file:        null,        // { name, type, preview }
                expanded:    false,
                showOptional: false,
            };
        },

        addItem(focus = true) {
            if (this.items.length >= this.maxProducts) {
                this.showToast('error', '{{ __('order.max_products_reached', ['max' => '__MAX__']) }}'.replace('__MAX__', this.maxProducts));
                return null;
            }
            const item = this.newItem();
            this.items.push(item);
            this._reindex();
            return item;
        },

        removeItem(idx) {
            this.items.splice(idx, 1);
            this._reindex();
            this.saveDraft();
        },

        toggleCard(idx) {
            const isDesktop = window.innerWidth >= 1024;
            if (isDesktop) return;

            const wasExpanded = this.items[idx].expanded;

            if (!wasExpanded) {
                // Collapse the currently open card
                const openIdx = this.items.findIndex((it, i) => i !== idx && it.expanded);
                if (openIdx > -1) {
                    this.items[openIdx].expanded = false;
                    this.showToast('success', 'تم حفظ المنتج رقم ' + (openIdx + 1));
                }
            }
            this.items[idx].expanded = !wasExpanded;
        },

        _reindex() {
            this.items.forEach((it, i) => { it._idx = i; });
        },

        // ---- Currency ----
        onCurrencyChange(item, code) {
            if (code !== 'OTHER') this.preferredCurrency = code;
            if (code === 'OTHER') {
                this.showToast('success', 'بما أن هذه العملة غير متاحة حاليًا، سنحسب التكلفة يدويًا ونرد عليك قريبًا.', 5000);
            }
        },

        // ---- File Upload ----
        triggerFileUpload(idx) {
            const isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
            if (!isLoggedIn) {
                this.showToast('error', 'لإرفاق الملفات يرجى تسجيل الدخول');
                return;
            }
            const el = document.getElementById('c1-file-' + idx);
            if (el) el.click();
        },

        onFileChosen(idx, event) {
            const file = event.target.files[0];
            if (!file) return;

            // 1-file-per-item
            if (this.items[idx].file) {
                this.showToast('error', 'يمكنك إرفاق ملف واحد فقط لكل منتج.');
                event.target.value = '';
                return;
            }

            // 10-files-per-order
            const totalFiles = this.items.filter(i => i.file).length;
            if (totalFiles >= 10) {
                this.showToast('error', '{{ __('order.max_files_exceeded') }}');
                event.target.value = '';
                return;
            }

            // Max 2MB
            if (file.size > 2 * 1024 * 1024) {
                this.showToast('error', 'حجم الملف كبير جداً. الحد الأقصى ٢ ميجابايت');
                event.target.value = '';
                return;
            }

            // Allowed types
            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp',
                             'application/pdf', 'application/vnd.ms-excel',
                             'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!allowed.includes(file.type)) {
                this.showToast('error', 'يرجى اختيار ملف مدعوم (صور، PDF، Excel)');
                event.target.value = '';
                return;
            }

            const fileType = file.type.startsWith('image/') ? 'image'
                           : file.type === 'application/pdf' ? 'pdf'
                           : 'xls';

            const reader = new FileReader();
            reader.onload = (e) => {
                this.items[idx].file = {
                    name:    file.name,
                    type:    fileType,
                    preview: e.target.result,
                    size:    file.size,
                };
                this.showToast('success', 'تم إرفاق الملف. بعد الإرسال يمكنك إضافة المزيد من صفحة الطلب.', 4000);
            };
            reader.readAsDataURL(file);
        },

        removeFile(idx) {
            this.items[idx].file = null;
            const el = document.getElementById('c1-file-' + idx);
            if (el) el.value = '';
        },

        // ---- Draft Persistence ----
        saveDraft() {
            const data = this.items.map(it => ({
                url:      it.url,
                qty:      it.qty,
                color:    it.color,
                size:     it.size,
                price:    it.price,
                currency: it.currency,
                notes:    it.notes,
            }));
            try {
                localStorage.setItem('wz_caldue1_draft', JSON.stringify(data));
            } catch (_) {}
        },

        loadDraft() {
            try {
                const raw = localStorage.getItem('wz_caldue1_draft');
                if (!raw) return false;
                const data = JSON.parse(raw);
                if (!Array.isArray(data) || data.length === 0) return false;

                this.items = [];
                data.forEach((d, i) => {
                    const item = this.newItem(d.currency || this.preferredCurrency);
                    Object.assign(item, {
                        url:      d.url    || '',
                        qty:      d.qty    || '1',
                        color:    d.color  || '',
                        size:     d.size   || '',
                        price:    d.price  || '',
                        currency: d.currency || this.preferredCurrency,
                        notes:    d.notes  || '',
                        expanded: i === 0,
                    });
                    this.items.push(item);
                });
                this._reindex();
                return true;
            } catch (_) { return false; }
        },

        clearDraft() {
            try { localStorage.removeItem('wz_caldue1_draft'); } catch (_) {}
        },

        clearAll() {
            if (!confirm('سيتم مسح كامل البيانات. هل تريد المتابعة؟')) return;
            this.items = [];
            this.clearDraft();
            const isDesktop = window.innerWidth >= 1024;
            const count = isDesktop ? 5 : 1;
            for (let i = 0; i < count; i++) this.addItem(false);
            if (this.items.length > 0) this.items[0].expanded = true;
            this.showToast('success', 'تم مسح جميع الحقول');
        },

        // ---- Tips ----
        checkTipsVisibility() {
            try {
                const hideUntil = localStorage.getItem('wz_hide_tips_until');
                if (hideUntil && Date.now() < parseInt(hideUntil)) {
                    this.showTips = false;
                } else if (hideUntil) {
                    localStorage.removeItem('wz_hide_tips_until');
                }
            } catch (_) {}
        },

        dontShowTips(event) {
            if (event.target.checked) {
                try {
                    localStorage.setItem('wz_hide_tips_until', (Date.now() + 30 * 86400000).toString());
                } catch (_) {}
                this.showTips = false;
                this.showToast('success', 'لن يتم إظهار النصائح لمدة 30 يوم', 2000);
            }
        },

        // ---- Submit prep — serialise products to wire model ----
        prepareSubmit() {
            const products = this.items.map((it, i) => ({
                _idx:     it._idx,
                url:      it.url   || '',
                qty:      it.qty   || '1',
                color:    it.color || '',
                size:     it.size  || '',
                price:    it.price || '',
                currency: it.currency || this.preferredCurrency,
                notes:    it.notes || '',
            }));
            // Inject into wire model via the hidden input (Livewire 3 uses wire:model)
            @this.set('productsJson', JSON.stringify(products));
        },

        // ---- Helpers ----
        toEnNum(str) {
            if (!str) return '';
            return String(str).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
        },

        hasContent(item) {
            return (item.url || '').trim() !== '' || (item.price || '').trim() !== '';
        },

        summaryText(item, idx) {
            const num = idx + 1;
            const url = (item.url || '').trim();
            if (!url) return 'منتج رقم ' + num;
            try {
                const host = new URL(url.startsWith('http') ? url : 'https://' + url).hostname.replace('www.','');
                return 'المنتج ' + num + ': ' + host;
            } catch (_) {
                return 'المنتج ' + num + ': ' + url.split('/')[0].replace('www.','');
            }
        },

        // ---- Toast ----
        showToast(type = 'success', msg = '', duration = null) {
            const container = document.getElementById('c1-toast-container');
            if (!container) return;
            const dur = duration !== null ? duration : (type === 'success' ? 1800 : 4000);
            const icon = type === 'success' ? '✅' : '❌';

            const el = document.createElement('div');
            el.className = 'c1-toast ' + type;
            el.innerHTML = `<div style="display:flex;align-items:center;gap:8px;flex:1;"><span>${icon}</span><span>${msg}</span></div><button type="button" style="background:none;border:none;color:#94a3b8;font-size:1.1rem;cursor:pointer;padding:4px;line-height:1;" onclick="this.closest('.c1-toast')._remove()">×</button>`;

            const remove = () => {
                if (!el.parentElement) return;
                el.style.animation = 'c1ToastOut .35s ease forwards';
                setTimeout(() => el.remove(), 350);
            };
            el._remove = remove;
            el.addEventListener('click', remove);

            container.appendChild(el);
            const timer = setTimeout(remove, dur);
        },
    };
}
</script>

</div>{{-- end x-data --}}
