{{--
  Thumbnails for files on a cart line item. $itemIndex = Livewire item index; $previews = getItemFilePreviews($itemIndex).
--}}
@if (count($previews ?? []) > 0)
<div class="flex flex-wrap items-center gap-2 mt-2">
    @foreach ($previews as $fp)
    <div class="relative w-11 h-11 shrink-0 rounded-lg overflow-hidden border border-slate-200">
        <div class="absolute inset-0 {{ ($fp['url'] ?? null) ? 'cursor-pointer' : '' }}"
             @if ($fp['url'] ?? null) data-zoom-src="{{ $fp['url'] }}" @click="$dispatch('zoom-image', $event.currentTarget.dataset.zoomSrc)" @endif>
            @if ($fp['url'] ?? null)
            <img src="{{ $fp['url'] }}" class="w-full h-full object-cover block pointer-events-none" alt="">
            @elseif (($fp['type'] ?? '') === 'img')
            <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[10px]">...</div>
            @elseif (($fp['type'] ?? '') === 'pdf')
            <div class="w-full h-full flex items-center justify-center bg-red-100 text-red-500 text-[10px] font-bold">PDF</div>
            @else
            <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[10px]">?</div>
            @endif
        </div>
        <button type="button"
                wire:click.stop="removeItemFile({{ $itemIndex }}, {{ $loop->index }})"
                class="absolute top-0 end-0 w-4 h-4 bg-red-500 text-white border-none rounded-full text-xs font-bold cursor-pointer flex items-center justify-center z-10 leading-none hover:bg-red-600 shadow-sm"
                aria-label="{{ __('order_form.remove') }}">×</button>
    </div>
    @endforeach
</div>
@endif
