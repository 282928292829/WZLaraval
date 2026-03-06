<table class="w-full text-sm table-fixed">
    <thead>
        <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100 bg-gray-50/50">
            <th class="px-4 py-2 font-medium text-start w-8">#</th>
            <th class="px-4 py-2 font-medium text-start w-[40%] min-w-0">{{ __('orders.product') }}</th>
            <th class="px-4 py-2 font-medium text-center w-14">{{ __('orders.qty') }}</th>
            <th class="px-4 py-2 font-medium text-start w-24 hidden sm:table-cell">{{ __('orders.color') }}</th>
            <th class="px-4 py-2 font-medium text-start w-24 hidden sm:table-cell">{{ __('orders.size') }}</th>
            <th class="px-4 py-2 font-medium text-start w-28 hidden sm:table-cell">{{ __('orders.price') }}</th>
            @if ($isStaff)
                <th class="px-4 py-2 font-medium text-start w-28 hidden sm:table-cell">{{ __('orders.final') }}</th>
            @endif
            <th class="px-4 py-2 font-medium text-start hidden md:table-cell">{{ __('orders.notes') }}</th>
            <th class="px-4 py-2 font-medium text-center w-14">{{ __('orders.image') }}</th>
            <th class="px-4 py-2 font-medium text-center w-28">{{ __('orders.attach') }}</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse ($order->items as $i => $item)
            @php
                $itemAllFiles = collect();
                if ($item->image_path) {
                    $itemAllFiles->push(['url' => \Illuminate\Support\Facades\Storage::disk('public')->url($item->image_path), 'source' => 'item', 'id' => $item->id, 'is_image' => true, 'name' => '']);
                }
                foreach ($order->files->where('order_item_id', $item->id) as $f) {
                    $itemAllFiles->push(['url' => $f->url(), 'source' => 'file', 'id' => $f->id, 'is_image' => $f->isImage(), 'name' => $f->original_name]);
                }
                $itemFileCount = $itemAllFiles->count();
                $canAddFiles = $isStaff || ($isOwner && $customerCanAddFiles);
                $slotsLeft = max(0, $maxFilesPerItemAfterSubmit - $itemFileCount);
            @endphp
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-4 py-3 align-middle">
                    <span class="text-xs text-gray-500 font-medium">{{ $i + 1 }}</span>
                </td>
                <td class="px-4 py-3 align-middle min-w-0">
                    @if (trim($item->url ?? '') !== '')
                        @php
                            $safeUrl = safe_item_url($item->url);
                            $displayText = $safeUrl
                                ? (parse_url($safeUrl, PHP_URL_HOST) ?: $item->url)
                                : $item->url;
                            $displayText = preg_replace('/^www\./i', '', $displayText);
                        @endphp
                        <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                            <span class="text-gray-800 font-medium truncate shrink-0 max-w-full" title="{{ $item->url }}">{{ $displayText }}</span>
                            @if ($safeUrl)
                                <a href="{{ $safeUrl }}" target="_blank" rel="noopener"
                                    class="shrink-0 inline-flex items-center gap-0.5 text-xs font-semibold py-1 px-2 rounded-md border border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors">
                                    {{ __('orders.view') }}
                                </a>
                            @else
                                <span class="shrink-0 inline-flex items-center gap-0.5 text-xs font-semibold py-1 px-2 rounded-md border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-60"
                                    title="{{ __('orders.view_disabled_unsafe') }}">
                                    {{ __('orders.view') }}
                                </span>
                            @endif
                            <button type="button"
                                x-data="{ copied: false }"
                                data-copy-url="{{ e($item->url) }}"
                                @click="(async () => {
                                    const url = $el.getAttribute('data-copy-url');
                                    try { await navigator.clipboard.writeText(url); } catch(e) {
                                        const ta = document.createElement('textarea'); ta.value = url;
                                        ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta);
                                        ta.focus(); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
                                    }
                                    copied = true; setTimeout(() => copied = false, 2000);
                                })()"
                                class="shrink-0 inline-flex items-center gap-0.5 text-xs font-semibold py-1 px-2 rounded-md border border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors"
                                :class="copied && '!border-green-200 !bg-green-50 !text-green-700'"
                                title="{{ __('orders.copy') }}">
                                <span x-show="!copied">{{ __('orders.copy') }}</span>
                                <span x-show="copied" x-cloak>{{ __('orders.copied') }}</span>
                            </button>
                        </div>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                    <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5 text-xs text-gray-400 sm:hidden">
                        @if ($item->color)<span>{{ $item->color }}</span>@endif
                        @if ($item->size)<span>{{ $item->size }}</span>@endif
                        @if ($item->currency && $item->unit_price)<span>{{ number_format($item->unit_price, 2) }} {{ $item->currency }}</span>@endif
                        @if ($isStaff && $item->final_price)<span class="text-primary-600">{{ number_format($item->final_price, 2) }} SAR</span>@endif
                        @if ($item->notes)<span class="italic text-gray-300 md:hidden">{{ Str::limit($item->notes, 40) }}</span>@endif
                    </div>
                </td>
                <td class="px-2 py-3 align-middle text-center">
                    <span class="text-xs font-semibold text-gray-800">{{ $item->qty }}</span>
                </td>
                <td class="px-4 py-3 align-middle text-gray-600 hidden sm:table-cell">{{ $item->color ?: '—' }}</td>
                <td class="px-4 py-3 align-middle text-gray-600 hidden sm:table-cell">{{ $item->size ?: '—' }}</td>
                <td class="px-2 py-3 align-middle text-xs text-gray-600 hidden sm:table-cell tabular-nums" dir="ltr">
                    @if ($item->currency && $item->unit_price){{ number_format($item->unit_price, 2) }} {{ $item->currency }}@else—@endif
                </td>
                @if ($isStaff)
                    <td class="px-2 py-3 align-middle text-xs hidden sm:table-cell tabular-nums" dir="ltr">
                        @if ($item->final_price)<span class="font-medium text-primary-600">{{ number_format($item->final_price, 2) }} SAR</span>
                        @else<span class="text-gray-300">—</span>@endif
                    </td>
                @endif
                <td class="px-4 py-3 align-middle text-xs text-gray-400 italic hidden md:table-cell max-w-[160px]">
                    <div class="truncate" title="{{ $item->notes }}">{{ $item->notes ?: '—' }}</div>
                </td>
                <td class="px-4 py-3 align-middle">
                    @if ($itemAllFiles->isNotEmpty())
                        <div class="flex flex-wrap gap-1 justify-center">
                            @foreach ($itemAllFiles as $itemFile)
                                <div class="relative group">
                                    @if ($itemFile['is_image'] ?? true)
                                        <button type="button"
                                            @click="$dispatch('open-lightbox', { src: '{{ $itemFile['url'] }}', gallery: window.orderLightboxImages })"
                                            class="shrink-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400 block">
                                            <img src="{{ $itemFile['url'] }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-100 cursor-zoom-in hover:opacity-90 transition-opacity">
                                        </button>
                                    @else
                                        <a href="{{ $itemFile['url'] }}" target="_blank" class="shrink-0 w-10 h-10 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-gray-100 block" title="{{ $itemFile['name'] ?? '' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>
                                    @endif
                                    @if ($isStaff)
                                        <form action="{{ route('orders.product-image.delete', $order) }}" method="POST" class="absolute -top-1 -end-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                            x-data x-on:submit="if (!confirm($el.getAttribute('data-confirm'))) $event.preventDefault()"
                                            data-confirm="{{ __('orders.delete_image_confirm') }}">
                                            @csrf
                                            @method('DELETE')
                                            @if (($itemFile['source'] ?? '') === 'item')<input type="hidden" name="item_id" value="{{ $itemFile['id'] }}">
                                            @else<input type="hidden" name="file_id" value="{{ $itemFile['id'] }}">@endif
                                            <button type="submit" class="w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center text-xs leading-none hover:bg-red-600" title="{{ __('orders.delete_image') }}">✕</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 align-middle">
                    @if ($canAddFiles)
                        <div x-data="{ open: false, uploading: false }" class="flex flex-col items-center gap-1">
                            <span class="text-xs text-gray-500">{{ $itemFileCount }}/{{ $maxFilesPerItemAfterSubmit }}</span>
                            @if ($slotsLeft > 0)
                                <button type="button" @click="open = !open" class="text-xs text-primary-600 hover:text-primary-700 font-medium">+ {{ __('orders.add_files') }}</button>
                                <form x-show="open" x-collapse
                                    @submit.prevent="
                                        const form = $el; const m = window.orderShowToastMessages || {};
                                        const filesInput = form.querySelector('input[type=file]');
                                        if (!filesInput || !filesInput.files || filesInput.files.length === 0) { $dispatch('order-toast', { type: 'error', message: m.required || 'Please select at least one file.' }); return; }
                                        uploading = true;
                                        fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                                        .then(r => r.json().catch(() => ({})))
                                        .then(data => { uploading = false; if (data.success) { $dispatch('order-toast', { type: 'success', message: data.message || m.uploaded }); form.reset(); open = false; window.location.reload(); } else { $dispatch('order-toast', { type: 'error', message: data.message || m.failed }); } })
                                        .catch(() => { uploading = false; $dispatch('order-toast', { type: 'error', message: m.failed }); });
                                    "
                                    action="{{ route('orders.items.files.store', [$order, $item->id]) }}" method="POST" enctype="multipart/form-data"
                                    class="mt-1 flex flex-col items-center gap-1">
                                    @csrf
                                    <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.tif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.heic" class="text-xs w-28">
                                    <button type="submit" :disabled="uploading" class="text-xs bg-primary-500 hover:bg-primary-600 text-white px-2 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-text="uploading ? (window.orderShowToastMessages?.uploading || 'Uploading…') : (window.orderShowToastMessages?.upload || 'Upload')"></span>
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">{{ __('orders.max_reached') }}</span>
                            @endif
                        </div>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $isStaff ? 11 : 10 }}" class="px-4 py-8 text-center text-sm text-gray-400">{{ __('orders.no_items') }}</td>
            </tr>
        @endforelse
    </tbody>
</table>
