<div x-show="invoiceType !== 'second_final'" x-data="{ expanded: false }" class="border border-gray-200 rounded-xl p-3 bg-white/50">
    <button type="button" @click="expanded = !expanded" class="text-xs font-medium text-gray-600 flex items-center gap-1">
        <span x-text="expanded ? '▼' : '▶'">▶</span> {{ __('orders.invoice_fee_lines') }}
    </button>
    <div x-show="expanded" class="mt-3 space-y-2" x-cloak>
        @foreach (['agent_fee' => 'fee_agent_fee', 'local_shipping' => 'fee_local_shipping', 'international_shipping' => 'fee_international_shipping', 'photo_fee' => 'fee_photo_fee', 'extra_packing' => 'fee_extra_packing'] as $field => $labelKey)
        @php $fieldVal = $order->getAttribute($field); @endphp
        <div class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="include_{{ $field }}" value="0">
            <input type="checkbox" name="include_{{ $field }}" id="include_{{ $field }}" value="1"
                @if($fieldVal > 0) checked @endif
                class="rounded border-gray-300 text-primary-500 focus:ring-primary-400">
            <label for="include_{{ $field }}" class="text-xs text-gray-600 w-36">{{ __('orders.'.$labelKey) }}</label>
            <input type="number" step="0.01" min="0" name="fee_{{ $field }}" placeholder="0"
                value="{{ $fieldVal > 0 ? $fieldVal : '' }}"
                class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-xs">
        </div>
        @endforeach
    </div>
</div>
