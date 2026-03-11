{{-- Compact single product form for wizard Step 1 - all fields, no-scroll friendly --}}
<div class="grid grid-cols-6 gap-2">
    <div class="col-span-6">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_url') }}</label>
        <textarea x-model="item.url" @blur="calcTotals(); saveDraft()" :placeholder="idx === 0 ? '{{ __('order_form.url_placeholder') }}' : ''" rows="2" class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none" :title="item.url || ''"></textarea>
    </div>
    <div class="col-span-3 sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_color') }}</label>
        <input type="text" x-model="item.color" @blur="saveDraft()" class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" :title="item.color || ''">
    </div>
    <div class="col-span-3 sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_size') }}</label>
        <input type="text" x-model="item.size" @blur="saveDraft()" class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" :title="item.size || ''">
    </div>
    <div class="col-span-6 sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_qty') }}</label>
        <input type="text" x-model="item.qty" @input="convertArabicNums($event)" @blur="calcTotals(); saveDraft()" placeholder="1" dir="rtl" class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
    </div>
    <div class="col-span-3 sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_price_per_unit') }}</label>
        <input type="text" x-model="item.price" @input="convertArabicNums($event)" @blur="calcTotals(); saveDraft()" inputmode="decimal" placeholder="{{ __('placeholder.amount') }}" class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
    </div>
    <div class="col-span-3 sm:col-span-2">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_currency') }}</label>
        @include('livewire.partials._currency-dropdown')
    </div>
    <div class="col-span-6">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_notes') }}</label>
        <input type="text" x-model="item.notes" @blur="saveDraft()" :placeholder="idx === 0 ? '{{ __('order_form.notes_placeholder') }}' : ''" class="order-form-input w-full px-3 py-2 border border-primary-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" :title="item.notes || ''">
    </div>
    <div class="col-span-6">
        <label class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_files') }}</label>
        <div class="flex items-center gap-2 flex-wrap">
            <template x-for="(f, fi) in (item._files || [])" :key="'wfile-'+idx+'-'+fi">
                <div class="flex items-center gap-1">
                    <div class="relative w-11 h-11 shrink-0 rounded-md overflow-hidden border border-slate-200 cursor-pointer" @click="openFileOrZoom(f)">
                        <template x-if="f.preview"><img :src="f.preview" class="w-full h-full object-cover block" @click.stop="openFileOrZoom(f)" alt=""></template>
                        <template x-if="!f.preview && f.fileType === 'img'"><div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[10px]">...</div></template>
                        <template x-if="!f.preview && f.fileType === 'pdf'"><div class="w-full h-full flex items-center justify-center bg-red-100 text-red-500 text-[10px] font-bold">PDF</div></template>
                        <template x-if="!f.preview && f.fileType !== 'img' && f.fileType !== 'pdf'"><div class="w-full h-full flex items-center justify-center bg-slate-100 text-[10px]">...</div></template>
                        <button type="button" class="absolute top-0 start-0 w-4 h-4 bg-red-500/90 text-white border-none rounded-full text-[10px] cursor-pointer flex items-center justify-center z-10" @click.stop="removeFile(idx, fi)">×</button>
                    </div>
                </div>
            </template>
            <template x-if="(item._files || []).length < maxImagesPerItem && totalFileCount() < maxImagesPerOrder">
                <span class="inline-flex">
                    <input type="file" :id="'order-file-wizard-' + idx" class="hidden" multiple
                           accept="{{ implode(',', $allowedMimeTypes ?? allowed_upload_mime_types()) }}"
                           @change="handleFileSelect($event, idx)">
                    <label :for="'order-file-wizard-' + idx"
                           @click="if (!isLoggedIn) { $event.preventDefault(); $wire.openLoginModalForAttach(); }"
                           class="border border-dashed border-primary-100 text-slate-500 bg-primary-50 py-2 px-3 rounded-md text-xs font-medium cursor-pointer inline-flex items-center justify-center hover:border-primary-500 hover:text-primary-500 transition-colors">{{ __('order_form.attach') }}</label>
                </span>
            </template>
        </div>
        <p class="text-[0.65rem] text-slate-500 mt-1 leading-tight">{{ __('order_form.file_info_bulk', ['max' => $maxImagesPerItem, 'size' => $maxFileSizeMb ?? 2]) }}</p>
    </div>
</div>
