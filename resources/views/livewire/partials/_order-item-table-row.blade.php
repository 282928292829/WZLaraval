<tr class="border-b border-primary-100 bg-white" x-data="{
  syncRowHeights(el) {
    const tr = el.closest('tr');
    if (!tr) return;
    const textareas = tr.querySelectorAll('textarea.order-field-sync');
    let maxH = 40;
    textareas.forEach(ta => { ta.style.height = 'auto'; });
    textareas.forEach(ta => {
      const v = (ta.value || '').trim();
      if (v) maxH = Math.max(maxH, Math.min(ta.scrollHeight, 130));
    });
    textareas.forEach(ta => {
      ta.style.height = maxH + 'px';
      const overflows = ta.scrollHeight > ta.offsetHeight;
      ta.classList.toggle('overflow-y-auto', overflows);
      ta.classList.toggle('scrollbar-hide', overflows);
      ta.classList.toggle('overflow-hidden', !overflows);
    });
  },
  resizeQty(el) {
    if (!(el.value || '').trim()) { el.style.height = '40px'; el.classList.remove('overflow-y-auto','scrollbar-hide'); el.classList.add('overflow-hidden'); return; }
    el.style.height = 'auto';
    el.style.height = Math.min(Math.max(el.scrollHeight, 40), 130) + 'px';
    const o = el.scrollHeight > el.offsetHeight;
    el.classList.toggle('overflow-y-auto', o);
    el.classList.toggle('scrollbar-hide', o);
    el.classList.toggle('overflow-hidden', !o);
  }
}">
    <td class="p-2 align-middle text-center">
        <button type="button" class="w-7 h-7 inline-flex items-center justify-center rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" @click.stop="removeItem(idx)" :aria-label="'{{ __('order_form.remove_row') }}'" title="{{ __('order_form.remove_row') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </td>
    <td class="p-2 align-middle font-semibold text-sm text-slate-800 leading-none" x-text="idx + 1"></td>
    <td class="p-2 align-top min-w-0">
        <textarea x-model="item.url" @blur="calcTotals(); saveDraft()" :placeholder="idx === 0 ? '{{ __('order_form.url_placeholder') }}' : ''" rows="1"
                  class="order-form-input order-field-sync overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-w-0 resize-none break-words"
                  style="min-height: 2.5rem;"
                  :title="item.url || ''"
                  x-init="$nextTick(() => syncRowHeights($el))"
                  @input="
                    if (item.url.length > 2000) { item.url = item.url.slice(0, 2000); showNotify('error', maxCharsMsg); }
                    syncRowHeights($el);
                  "></textarea>
    </td>
    <td class="p-2 align-top min-w-0">
        <textarea x-model="item.color" @blur="saveDraft()" rows="1"
                  class="order-form-input order-field-sync overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-w-0 resize-none break-words"
                  style="min-height: 2.5rem;"
                  :title="item.color || ''"
                  x-init="$nextTick(() => syncRowHeights($el))"
                  @input="
                    if (item.color.length > 2000) { item.color = item.color.slice(0, 2000); showNotify('error', maxCharsMsg); }
                    syncRowHeights($el);
                  "></textarea>
    </td>
    <td class="p-2 align-top min-w-0">
        <textarea x-model="item.size" @blur="saveDraft()" rows="1"
                  class="order-form-input order-field-sync overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-w-0 resize-none break-words"
                  style="min-height: 2.5rem;"
                  :title="item.size || ''"
                  x-init="$nextTick(() => syncRowHeights($el))"
                  @input="
                    if (item.size.length > 2000) { item.size = item.size.slice(0, 2000); showNotify('error', maxCharsMsg); }
                    syncRowHeights($el);
                  "></textarea>
    </td>
    <td class="p-2 align-middle">
        <textarea x-model="item.qty" @input="convertArabicNums($event)" @blur="calcTotals(); saveDraft()" placeholder="1" rows="1" dir="rtl"
                  class="order-form-input overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-w-0 resize-none break-words"
                  style="min-height: 2.5rem;"
                  :title="item.qty || ''"
                  x-init="$nextTick(() => resizeQty($el))"
                  @input="
                    convertArabicNums($event);
                    if (item.qty.length > 2000) { item.qty = item.qty.slice(0, 2000); showNotify('error', maxCharsMsg); }
                    resizeQty($el);
                  "></textarea>
    </td>
    <td class="p-2 align-middle"><input type="text" x-model="item.price" @input="convertArabicNums($event)" @blur="calcTotals(); saveDraft()" inputmode="decimal" placeholder="{{ __('placeholder.amount') }}" class="order-form-input w-full px-2 py-2 border border-primary-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 max-w-full truncate" style="overflow:hidden;text-overflow:ellipsis" :title="item.price || ''"></td>
    <td class="p-2 align-middle relative">
        @include('livewire.partials._currency-dropdown')
    </td>
    <td class="p-2 align-top min-w-0">
        <textarea x-model="item.notes" @blur="saveDraft()" :placeholder="idx === 0 ? '{{ __('order_form.notes_placeholder') }}' : ''" rows="1"
                  class="order-form-input order-field-sync overflow-hidden w-full px-3 py-2 border border-primary-100 rounded-lg text-sm focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-w-0 resize-none break-words"
                  style="min-height: 2.5rem;"
                  :title="item.notes || ''"
                  x-init="$nextTick(() => syncRowHeights($el))"
                  @input="
                    if (item.notes.length > 2000) { item.notes = item.notes.slice(0, 2000); showNotify('error', maxCharsMsg); }
                    syncRowHeights($el);
                  "></textarea>
    </td>
    <td class="p-2 align-top">
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <template x-for="(f, fi) in (item._files || [])" :key="'file-'+idx+'-'+fi">
                <div class="flex items-center gap-1">
                    <div class="relative w-11 h-11 shrink-0 rounded-md overflow-hidden border border-slate-200 cursor-pointer"
                         @click="openFileOrZoom(f)">
                        <template x-if="f.preview"><img :src="f.preview" class="w-full h-full object-cover block cursor-pointer" @click.stop="openFileOrZoom(f)" alt=""></template>
                        <template x-if="!f.preview && f.fileType === 'img'"><div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[10px] pointer-events-none">...</div></template>
                        <template x-if="!f.preview && f.fileType === 'pdf'"><div class="w-full h-full flex items-center justify-center bg-red-100 text-red-500 text-[10px] font-bold pointer-events-none">PDF</div></template>
                        <template x-if="!f.preview && f.fileType === 'xls'"><div class="w-full h-full flex items-center justify-center bg-green-100 text-green-600 text-[10px] font-bold pointer-events-none">XLS</div></template>
                        <template x-if="!f.preview && f.fileType === 'doc'"><div class="w-full h-full flex items-center justify-center bg-blue-100 text-blue-600 text-[10px] font-bold pointer-events-none">DOC</div></template>
                        <button type="button" class="absolute top-0 start-0 w-4 h-4 bg-red-500/90 text-white border-none rounded-full text-[10px] cursor-pointer flex items-center justify-center z-10" @click.stop="removeFile(idx, fi)">×</button>
                    </div>
                    <template x-if="f.uploadProgress !== null && f.uploadProgress !== undefined"><div class="w-12 h-1 bg-slate-100 rounded-sm overflow-hidden"><div class="h-full bg-slate-400 rounded-sm transition-[width] duration-200" :style="'width:' + f.uploadProgress + '%'"></div></div></template>
                </div>
            </template>
            <template x-if="(item._files || []).length < maxImagesPerItem && totalFileCount() < maxImagesPerOrder">
                <span class="inline-flex min-h-[44px] min-w-[44px] cursor-pointer select-none"
                      @if(auth()->guest())
                      @click.prevent="$dispatch('open-login-modal-attach')"
                      @else
                      @click.prevent="$event.currentTarget.querySelector('input[type=file]')?.click()"
                      @endif>
                    <input type="file" class="hidden" multiple
                           accept="{{ implode(',', $allowedMimeTypes ?? allowed_upload_mime_types()) }}"
                           @change="handleFileSelect($event, idx)">
                    <span class="border border-dashed border-primary-100 text-slate-500 bg-primary-50 py-2 px-3 rounded-md text-xs font-medium inline-flex items-center justify-center hover:border-primary-500 hover:bg-primary-50 hover:text-primary-500 transition-colors">{{ __('order_form.attach') }}</span>
                </span>
            </template>
        </div>
    </td>
</tr>
