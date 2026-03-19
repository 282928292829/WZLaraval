<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    @if (!$editing)
        {{-- Read-only view: table + edit banner --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">
                {{ __('orders.items') }}
                <span class="ms-1 text-xs font-normal text-gray-400">({{ $order->items->count() }})</span>
            </h2>
            <div class="flex items-center gap-3">
                @if ($isStaff)
                    <a href="{{ route('orders.export-excel', $order) }}"
                        class="inline-flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-700 font-medium"
                        title="{{ __('orders.export_excel') }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('orders.export_excel') }}
                    </a>
                @endif
                @if ($isStaff && auth()->user()->can('edit-prices'))
                    <button type="button" x-data @click="$dispatch('open-edit-prices')"
                        class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                        {{ __('orders.edit_prices') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            @include('orders.partials.items-table-readonly', [
                'order' => $order,
                'isOwner' => $isOwner,
                'isStaff' => $isStaff,
                'customerCanAddFiles' => $customerCanAddFiles,
                'maxFilesPerItemAfterSubmit' => $maxFilesPerItemAfterSubmit,
            ])
        </div>

        @if ($orderEditEnabled && $canEditItems)
            <div class="border-t border-amber-100 bg-amber-50 px-4 py-2.5 flex items-center gap-2 text-xs text-amber-700">
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                {{ __('orders.click_edit_within', ['time' => $clickEditRemaining]) }}
                <a href="{{ route('new-order') }}?edit={{ $order->id }}" class="ms-auto font-medium text-amber-800 hover:underline">
                    {{ __('orders.edit_items') }} →
                </a>
            </div>
        @endif
    @else
        {{-- Edit mode: editable form --}}
        <div class="px-4 py-3 border-b border-amber-100 bg-amber-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-amber-800">
                {{ __('orders.edit_order_title', ['number' => $order->order_number]) }}
            </h2>
            @if ($resubmitDeadline)
                <span class="text-xs text-amber-700">{{ __('orders.edit_resubmit_deadline_hint') }} {{ $resubmitDeadline }}</span>
            @endif
        </div>

        <form wire:submit="save" class="p-4 space-y-4">
            {{-- Desktop: table --}}
            <div class="overflow-x-auto hidden lg:block">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                            <th class="p-2 w-9"></th>
                            <th class="p-2 text-start w-8">#</th>
                            <th class="p-2 text-start">{{ __('order_form.th_url') }}</th>
                            <th class="p-2 text-start w-20">{{ __('order_form.th_color') }}</th>
                            <th class="p-2 text-start w-20">{{ __('order_form.th_size') }}</th>
                            <th class="p-2 text-start w-16">{{ __('order_form.th_qty') }}</th>
                            <th class="p-2 text-start w-24">{{ __('order_form.th_price_per_unit') }}</th>
                            <th class="p-2 text-start w-24">{{ __('order_form.th_currency') }}</th>
                            <th class="p-2 text-start">{{ __('order_form.th_notes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($items as $idx => $item)
                            <tr class="border-b border-primary-100">
                                <td class="p-2">
                                    @if (count($items) > 1)
                                        <button type="button" wire:click="removeProduct({{ $idx }})" wire:confirm="{{ __('order_form.reset_confirm') }}"
                                            class="w-7 h-7 inline-flex items-center justify-center rounded text-gray-400 hover:text-red-600 hover:bg-red-50"
                                            title="{{ __('order_form.remove_row') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </td>
                                <td class="p-2 font-medium text-gray-600">{{ $idx + 1 }}</td>
                                <td class="p-2"><input type="text" wire:model="items.{{ $idx }}.url" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500" placeholder="{{ __('order_form.url_placeholder') }}"></td>
                                <td class="p-2"><input type="text" wire:model="items.{{ $idx }}.color" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20"></td>
                                <td class="p-2"><input type="text" wire:model="items.{{ $idx }}.size" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20"></td>
                                <td class="p-2"><input type="text" wire:model="items.{{ $idx }}.qty" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20" inputmode="numeric"></td>
                                <td class="p-2"><input type="text" wire:model="items.{{ $idx }}.price" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20" inputmode="decimal"></td>
                                <td class="p-2">
                                    <select wire:model="items.{{ $idx }}.currency" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20">
                                        @foreach ($currencies as $code => $cur)
                                            <option value="{{ $code }}">{{ $cur['label'] ?? $code }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2"><input type="text" wire:model="items.{{ $idx }}.notes" class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20" placeholder="{{ __('order_form.notes_placeholder') }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: cards --}}
            <div class="lg:hidden space-y-3">
                @foreach ($items as $idx => $item)
                    <div class="border border-primary-100 rounded-xl p-3 bg-white space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700">{{ __('order_form.product_num') }} {{ $idx + 1 }}</span>
                            @if (count($items) > 1)
                                <button type="button" wire:click="removeProduct({{ $idx }})" wire:confirm="{{ __('order_form.reset_confirm') }}"
                                    class="text-red-500 hover:text-red-600 text-sm">{{ __('order_form.remove') }}</button>
                            @endif
                        </div>
                        <div><label class="text-xs text-gray-500">{{ __('order_form.th_url') }}</label><input type="text" wire:model="items.{{ $idx }}.url" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" placeholder="{{ __('order_form.url_placeholder') }}"></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="text-xs text-gray-500">{{ __('order_form.th_qty') }}</label><input type="text" wire:model="items.{{ $idx }}.qty" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" inputmode="numeric"></div>
                            <div><label class="text-xs text-gray-500">{{ __('order_form.th_price_per_unit') }}</label><input type="text" wire:model="items.{{ $idx }}.price" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" inputmode="decimal"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="text-xs text-gray-500">{{ __('order_form.th_color') }}</label><input type="text" wire:model="items.{{ $idx }}.color" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg"></div>
                            <div><label class="text-xs text-gray-500">{{ __('order_form.th_size') }}</label><input type="text" wire:model="items.{{ $idx }}.size" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg"></div>
                        </div>
                        <div><label class="text-xs text-gray-500">{{ __('order_form.th_currency') }}</label>
                            <select wire:model="items.{{ $idx }}.currency" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                                @foreach ($currencies as $code => $cur)
                                    <option value="{{ $code }}">{{ $cur['label'] ?? $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="text-xs text-gray-500">{{ __('order_form.th_notes') }}</label><input type="text" wire:model="items.{{ $idx }}.notes" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" placeholder="{{ __('order_form.notes_placeholder') }}"></div>
                    </div>
                @endforeach
            </div>

            <button type="button" wire:click="addProduct" class="w-full py-2.5 inline-flex items-center justify-center gap-2 border-2 border-dashed border-primary-500/30 text-primary-600 font-medium rounded-lg hover:border-primary-500/50 hover:bg-primary-50/50 transition-colors">
                + {{ __('order_form.add_product') }}
            </button>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('order_form.general_notes') }}</label>
                <textarea wire:model="orderNotes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500" placeholder="{{ __('order_form.general_notes_ph') }}"></textarea>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 pt-2">
                <button type="submit" class="flex-1 py-3 px-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                    {{ __('orders.save_changes') }}
                </button>
                <button type="button" wire:click="cancelEdit" class="flex-1 py-3 px-4 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('orders.cancel') }}
                </button>
            </div>
        </form>
    @endif
</div>
