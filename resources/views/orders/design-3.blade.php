{{-- Design 3: HTML table (desktop), production-style mobile layout — /new-order-design-3 --}}
@php
    $design3Currencies = order_form_currencies();
@endphp
<div class="bg-white text-slate-800 font-[family-name:var(--font-family-arabic)]" x-data='orderDesignForm({ "initialCount": 12, "currencyList": @json($design3Currencies) })'>
    <div class="max-w-7xl mx-auto p-4">
        <h1 class="text-xl font-bold text-slate-800 mb-2">{{ __('Create new order') }}</h1>

        <div id="order-form" style="padding-bottom: 14rem">
            <section class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 mb-4">
                {{-- Desktop: HTML table with sticky header --}}
                <div x-ref="tableScrollContainer" class="overflow-auto lg:max-h-[22rem] lg:min-w-0 hidden lg:block">
                    <table class="w-full border-collapse table-fixed min-w-[720px]">
                        <colgroup>
                            <col style="width:2rem">
                            <col style="width:9rem">
                            <col style="width:3.5rem">
                            <col style="width:5.5rem">
                            <col style="width:5.5rem">
                            <col style="width:4.25rem">
                            <col style="width:5.5rem">
                            <col style="width:9rem">
                            <col style="width:10rem">
                        </colgroup>
                        <thead class="sticky top-0 z-10 bg-orange-50 shadow-sm">
                            <tr>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">#</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_url') }}</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_qty') }}</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_color') }}</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_size') }}</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_price') }}</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_currency') }}</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_notes') }}</th>
                                <th class="text-start p-2 font-bold text-xs text-slate-800">{{ __('order_form.th_files') }}</th>
                            </tr>
                        </thead>
                        <tbody x-ref="tableBody" class="border-t border-orange-100">
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr class="border-b border-orange-100 bg-white">
                                    <td class="p-2 font-semibold text-sm text-slate-800" x-text="idx + 1"></td>
                                    <td class="p-2 overflow-hidden"><input type="text" x-model="item.url" @blur="calcTotals()" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-w-0 truncate" :title="item.url || ''"></td>
                                    <td class="p-2"><input type="tel" x-model="item.qty" @blur="calcTotals()" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" dir="rtl"></td>
                                    <td class="p-2"><input type="text" x-model="item.color" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"></td>
                                    <td class="p-2"><input type="text" x-model="item.size" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10"></td>
                                    <td class="p-2 overflow-hidden"><input type="text" x-model="item.price" @blur="calcTotals()" inputmode="decimal" class="order-form-input w-full px-2 py-2 border border-orange-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 max-w-full truncate" style="overflow:hidden;text-overflow:ellipsis" :title="item.price || ''"></td>
                                    <td class="p-2">
                                        <select x-model="item.currency" @change="calcTotals()" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                                            <template x-for="(cur, code) in currencyList" :key="code"><option :value="code" x-text="cur.label" :selected="code === item.currency"></option></template>
                                        </select>
                                    </td>
                                    <td class="p-2 overflow-hidden"><input type="text" x-model="item.notes" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 min-w-0 truncate" :title="item.notes || ''"></td>
                                    <td class="p-2">
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" class="border border-dashed border-orange-100 text-slate-500 bg-orange-50 py-2 px-3 rounded-md text-xs font-medium hover:border-primary-500 hover:text-primary-500 shrink-0">{{ __('order_form.attach') }}</button>
                                            <button type="button" @click="removeItem(idx)" class="py-2 px-3 rounded-md text-xs font-medium border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 shrink-0">{{ __('order_form.remove_row') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Mobile: production-style collapsible cards with summary bar + expandable fields --}}
                <div class="lg:hidden flex flex-col gap-2.5">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="order-item-card group border border-orange-100 rounded-xl overflow-hidden shadow-sm transition-all duration-150 relative"
                             :class="{
                                 'expanded': item._expanded,
                                 'is-valid': (item.url || '').trim().length > 0,
                                 'bg-white': item._expanded,
                                 'bg-orange-50/50': !item._expanded
                             }">

                            {{-- Mobile Summary Bar --}}
                            <div class="flex items-center justify-between gap-2 px-3 py-3 cursor-pointer select-none border-b border-orange-100"
                                 :class="{ 'bg-orange-50': !item._expanded, 'bg-white': item._expanded }"
                                 @click="toggleItem(idx)">
                                <div class="font-semibold text-sm text-slate-800 truncate flex-1 min-w-0" x-text="itemSummary(idx, item._expanded)"></div>
                                <div class="flex gap-2 items-center shrink-0" @click.stop>
                                    <button type="button"
                                            class="inline-flex items-center justify-center py-1.5 px-2.5 rounded-md text-xs font-semibold bg-primary-500/10 text-primary-500 border border-primary-500/25 hover:bg-primary-500/20 hover:border-primary-500 transition-colors"
                                            @click="item._expanded = !item._expanded">
                                        {{ __('order_form.show_edit') }}
                                    </button>
                                    <button type="button"
                                            class="inline-flex items-center justify-center py-1.5 px-2.5 rounded-md text-xs font-semibold bg-red-100/30 text-red-600 border border-red-200 hover:bg-red-100 hover:text-red-700 transition-colors"
                                            @click="removeItem(idx)">
                                        {{ __('order_form.remove') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Item Fields Grid (expandable on mobile) --}}
                            <div class="order-item-details p-3 max-lg:hidden max-lg:group-[.expanded]:grid max-lg:grid-cols-6 gap-2.5">
                                <div class="order-cell-url">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_url') }}</span>
                                    <input type="text" x-model="item.url" @blur="calcTotals()" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                                </div>
                                <div class="order-cell-qty">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_qty') }}</span>
                                    <input type="tel" x-model="item.qty" @blur="calcTotals()" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" dir="rtl">
                                </div>
                                <div class="order-cell-col">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_color') }}</span>
                                    <input type="text" x-model="item.color" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                                </div>
                                <div class="order-cell-siz">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_size') }}</span>
                                    <input type="text" x-model="item.size" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                                </div>
                                <div class="order-cell-prc">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_price') }}</span>
                                    <input type="text" x-model="item.price" @blur="calcTotals()" class="order-form-input w-full px-3 py-2 min-w-[4ch] border border-orange-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                                </div>
                                <div class="order-cell-cur">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_currency') }}</span>
                                    <select x-model="item.currency" @change="calcTotals()" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                                        <template x-for="(cur, code) in currencyList" :key="code"><option :value="code" x-text="cur.label" :selected="code === item.currency"></option></template>
                                    </select>
                                </div>
                                <div class="order-cell-not">
                                    <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_notes') }}</span>
                                    <input type="text" x-model="item.notes" class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white h-10 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                                </div>
                                <div class="order-optional-section">
                                    <div class="order-upload-container flex items-center gap-2">
                                        <span class="block text-xs text-slate-500 mb-0.5 font-medium">{{ __('order_form.th_files') }}</span>
                                        <button type="button" class="border border-dashed border-orange-100 text-slate-500 bg-orange-50 py-2 px-3 rounded-md text-xs font-medium hover:border-primary-500 hover:text-primary-500">{{ __('order_form.attach') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </section>

            {{-- Always visible: Add Product + General Notes (sticky above footer) --}}
            <div class="sticky bottom-[5.5rem] lg:bottom-[4.5rem] z-50 bg-white -mx-4 px-4 pt-2 pb-2 border-t border-orange-100/60 shadow-[0_-4px_12px_rgba(0,0,0,0.04)]">
                <button type="button" @click="addItem()" class="w-full py-3 inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary-500/10 to-primary-400/5 text-primary-500 border-2 border-primary-500/25 font-semibold rounded-md text-sm hover:from-primary-500/20 hover:to-primary-400/10 hover:border-primary-500 transition-all">
                    + {{ __('order_form.add_product') }}
                </button>
                <section class="mt-2">
                    <h3 class="text-base mb-1.5">{{ __('order_form.general_notes') }}</h3>
                    <textarea x-model="orderNotes"
                              placeholder="{{ __('order_form.general_notes_ph') }}"
                              rows="2"
                              class="order-form-input w-full px-3 py-2 border border-orange-100 rounded-lg text-sm bg-white resize-y focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 transition-colors"></textarea>
                </section>
            </div>

            {{-- Sticky Footer --}}
            <div class="order-summary-card">
                <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                    <span class="text-[0.7rem] font-normal text-stone-400 whitespace-nowrap overflow-hidden text-ellipsis" x-text="productCountText()"></span>
                    <span class="text-stone-400 font-normal text-[0.7rem] whitespace-nowrap" x-text="totalText()"></span>
                </div>
                <button type="button" disabled
                        class="shrink-0 min-w-[120px] max-w-[180px] w-auto inline-flex items-center justify-center py-3 px-4 rounded-md font-semibold text-base w-full bg-gradient-to-r from-primary-500 to-primary-400 text-white shadow-lg shadow-primary-500/25 opacity-80 cursor-not-allowed">
                    {{ __('order_form.confirm_order') }}
                </button>
            </div>
        </div>
    </div>
</div>
