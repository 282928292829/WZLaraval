{{--
  Shared item fields partial — @include inside x-for="(item, idx) in items".
  Assumes Alpine vars: item, idx, calcTotals(), saveDraft(), showNotify(), convertArabicNums(),
                       openCurrencyRow, onCurrencyChange(), handleFileSelect(), removeFile(),
                       openFileOrZoom(), totalFileCount(), isLoggedIn, maxImagesPerItem,
                       maxImagesPerOrder, maxCharsMsg, msgMaxPerItem, msgMaxOrder.
  PHP vars from NewOrder::render(): $maxImagesPerItem, $maxFileSizeMb, $allowedMimeTypes.
  Layout: renders all fields. Each field div is a direct child — place inside a 6-col grid.
  Field divs carry col-span utility classes for the standard card/mobile grid layout.
  For table layouts, wrap each field individually in <td> without using this partial's wrappers.
--}}

{{-- URL — full width --}}
<div class="order-cell-url col-span-6">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_url') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
    </span>
    <textarea
        x-model="item.url"
        @blur="calcTotals(); saveDraft()"
        :placeholder="idx === 0 ? '{{ __('order_form.url_placeholder') }}' : ''"
        rows="1"
        class="order-form-input overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words min-h-[2.5rem]"
        :title="item.url || ''"
        x-init="$nextTick(() => {
            if ((item.url || '').trim()) {
                $el.style.height = 'auto';
                $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            } else {
                $el.style.height = '40px';
            }
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        })"
        @input="
            if (item.url.length > 2000) { item.url = item.url.slice(0, 2000); showNotify('error', maxCharsMsg); }
            if (!(item.url || '').trim()) {
                $el.style.height = '40px';
                $el.classList.remove('overflow-y-auto','scrollbar-hide');
                $el.classList.add('overflow-hidden');
                return;
            }
            $el.style.height = 'auto';
            $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        "
    ></textarea>
</div>

{{-- Color — half width --}}
<div class="order-cell-col col-span-3">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_color') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
    </span>
    <textarea
        x-model="item.color"
        @blur="saveDraft()"
        rows="1"
        class="order-form-input overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words min-h-[2.5rem]"
        :title="item.color || ''"
        x-init="$nextTick(() => {
            if ((item.color || '').trim()) {
                $el.style.height = 'auto';
                $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            } else {
                $el.style.height = '40px';
            }
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        })"
        @input="
            if (item.color.length > 2000) { item.color = item.color.slice(0, 2000); showNotify('error', maxCharsMsg); }
            if (!(item.color || '').trim()) {
                $el.style.height = '40px';
                $el.classList.remove('overflow-y-auto','scrollbar-hide');
                $el.classList.add('overflow-hidden');
                return;
            }
            $el.style.height = 'auto';
            $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        "
    ></textarea>
</div>

{{-- Size — half width --}}
<div class="order-cell-siz col-span-3">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_size') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
    </span>
    <textarea
        x-model="item.size"
        @blur="saveDraft()"
        rows="1"
        class="order-form-input overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words min-h-[2.5rem]"
        :title="item.size || ''"
        x-init="$nextTick(() => {
            if ((item.size || '').trim()) {
                $el.style.height = 'auto';
                $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            } else {
                $el.style.height = '40px';
            }
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        })"
        @input="
            if (item.size.length > 2000) { item.size = item.size.slice(0, 2000); showNotify('error', maxCharsMsg); }
            if (!(item.size || '').trim()) {
                $el.style.height = '40px';
                $el.classList.remove('overflow-y-auto','scrollbar-hide');
                $el.classList.add('overflow-hidden');
                return;
            }
            $el.style.height = 'auto';
            $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        "
    ></textarea>
</div>

{{-- Qty + Price + Currency — inline row, full width; children are flex inside --}}
<div class="order-cell-qty-prc-cur col-span-6 flex flex-nowrap items-end gap-2">
<div class="order-cell-qty min-w-0 flex-1">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_qty') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
    </span>
    <textarea
        x-model="item.qty"
        @blur="calcTotals(); saveDraft()"
        placeholder="1"
        rows="1"
        dir="rtl"
        class="order-form-input overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words min-h-[2.5rem]"
        :title="item.qty || ''"
        x-init="$nextTick(() => {
            if ((item.qty || '').trim()) {
                $el.style.height = 'auto';
                $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            } else {
                $el.style.height = '40px';
            }
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        })"
        @input="
            convertArabicNums($event);
            if (item.qty.length > 2000) { item.qty = item.qty.slice(0, 2000); showNotify('error', maxCharsMsg); }
            if (!(item.qty || '').trim()) {
                $el.style.height = '40px';
                $el.classList.remove('overflow-y-auto','scrollbar-hide');
                $el.classList.add('overflow-hidden');
                return;
            }
            $el.style.height = 'auto';
            $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        "
    ></textarea>
</div>

{{-- Price --}}
<div class="order-cell-prc min-w-0 flex-1">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_price_per_unit') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
    </span>
    <input
        type="text"
        x-model="item.price"
        @input="convertArabicNums($event)"
        @blur="calcTotals(); saveDraft()"
        inputmode="decimal"
        placeholder="{{ __('placeholder.amount') }}"
        class="order-form-input w-full px-3 py-2 min-w-[4ch] border border-primary-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"
    >
</div>

{{-- Currency --}}
<div class="order-cell-cur min-w-0 flex-1 min-w-[5rem]">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_currency') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
    </span>
    @include('livewire.partials._currency-dropdown')
</div>
</div>{{-- /order-cell-qty-prc-cur --}}

{{-- Notes — full width --}}
<div class="order-cell-not col-span-6">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_notes') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
    </span>
    <textarea
        x-model="item.notes"
        @blur="saveDraft()"
        :placeholder="idx === 0 ? '{{ __('order_form.notes_placeholder') }}' : ''"
        rows="1"
        class="order-form-input overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none break-words min-h-[2.5rem]"
        :title="item.notes || ''"
        x-init="$nextTick(() => {
            if ((item.notes || '').trim()) {
                $el.style.height = 'auto';
                $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            } else {
                $el.style.height = '40px';
            }
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        })"
        @input="
            if (item.notes.length > 2000) { item.notes = item.notes.slice(0, 2000); showNotify('error', maxCharsMsg); }
            if (!(item.notes || '').trim()) {
                $el.style.height = '40px';
                $el.classList.remove('overflow-y-auto','scrollbar-hide');
                $el.classList.add('overflow-hidden');
                return;
            }
            $el.style.height = 'auto';
            $el.style.height = Math.min(Math.max($el.scrollHeight, 40), 130) + 'px';
            const o = $el.scrollHeight > $el.offsetHeight;
            $el.classList.toggle('overflow-y-auto', o);
            $el.classList.toggle('scrollbar-hide', o);
            $el.classList.toggle('overflow-hidden', !o);
        "
    ></textarea>
</div>

{{-- Files — full width --}}
<div class="order-cell-files order-optional-section order-upload-container col-span-6">
    <span class="block text-xs text-slate-500 mb-0.5 font-medium">
        {{ __('order_form.th_files') }}
        <span class="order-field-optional">{{ __('order_form.optional') }}</span>
        <span class="order-field-optional" x-show="item._files && item._files.length > 0" x-cloak>— {{ __('order_form.file_info_bulk', ['max' => $maxImagesPerItem, 'size' => $maxFileSizeMb ?? 2]) }}</span>
    </span>
    <div class="flex items-center gap-2 flex-wrap">
        <template x-for="(f, fi) in (item._files || [])" :key="'itemfile-'+idx+'-'+fi">
            <div class="flex items-center gap-1">
                <div class="relative w-11 h-11 shrink-0 rounded-md overflow-hidden border border-slate-200 cursor-pointer"
                     @click="openFileOrZoom(f)">
                    <template x-if="f.preview"><img :src="f.preview" class="w-full h-full object-cover block" @click.stop="openFileOrZoom(f)" alt=""></template>
                    <template x-if="!f.preview && f.fileType === 'img'"><div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[10px] pointer-events-none">...</div></template>
                    <template x-if="!f.preview && f.fileType === 'pdf'"><div class="w-full h-full flex items-center justify-center bg-red-100 text-red-500 text-[10px] font-bold pointer-events-none">PDF</div></template>
                    <template x-if="!f.preview && f.fileType === 'xls'"><div class="w-full h-full flex items-center justify-center bg-green-100 text-green-600 text-[10px] font-bold pointer-events-none">XLS</div></template>
                    <template x-if="!f.preview && f.fileType === 'doc'"><div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 text-[10px] font-bold pointer-events-none">DOC</div></template>
                    <button type="button"
                            class="absolute top-0 end-0 w-3.5 h-3.5 bg-red-500 text-white border-none rounded-full text-[9px] font-bold cursor-pointer flex items-center justify-center z-10 leading-none"
                            :aria-label="'{{ __('order_form.remove') }}'"
                            @click.stop="removeFile(idx, fi)">&times;</button>
                </div>
                <template x-if="f.uploadProgress !== null && f.uploadProgress !== undefined">
                    <div class="w-12 h-1 bg-slate-100 rounded-sm overflow-hidden">
                        <div class="h-full bg-slate-400 rounded-sm transition-[width] duration-200" :style="'width:' + f.uploadProgress + '%'"></div>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="(item._files || []).length < maxImagesPerItem && totalFileCount() < maxImagesPerOrder">
            <span class="inline-flex">
                <input type="file"
                       :id="'order-item-file-' + idx"
                       class="hidden"
                       multiple
                       accept="{{ implode(',', $allowedMimeTypes ?? allowed_upload_mime_types()) }}"
                       @change="handleFileSelect($event, idx)">
                <label :for="'order-item-file-' + idx"
                       @click="if (!isLoggedIn) { $event.preventDefault(); $wire.openLoginModalForAttach(); }"
                       class="border border-dashed border-primary-100 text-slate-500 bg-primary-50 py-2 px-3 rounded-md text-xs font-medium cursor-pointer inline-flex items-center justify-center hover:border-primary-500 hover:text-primary-500 transition-colors">{{ __('order_form.attach') }}</label>
            </span>
        </template>
    </div>
</div>
