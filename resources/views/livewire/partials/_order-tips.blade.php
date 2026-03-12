{{-- Shared tips/hints box — @include in all new-order layouts. Requires Alpine: tipsOpen, tipsHidden, hideTips30Days() --}}
<section class="bg-white rounded-lg shadow-sm border border-primary-100 mb-5 overflow-hidden lg:shrink-0" x-show="!tipsHidden" x-cloak>
    <button type="button"
            class="w-full px-4 py-3 flex justify-between items-center cursor-pointer border-b border-primary-100 bg-transparent text-start"
            :aria-expanded="tipsOpen"
            @click="tipsOpen = !tipsOpen">
        <h2 class="text-sm font-semibold text-slate-800 m-0">{{ __('order_form.tips_title') }}</h2>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary-500 transition-transform duration-150 shrink-0" :class="tipsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>
    <div x-show="tipsOpen" x-collapse class="p-4 text-sm leading-relaxed text-slate-600">
        <ul class="list-none p-0 m-0">
            @for ($i = 1; $i <= 8; $i++)
                <li class="mb-2.5 relative ps-[18px] before:content-['•'] before:absolute before:start-0 before:text-primary-500 before:font-bold">{{ __("order_form.tip_{$i}") }}</li>
            @endfor
        </ul>
        <div class="mt-4 pt-4 border-t border-primary-100">
            <label class="flex items-center gap-3 min-h-[44px] text-sm text-slate-500 cursor-pointer">
                <input type="checkbox" @change="hideTips30Days()" class="w-4 h-4 cursor-pointer rounded">
                <span>{{ __('order_form.tips_dont_show') }}</span>
            </label>
        </div>
    </div>
</section>
