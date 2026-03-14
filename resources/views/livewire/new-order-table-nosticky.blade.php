{{--
  Layout: Table No-Sticky (Option B)
  Body scrolls both axes. Horizontal scroll via overflow-x-auto on table wrapper.
  No sticky thead (overflow-x-auto parent blocks it — intentional trade-off for body-scroll UX).
  Fixed submit footer via .order-summary-card.
  Test URL: /new-order-table-nosticky
--}}

@php
    $isLoggedIn = auth()->check();
@endphp

<div
    data-guest="{{ auth()->guest() ? 'true' : 'false' }}"
    x-data="newOrderFormTableNoSticky(
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
    @keydown.escape.window="closeZoom()"
    @open-login-modal-attach.window="$wire.openLoginModalForAttach()"
    class="bg-slate-50 text-slate-800 font-[family-name:var(--font-family-arabic)]"
>

{{-- Toast container --}}
<div x-ref="toasts" id="toast-container"></div>

<div class="max-w-6xl mx-auto px-3 py-4 pb-28">

    {{-- Page header --}}
    <div class="flex flex-nowrap items-center justify-between gap-2 mb-4">
        <span class="shrink-0 text-lg font-bold text-slate-800 leading-tight">
            @if ($editingOrderId)
                {{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}
            @else
                {{ __('Create new order') }}
            @endif
        </span>
        <span class="flex-1 min-w-0 text-xs text-slate-400 text-center truncate"
              x-show="filledCount > 0" x-text="productCountText()" x-cloak></span>
        @if ($showAddTestItems ?? false)
        <button type="button"
                @click="addFiveTestItems()"
                class="shrink-0 text-xs text-slate-400 underline bg-transparent border-none cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors">
            {{ __('order.dev_add_5_test_items') }}
        </button>
        @endif
    </div>

    {{-- Edit mode banner --}}
    @if ($editingOrderId)
    <section class="p-3 mb-4 bg-amber-50 border border-amber-200 rounded-xl">
        <p class="text-sm font-semibold text-amber-800 m-0">{{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}</p>
        <p class="text-xs text-amber-700 mt-1 mb-0">{{ __('orders.edit_resubmit_deadline_hint') }}</p>
    </section>
    @endif

    {{-- Tips box --}}
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

    {{-- Table card — overflow-x-auto handles horizontal scroll; body handles vertical --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="relative">
            {{-- Right-fade gradient: visible when table can still scroll right --}}
            <div x-show="tableCanScrollRight"
                 class="absolute inset-y-0 end-0 w-16 bg-gradient-to-s from-white to-transparent pointer-events-none z-10 rounded-e-xl"
                 aria-hidden="true"></div>
            <div class="overflow-x-auto"
                 x-ref="tableScrollContainer"
                 x-init="$nextTick(() => updateScrollUI($el))"
                 @scroll="updateScrollUI($el)"
                 @resize.window.debounce.150="updateScrollUI($refs.tableScrollContainer)">
            <table class="w-full min-w-[1020px] border-collapse text-sm">
                {{-- No sticky thead: overflow-x-auto parent blocks position:sticky in the vertical axis --}}
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200">
                        <th class="w-8 py-2.5 px-2"></th>
                        <th class="w-8 py-2.5 px-2 text-center text-xs font-semibold text-slate-500">
                            {{ __('order_form.th_num_1') }}<br>
                            {{ __('order_form.th_num_2') }}
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 min-w-[220px] md:min-w-[280px]">
                            {{ __('order_form.th_url') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 w-14">
                            {{ __('order_form.th_qty') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 min-w-[90px]">
                            {{ __('order_form.th_color') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 min-w-[90px]">
                            {{ __('order_form.th_size') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 w-24">
                            {{ __('order_form.th_price_per_unit') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 w-20">
                            {{ __('order_form.th_currency') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 min-w-[140px]">
                            {{ __('order_form.th_notes') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                        <th class="py-2.5 px-2 text-start text-xs font-semibold text-slate-500 min-w-[100px]">
                            {{ __('order_form.th_files') }}<br>
                            <span class="font-normal text-slate-400">{{ __('order_form.optional') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in items" :key="item._id || idx">
                        <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors group align-top">

                            {{-- Delete — undo toast in removeItem() handles safety, no confirm() needed --}}
                            <td class="py-2 px-1 text-center">
                                <button type="button"
                                        @click="removeItem(idx)"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded text-slate-300 hover:text-red-500 hover:bg-red-50 focus-visible:text-red-500 focus-visible:outline-none transition-colors"
                                        :aria-label="'{{ __('order_form.remove') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </td>

                            {{-- Row number --}}
                            <td class="py-2 px-2 text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold leading-none mt-1"
                                      x-text="idx + 1"></span>
                            </td>

                            {{-- URL --}}
                            <td class="py-2 px-2">
                                <textarea
                                    x-model="item.url"
                                    @blur="calcTotals(); saveDraft()"
                                    :placeholder="idx === 0 ? '{{ __('order_form.url_placeholder') }}' : ''"
                                    rows="1"
                                    class="order-form-input w-full px-2.5 py-1.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words overflow-hidden min-h-[2rem]"
                                    :title="item.url || ''"
                                    x-init="$nextTick(() => {
                                        if ((item.url || '').trim()) {
                                            $el.style.height = 'auto';
                                            $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        } else {
                                            $el.style.height = '32px';
                                        }
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    })"
                                    @input="
                                        if (item.url.length > 2000) { item.url = item.url.slice(0, 2000); showNotify('error', maxCharsMsg); }
                                        if (!(item.url || '').trim()) {
                                            $el.style.height = '32px';
                                            $el.classList.remove('overflow-y-auto','scrollbar-hide');
                                            $el.classList.add('overflow-hidden');
                                            return;
                                        }
                                        $el.style.height = 'auto';
                                        $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    "
                                ></textarea>
                            </td>

                            {{-- Qty --}}
                            <td class="py-2 px-2">
                                <input
                                    type="text"
                                    x-model="item.qty"
                                    @blur="calcTotals(); saveDraft()"
                                    @input="convertArabicNums($event)"
                                    placeholder="1"
                                    inputmode="numeric"
                                    class="order-form-input w-full px-2.5 py-1.5 border border-primary-100 rounded-lg text-sm bg-white h-8 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 text-center"
                                >
                            </td>

                            {{-- Color --}}
                            <td class="py-2 px-2">
                                <textarea
                                    x-model="item.color"
                                    @blur="saveDraft()"
                                    rows="1"
                                    class="order-form-input w-full px-2.5 py-1.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words overflow-hidden min-h-[2rem]"
                                    :title="item.color || ''"
                                    x-init="$nextTick(() => {
                                        if ((item.color || '').trim()) {
                                            $el.style.height = 'auto';
                                            $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        } else {
                                            $el.style.height = '32px';
                                        }
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    })"
                                    @input="
                                        if (item.color.length > 2000) { item.color = item.color.slice(0, 2000); showNotify('error', maxCharsMsg); }
                                        if (!(item.color || '').trim()) {
                                            $el.style.height = '32px';
                                            $el.classList.remove('overflow-y-auto','scrollbar-hide');
                                            $el.classList.add('overflow-hidden');
                                            return;
                                        }
                                        $el.style.height = 'auto';
                                        $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    "
                                ></textarea>
                            </td>

                            {{-- Size --}}
                            <td class="py-2 px-2">
                                <textarea
                                    x-model="item.size"
                                    @blur="saveDraft()"
                                    rows="1"
                                    class="order-form-input w-full px-2.5 py-1.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words overflow-hidden min-h-[2rem]"
                                    :title="item.size || ''"
                                    x-init="$nextTick(() => {
                                        if ((item.size || '').trim()) {
                                            $el.style.height = 'auto';
                                            $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        } else {
                                            $el.style.height = '32px';
                                        }
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    })"
                                    @input="
                                        if (item.size.length > 2000) { item.size = item.size.slice(0, 2000); showNotify('error', maxCharsMsg); }
                                        if (!(item.size || '').trim()) {
                                            $el.style.height = '32px';
                                            $el.classList.remove('overflow-y-auto','scrollbar-hide');
                                            $el.classList.add('overflow-hidden');
                                            return;
                                        }
                                        $el.style.height = 'auto';
                                        $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    "
                                ></textarea>
                            </td>

                            {{-- Price --}}
                            <td class="py-2 px-2">
                                <input
                                    type="text"
                                    x-model="item.price"
                                    @input="convertArabicNums($event)"
                                    @blur="calcTotals(); saveDraft()"
                                    inputmode="decimal"
                                    placeholder="{{ __('placeholder.amount') }}"
                                    class="order-form-input w-full px-2.5 py-1.5 border border-primary-100 rounded-lg text-sm bg-white h-8 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"
                                >
                            </td>

                            {{-- Currency --}}
                            <td class="py-2 px-2">
                                @include('livewire.partials._currency-dropdown', ['compact' => true])
                            </td>

                            {{-- Notes --}}
                            <td class="py-2 px-2">
                                <textarea
                                    x-model="item.notes"
                                    @blur="saveDraft()"
                                    :placeholder="idx === 0 ? '{{ __('order_form.notes_placeholder') }}' : ''"
                                    rows="1"
                                    class="order-form-input w-full px-2.5 py-1.5 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words overflow-hidden min-h-[2rem]"
                                    :title="item.notes || ''"
                                    x-init="$nextTick(() => {
                                        if ((item.notes || '').trim()) {
                                            $el.style.height = 'auto';
                                            $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        } else {
                                            $el.style.height = '32px';
                                        }
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    })"
                                    @input="
                                        if (item.notes.length > 2000) { item.notes = item.notes.slice(0, 2000); showNotify('error', maxCharsMsg); }
                                        if (!(item.notes || '').trim()) {
                                            $el.style.height = '32px';
                                            $el.classList.remove('overflow-y-auto','scrollbar-hide');
                                            $el.classList.add('overflow-hidden');
                                            return;
                                        }
                                        $el.style.height = 'auto';
                                        $el.style.height = Math.min(Math.max($el.scrollHeight, 32), 100) + 'px';
                                        const o = $el.scrollHeight > $el.offsetHeight;
                                        $el.classList.toggle('overflow-y-auto', o);
                                        $el.classList.toggle('scrollbar-hide', o);
                                        $el.classList.toggle('overflow-hidden', !o);
                                    "
                                ></textarea>
                            </td>

                            {{-- Files --}}
                            <td class="py-2 px-2">
                                <div class="flex items-start gap-1 flex-wrap min-h-[2rem]">
                                    <template x-for="(f, fi) in (item._files || [])" :key="'tbl-ns-file-'+idx+'-'+fi">
                                        <div class="flex items-center gap-0.5">
                                            <div class="relative w-8 h-8 shrink-0 rounded overflow-hidden border border-slate-200 cursor-pointer"
                                                 @click="openFileOrZoom(f)">
                                                <template x-if="f.preview"><img :src="f.preview" class="w-full h-full object-cover block" @click.stop="openFileOrZoom(f)" alt=""></template>
                                                <template x-if="!f.preview && f.fileType === 'img'"><div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[9px] pointer-events-none">...</div></template>
                                                <template x-if="!f.preview && f.fileType === 'pdf'"><div class="w-full h-full flex items-center justify-center bg-red-100 text-red-500 text-[9px] font-bold pointer-events-none">PDF</div></template>
                                                <template x-if="!f.preview && f.fileType === 'xls'"><div class="w-full h-full flex items-center justify-center bg-green-100 text-green-600 text-[9px] font-bold pointer-events-none">XLS</div></template>
                                                <template x-if="!f.preview && f.fileType === 'doc'"><div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 text-[9px] font-bold pointer-events-none">DOC</div></template>
                                                <button type="button"
                                                        class="absolute top-0 end-0 w-3 h-3 bg-red-500 text-white border-none rounded-full text-[8px] font-bold cursor-pointer flex items-center justify-center z-10 leading-none"
                                                        :aria-label="'{{ __('order_form.remove') }}'"
                                                        @click.stop="removeFile(idx, fi)">&times;</button>
                                            </div>
                                            <template x-if="f.uploadProgress !== null && f.uploadProgress !== undefined">
                                                <div class="w-8 h-0.5 bg-slate-100 rounded-sm overflow-hidden">
                                                    <div class="h-full bg-slate-400 rounded-sm transition-[width] duration-200" :style="'width:' + f.uploadProgress + '%'"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="(item._files || []).length < maxImagesPerItem && totalFileCount() < maxImagesPerOrder">
                                        <span class="inline-flex">
                                            <input type="file"
                                                   :id="'tbl-ns-item-file-' + idx"
                                                   class="hidden"
                                                   multiple
                                                   accept="{{ implode(',', $allowedMimeTypes ?? allowed_upload_mime_types()) }}"
                                                   @change="handleFileSelect($event, idx)">
                                            <label :for="'tbl-ns-item-file-' + idx"
                                                   @click="if (!isLoggedIn) { $event.preventDefault(); $wire.openLoginModalForAttach(); }"
                                                   class="border border-dashed border-primary-100 text-slate-400 bg-primary-50 py-1 px-2 rounded text-[11px] font-medium cursor-pointer inline-flex items-center justify-center hover:border-primary-500 hover:text-primary-500 transition-colors whitespace-nowrap">+{{ __('order_form.attach') }}</label>
                                        </span>
                                    </template>
                                </div>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>

        {{-- Custom horizontal scrollbar — visible on all platforms including iOS --}}
        <div x-show="scrollThumbWidth < 99" class="px-3 pt-1 pb-2">
            <div class="relative h-1.5 bg-slate-100 rounded-full cursor-pointer"
                 x-ref="scrollTrack"
                 @click="trackScrollClick($event)">
                <div class="absolute top-0 h-full bg-slate-300 rounded-full cursor-grab active:cursor-grabbing hover:bg-primary-400 transition-colors"
                     :style="`width: ${scrollThumbWidth}%; left: ${scrollThumbLeft}%`"
                     @mousedown.prevent="startScrollDrag($event)"
                     @touchstart.prevent="startScrollDrag($event)"></div>
            </div>
        </div>

        {{-- Add row button — full width under table --}}
        <div class="border-t border-slate-100 px-3 py-2.5">
            <button type="button"
                    @click="addProduct()"
                    class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 rounded-lg text-sm font-semibold text-primary-600 bg-primary-50 border border-dashed border-primary-200 hover:bg-primary-100 hover:border-primary-400 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('order_form.add_product') }}
            </button>
        </div>
    </div>

    {{-- General notes --}}
    <section class="mt-3 bg-white rounded-xl border border-slate-200 p-3">
        <div class="flex items-center justify-between gap-2 mb-1.5">
            <h3 class="text-sm font-semibold text-slate-700 m-0">
                {{ __('order_form.general_notes') }}
                <span class="text-xs font-normal text-slate-400 ms-1">{{ __('order_form.optional') }}</span>
            </h3>
            @if ($showResetAll ?? true)
            <button type="button"
                    @click="resetAll()"
                    class="text-xs text-slate-400 underline bg-transparent border-none cursor-pointer p-0 font-inherit hover:text-red-500 transition-colors whitespace-nowrap">
                {{ __('order_form.reset_all') }}
            </button>
            @endif
        </div>
        <textarea
            x-model="orderNotes"
            @input.debounce.500ms="saveDraft()"
            placeholder="{{ __('order_form.general_notes_ph') }}"
            rows="2"
            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/10 transition-colors"
        ></textarea>
    </section>

</div>

{{-- Fixed submit footer (body-scroll layout — position fixed works correctly here) --}}
<div class="order-summary-card">
    <div class="flex flex-col gap-0.5 flex-1 min-w-0">
        <span class="text-[0.7rem] font-normal text-stone-400 whitespace-nowrap overflow-hidden text-ellipsis"
              x-text="productCountText()"></span>
        <span class="text-stone-400 font-normal text-[0.7rem] whitespace-nowrap"
              x-text="totalText()"></span>
    </div>
    <button type="button"
            @click="submitOrder()"
            :disabled="submitting"
            class="shrink-0 min-w-[120px] max-w-[180px] w-auto inline-flex items-center justify-center py-3 px-4 rounded-md font-semibold text-base bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 hover:from-primary-600 hover:to-primary-500 hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-60 disabled:pointer-events-none">
        @if ($editingOrderId)
        <span x-show="!submitting">{{ __('orders.save_changes') }}</span>
        @else
        <span x-show="!submitting">{{ __('order_form.confirm_order') }}</span>
        @endif
        <span x-show="submitting" x-cloak>{{ __('order_form.submitting') }}...</span>
    </button>
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
@include('livewire.partials._order-login-modal')

</div>

@push('scripts')
{{-- Shared core logic --}}
<script>
@include('livewire.partials._new-order-form-js')
</script>

<script>
function newOrderFormTableNoSticky(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes) {
    return {
        ...newOrderForm(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes),

        // Table-specific state
        showDraftPrompt: false,
        pendingDraftItems: null,
        pendingDraftNotes: '',
        tableCanScrollRight: true,
        scrollThumbWidth: 100,
        scrollThumbLeft: 0,
        _scrDragging: false,
        _scrDragX: 0,
        _scrDragLeft: 0,

        updateScrollUI(el) {
            const ratio = el.clientWidth / el.scrollWidth;
            this.scrollThumbWidth = Math.max(ratio * 100, 8);
            this.scrollThumbLeft = el.scrollWidth > el.clientWidth
                ? (el.scrollLeft / (el.scrollWidth - el.clientWidth)) * (100 - this.scrollThumbWidth)
                : 0;
            this.tableCanScrollRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
        },

        startScrollDrag(e) {
            this._scrDragging = true;
            this._scrDragX = e.clientX ?? e.touches[0].clientX;
            this._scrDragLeft = this.$refs.tableScrollContainer.scrollLeft;
        },

        trackScrollClick(e) {
            if (e.target !== e.currentTarget) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const ratio = (e.clientX - rect.left) / rect.width;
            const container = this.$refs.tableScrollContainer;
            container.scrollLeft = ratio * (container.scrollWidth - container.clientWidth);
        },

        // Show draft prompt instead of silently restoring
        init() {
            this.checkTipsHidden();

            if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
                this.items = initialItems.map((d) => ({
                    _id: Math.random().toString(36).slice(2),
                    url: d.url || '', qty: (d.qty || '1').toString(), color: d.color || '', size: d.size || '',
                    price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: true, _focused: false, _showOptional: false,
                    _files: []
                }));
                this.orderNotes = initialOrderNotes || '';
            } else {
                const draft = this.peekDraft();
                if (draft) {
                    this.pendingDraftItems = draft.items;
                    this.pendingDraftNotes = draft.notes;
                    this.showDraftPrompt = true;
                    this.items = this.defaultRows();
                } else {
                    this.items = this.defaultRows();
                }
            }

            this.calcTotals();

            const onScrMove = (e) => {
                if (!this._scrDragging) return;
                const x = e.clientX ?? e.touches?.[0]?.clientX;
                if (x == null) return;
                const dx = x - this._scrDragX;
                const container = this.$refs.tableScrollContainer;
                const track = this.$refs.scrollTrack;
                const scrollRange = container.scrollWidth - container.clientWidth;
                const trackRange = track.clientWidth * (1 - this.scrollThumbWidth / 100);
                container.scrollLeft = Math.max(0, Math.min(scrollRange, this._scrDragLeft + dx * (scrollRange / trackRange)));
            };
            const onScrUp = () => { this._scrDragging = false; };
            document.addEventListener('mousemove', onScrMove);
            document.addEventListener('mouseup', onScrUp);
            document.addEventListener('touchmove', onScrMove, { passive: true });
            document.addEventListener('touchend', onScrUp);

            window.addEventListener('beforeunload', (e) => {
                if (this.submitting || !this.hasUnsavedData()) return;
                @if (config('app.env') === 'local')
                return;
                @endif
                e.preventDefault();
            });
        },

        // 5 rows on desktop (≥768px), 3 on mobile
        defaultRows() {
            const count = window.innerWidth >= 768 ? 5 : 3;
            const rows = [];
            for (let i = 0; i < count; i++) {
                rows.push(this.emptyItem());
            }
            return rows;
        },

        // Read draft without loading it — only return if it has real content
        peekDraft() {
            try {
                let raw = localStorage.getItem('wz_order_form_draft');
                let notes = localStorage.getItem('wz_order_form_notes');
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

        restoreDraft() {
            if (!this.pendingDraftItems) { this.showDraftPrompt = false; return; }
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
            this.calcTotals();
            this.showNotify('success', @js(__('order_form.draft_restored')));
        },

        discardDraft() {
            this.clearDraft();
            this.pendingDraftItems = null;
            this.pendingDraftNotes = '';
            this.showDraftPrompt = false;
            this.items = this.defaultRows();
            this.orderNotes = '';
            this.calcTotals();
            this.$nextTick(() => this.saveDraft());
        },

        // Scroll window to bottom after adding — body-scroll approach
        addProduct() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', @js(__('order_form.max_products', ['max' => $maxProducts ?? 30])));
                return;
            }
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;
            this.items.push(this.emptyItem(lastCur));
            this.saveDraft();

            this.$nextTick(() => {
                setTimeout(() => {
                    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                }, 100);
            });
        },

        // Undo toast handles safety — no confirm() dialog
        removeItem(idx) {
            if (this.items.length === 0) return;
            const removed = { ...this.items[idx] };
            this.items.splice(idx, 1);
            this.calcTotals();
            this.saveDraft();
            if (this.items.length === 0) {
                this.items = [this.emptyItem()];
                this.calcTotals();
            }
            const c = this.$refs.toasts;
            if (c) {
                const t = document.createElement('div');
                t.className = 'toast success';
                const label = @js(__('order_form.item_removed'));
                const undoLabel = @js(__('order_form.undo'));
                t.innerHTML = `<span style="flex:1">${label}</span><button type="button" class="toast-close" style="font-weight:600;color:var(--color-primary-600,#ea580c)">${undoLabel}</button>`;
                let undone = false;
                const closeToast = () => {
                    t.style.animation = 'toastOut 0.4s ease forwards';
                    setTimeout(() => t.remove(), 400);
                };
                const undo = t.querySelector('.toast-close');
                undo.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (!undone) {
                        undone = true;
                        this.items.splice(idx, 0, { ...removed });
                        this.calcTotals();
                        this.saveDraft();
                    }
                    closeToast();
                });
                c.appendChild(t);
                setTimeout(() => { if (t.parentElement && !undone) closeToast(); }, 4000);
            }
        },

        resetAll() {
            if (!confirm('{{ __('order_form.reset_confirm') }}')) return;
            this.items = this.defaultRows();
            this.orderNotes = '';
            this.clearDraft();
            this.calcTotals();
            this.showNotify('success', '{{ __('order_form.cleared') }}');
        },

        // Fix: pushed items now include _id for stable x-for keying
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
            const isEmpty = (item) => !(item.url || '').trim() && !(item.color || '').trim() && !(item.size || '').trim() && !parseFloat(item.price) && !(item.notes || '').trim();

            for (let i = 0; i < 5; i++) {
                const cur = currencies[i % currencies.length] || lastCur;
                const testData = {
                    _id: Math.random().toString(36).slice(2),
                    url: urls[i], qty: String(Math.floor(Math.random() * 2) + 1),
                    color: colors[i % colors.length], size: sizes[Math.floor(Math.random() * sizes.length)],
                    price: String((Math.random() * 80 + 15).toFixed(2)), currency: cur,
                    notes: notes[i % notes.length],
                    _expanded: true, _focused: false, _showOptional: false, _files: []
                };
                const emptyIdx = this.items.findIndex(isEmpty);
                if (emptyIdx !== -1) {
                    Object.assign(this.items[emptyIdx], testData);
                } else if (this.items.length < this.maxProducts) {
                    this.items.push(testData);
                } else { break; }
            }

            this.calcTotals();
            this.saveDraft();
            this.showNotify('success', '{{ __('order.dev_5_items_added') }}');
        },
    };
}
</script>
@endpush
