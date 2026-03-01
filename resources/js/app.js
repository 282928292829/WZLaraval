import './bootstrap';

import Collapse from '@alpinejs/collapse';

// Livewire 3 bundles and initialises Alpine automatically.
// We only need to register plugins BEFORE Alpine starts.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Collapse);

    window.Alpine.data('orderDesignForm', (config = {}) => {
        const count = config.initialCount ?? 1;
        const empty = (i) => ({ url: '', qty: '1', color: '', size: '', price: '', currency: 'USD', notes: '', _expanded: i === 0 });
        const defaultCurrencies = { USD: { label: 'USD' }, EUR: { label: 'EUR' }, GBP: { label: 'GBP' }, SAR: { label: 'SAR' } };
        const serverCurrencies = config.currencyList && typeof config.currencyList === 'object' ? config.currencyList : null;
        return {
            items: Array.from({ length: count }, (_, i) => empty(i)),
            orderNotes: '',
            currencyList: (serverCurrencies && Object.keys(serverCurrencies).length > 0) ? serverCurrencies : defaultCurrencies,
            filledCount: 0,
            totalSar: 0,
            init() {
                if (this.items.length === 0) this.addItem();
                this.calcTotals();
            },
            addItem() {
                const last = this.items[this.items.length - 1];
                this.items.forEach((it, i) => { it._expanded = false; });
                this.items.push({ url: '', qty: '1', color: '', size: '', price: '', currency: last?.currency || 'USD', notes: '', _expanded: true });
                this.calcTotals();
                this.$nextTick(() => {
                    if (window.innerWidth >= 1024 && this.$refs.tableScrollContainer) {
                        const scrollToBottom = () => {
                            const el = this.$refs.tableScrollContainer;
                            if (el) el.scrollTop = el.scrollHeight - el.clientHeight;
                        };
                        requestAnimationFrame(scrollToBottom);
                        setTimeout(scrollToBottom, 50);
                    }
                });
            },
            removeItem(idx) {
                this.items.splice(idx, 1);
                if (this.items.length === 0) this.addItem();
                else if (this.items.length > 0 && !this.items.some(i => i._expanded)) this.items[0]._expanded = true;
                this.calcTotals();
            },
            toggleItem(idx) { this.items[idx]._expanded = !this.items[idx]._expanded; },
            itemSummary(idx, expanded) {
                const num = idx + 1;
                if (!expanded) return (document.documentElement.lang === 'ar' ? 'منتج رقم: ' : 'Product #') + num;
                const url = (this.items[idx].url || '').trim();
                if (!url) return (document.documentElement.lang === 'ar' ? 'منتج رقم: ' : 'Product #') + num;
                try {
                    const host = new URL(url.startsWith('http') ? url : 'https://' + url).hostname.replace('www.', '');
                    return (document.documentElement.lang === 'ar' ? 'منتج رقم: ' : 'Product #') + num + ': ' + host;
                } catch { return (document.documentElement.lang === 'ar' ? 'منتج رقم: ' : 'Product #') + num + ': ' + url.substring(0, 30); }
            },
            calcTotals() {
                let filled = 0, total = 0;
                this.items.forEach(item => {
                    if ((item.url || '').trim()) filled++;
                    const q = Math.max(1, parseFloat(item.qty) || 1);
                    const p = parseFloat(item.price) || 0;
                    total += p * q;
                });
                this.filledCount = filled;
                this.totalSar = Math.floor(total);
            },
            productCountText() { return (document.documentElement.lang === 'ar' ? 'منتجات: ' : 'Products: ') + this.filledCount; },
            totalText() { return (document.documentElement.lang === 'ar' ? 'القيمة التقريبية: ' : 'Value (approx): ') + this.totalSar.toLocaleString() + ' SAR'; }
        };
    });

    window.Alpine.data('invoiceForm', (config = {}) => ({
        type: config.type || 'first_payment',
        firstItemsTotal: config.firstItemsTotal ?? 0,
        firstExtras: config.firstExtras || [],
        secondProductValue: config.secondProductValue ?? 0,
        items: (config.items || []).map((it, i) => ({ ...it, id: it.id ?? `item-${i}-${Date.now()}` })),
        generalLines: (config.generalLines || []).map((it, i) => ({ ...it, id: it.id ?? `g-${i}-${Date.now()}` })),
        _nextId: 0,
        init() {
            if (this.items.length === 0) this.addItem();
            if (this.generalLines.length === 0 && this.type === 'general') this.addGeneralLine();
        },
        addFirstExtra() {
            this.firstExtras.push({ id: `fe-${Date.now()}-${Math.random().toString(36).slice(2)}`, label: '', amount: 0 });
        },
        addItem() {
            this.items.push({
                id: `item-${Date.now()}-${Math.random().toString(36).slice(2)}`,
                description: '',
                qty: 1,
                unit_price: 0,
                currency: 'SAR',
            });
        },
        addGeneralLine() {
            this.generalLines.push({
                id: `gl-${Date.now()}-${Math.random().toString(36).slice(2)}`,
                label: '',
                amount: 0,
            });
        },
    }));
});

/**
 * toEnglishDigits — Convert Arabic/Persian digits to English digits.
 * Matches WordPress's toEnglishDigits() function exactly.
 * Handles both Persian digits [۰۱۲۳۴۵۶۷۸۹] and Arabic-Indic digits [٠١٢٣٤٥٦٧٨٩].
 * Exposed globally so Alpine expressions and inline scripts can call it directly.
 */
window.toEnglishDigits = function (str) {
    if (!str) return str;
    
    str = String(str);
    
    // Convert Persian digits [۰۱۲۳۴۵۶۷۸۹]
    str = str.replace(/[۰-۹]/g, function(c) {
        return c.charCodeAt(0) - '۰'.charCodeAt(0);
    });
    
    // Convert Arabic-Indic digits [٠١٢٣٤٥٦٧٨٩]
    str = str.replace(/[٠-٩]/g, function(c) {
        return c.charCodeAt(0) - '٠'.charCodeAt(0);
    });
    
    return str;
};

/**
 * Legacy alias for backward compatibility
 */
window.convert2num = window.toEnglishDigits;

/**
 * Global input handler: automatically converts Arabic/Persian digits to English digits
 * on ALL input fields and textareas site-wide.
 * Uses event delegation so it works with dynamically added Livewire elements.
 * 
 * Applies to: text, number, tel, email, password, search, url, textarea, and all other input types.
 */
function convertDigitsOnInput(e) {
    const el = e.target;
    
    // Only process INPUT and TEXTAREA elements
    if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') return;
    
    // Skip readonly and disabled fields
    if (el.readOnly || el.disabled) return;
    
    // Skip if field has data-no-convert attribute (opt-out)
    if (el.dataset.noConvert === 'true') return;
    
    const converted = window.toEnglishDigits(el.value);
    if (el.value !== converted) {
        const start = el.selectionStart;
        const end   = el.selectionEnd;
        el.value = converted;
        // Preserve cursor position
        try { 
            el.setSelectionRange(start, end); 
        } catch (_) {
            // Fallback for elements that don't support setSelectionRange
            try {
                el.focus();
            } catch (_) {}
        }
    }
}

// Apply conversion on input (real-time as user types)
document.addEventListener('input', convertDigitsOnInput, true);

// Apply conversion on blur (catches any missed conversions)
document.addEventListener('blur', convertDigitsOnInput, true);
