{{-- Design 2: HTML table, compact — /new-order-design-2 --}}
<div class="bg-white text-slate-800 font-[family-name:var(--font-family-arabic)]" x-data="orderDesignForm">
    <div class="max-w-7xl mx-auto p-4">
        <h1 class="text-xl font-bold text-slate-800 mb-2">{{ __('Create new order') }}</h1>
        <p class="text-sm text-slate-500 mb-4">{{ __('design_demo.design_2') }} — {{ __('design_demo.subtitle_2') }}</p>

        <section class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 mb-4">
            <div class="overflow-x-auto lg:min-w-0">
                <table class="w-full border-collapse hidden lg:table text-sm">
                    <thead>
                        <tr class="bg-orange-50">
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800 w-6">#</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_url') }}</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_qty') }}</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_color') }}</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_size') }}</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_price') }}</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_currency') }}</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_notes') }}</th>
                            <th class="text-start py-1.5 px-2 font-bold text-xs text-slate-800">{{ __('order_form.th_files') }}</th>
                        </tr>
                    </thead>
                    <tbody class="border-t border-orange-100">
                        <template x-for="(item, idx) in items" :key="idx">
                            <tr class="border-b border-orange-100">
                                <td class="py-1.5 px-2 font-semibold text-xs text-slate-800" x-text="idx + 1"></td>
                                <td class="py-1.5 px-2"><input type="text" x-model="item.url" class="order-form-input w-full px-2 py-1.5 border border-orange-100 rounded text-xs h-8 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500/10"></td>
                                <td class="py-1.5 px-2"><input type="tel" x-model="item.qty" class="order-form-input w-full px-2 py-1.5 border border-orange-100 rounded text-xs h-8 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500/10" dir="rtl"></td>
                                <td class="py-1.5 px-2"><input type="text" x-model="item.color" class="order-form-input w-full px-2 py-1.5 border border-orange-100 rounded text-xs h-8 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500/10"></td>
                                <td class="py-1.5 px-2"><input type="text" x-model="item.size" class="order-form-input w-full px-2 py-1.5 border border-orange-100 rounded text-xs h-8 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500/10"></td>
                                <td class="py-1.5 px-2"><input type="text" x-model="item.price" class="order-form-input w-full px-2 py-1.5 min-w-[3ch] border border-orange-100 rounded text-xs h-8 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500/10"></td>
                                <td class="py-1.5 px-2">
                                    <select x-model="item.currency" class="order-form-input w-full px-2 py-1.5 border border-orange-100 rounded text-xs h-8 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500/10">
                                        <template x-for="(cur, code) in currencyList" :key="code"><option :value="code" x-text="cur.label" :selected="code === item.currency"></option></template>
                                    </select>
                                </td>
                                <td class="py-1.5 px-2"><input type="text" x-model="item.notes" class="order-form-input w-full px-2 py-1.5 border border-orange-100 rounded text-xs h-8 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500/10"></td>
                                <td class="py-1.5 px-2">
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="border border-dashed border-orange-100 text-slate-500 bg-orange-50 py-1 px-2 rounded text-[0.7rem] font-medium hover:border-primary-500 hover:text-primary-500">{{ __('order_form.attach') }}</button>
                                        <button type="button" @click="removeItem(idx)" class="py-1 px-2 rounded text-[0.7rem] font-medium border border-red-200 text-red-600 bg-red-50 hover:bg-red-100">{{ __('order_form.remove_row') }}</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile: simple cards --}}
            <div class="lg:hidden space-y-3">
                <template x-for="(item, idx) in items" :key="idx">
                    <div class="border border-orange-100 rounded-lg p-3 bg-orange-50/50">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-sm">#<span x-text="idx + 1"></span></span>
                            <button type="button" @click="removeItem(idx)" class="text-red-600 text-xs font-medium">{{ __('order_form.remove') }}</button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <input type="text" x-model="item.url" placeholder="{{ __('order_form.th_url') }}" class="order-form-input col-span-2 px-3 py-2 border border-orange-100 rounded-lg">
                            <input type="tel" x-model="item.qty" placeholder="{{ __('order_form.th_qty') }}" class="order-form-input px-3 py-2 border border-orange-100 rounded-lg" dir="rtl">
                            <input type="text" x-model="item.price" placeholder="{{ __('order_form.th_price') }}" class="order-form-input px-3 py-2 border border-orange-100 rounded-lg">
                            <input type="text" x-model="item.color" placeholder="{{ __('order_form.th_color') }}" class="order-form-input px-3 py-2 border border-orange-100 rounded-lg">
                            <input type="text" x-model="item.size" placeholder="{{ __('order_form.th_size') }}" class="order-form-input px-3 py-2 border border-orange-100 rounded-lg">
                            <input type="text" x-model="item.notes" placeholder="{{ __('order_form.th_notes') }}" class="order-form-input col-span-2 px-3 py-2 border border-orange-100 rounded-lg">
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="addItem()" class="w-full mt-4 py-2.5 inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary-500/10 to-primary-400/5 text-primary-500 border-2 border-primary-500/25 font-semibold rounded-md text-sm hover:from-primary-500/20 hover:to-primary-400/10 hover:border-primary-500 transition-all">
                + {{ __('order_form.add_product') }}
            </button>
        </section>

        <div class="flex gap-2 text-sm text-slate-500 mb-4">
            <a href="{{ route('new-order') }}" class="underline">{{ __('design_demo.production_form') }}</a>
            <a href="{{ url('/new-order-design-1') }}" class="underline">{{ __('design_demo.design_1') }}</a>
            <a href="{{ url('/new-order-design-3') }}" class="underline">{{ __('design_demo.design_3') }}</a>
        </div>
    </div>
</div>

