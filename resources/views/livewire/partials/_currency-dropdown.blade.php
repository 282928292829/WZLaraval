{{-- Modern currency dropdown — width fits longest currency, never exceeds it. Expects: idx, item, currencyList, onCurrencyChange, calcTotals, saveDraft, openCurrencyRow --}}
<div class="relative inline-block">
    <button type="button"
            @click="openCurrencyRow = openCurrencyRow === idx ? null : idx"
            class="order-form-input w-auto min-w-[6rem] max-w-[14rem] h-10 px-3 py-2 rounded-lg text-sm text-start bg-white border border-primary-100 hover:border-primary-200 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 inline-flex items-center justify-between gap-1.5"
            :title="currencyList[item.currency]?.label || ''">
        <span class="truncate" x-text="currencyList[item.currency]?.label || item.currency || ''"></span>
        <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="{ 'rotate-180': openCurrencyRow === idx }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openCurrencyRow === idx" x-collapse x-cloak
         @click.outside="openCurrencyRow = null"
         class="absolute top-full mt-1 z-30 w-max min-w-full max-w-[14rem] bg-white rounded-lg shadow-lg border border-slate-200 py-1 max-h-56 overflow-y-auto scrollbar-hide {{ app()->getLocale() === 'ar' ? 'right-0 left-auto' : 'left-0 right-auto' }}">
        <template x-for="(cur, code) in currencyList" :key="code">
            <button type="button" @click="item.currency = code; onCurrencyChange(idx); calcTotals(); saveDraft(); openCurrencyRow = null"
                    class="w-full px-3 py-2 text-start text-sm hover:bg-primary-50 focus:bg-primary-50 focus:outline-none transition-colors whitespace-nowrap"
                    :class="{ 'bg-primary-50 text-primary-700 font-medium': item.currency === code }"
                    :title="cur.symbol ? (cur.label + ' (' + cur.symbol + ')') : cur.label">
                <span x-text="cur.label || code"></span>
            </button>
        </template>
    </div>
</div>
