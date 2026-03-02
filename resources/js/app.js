import './bootstrap';

import Collapse from '@alpinejs/collapse';

// Livewire 3 bundles and initialises Alpine automatically.
// We only need to register plugins BEFORE Alpine starts.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Collapse);

    window.Alpine.store('commentExpanded', { id: null });

    window.Alpine.data('commentCard', (config = {}) => ({
        id: config.id,
        body: config.body ?? '',
        orderId: config.orderId,
        orderNumber: config.orderNumber ?? '',
        author: config.author ?? '',
        createdAt: config.createdAt ?? '',
        isoCreatedAt: config.isoCreatedAt ?? '',
        isInternal: config.isInternal ?? false,
        trashed: config.trashed ?? false,
        canEdit: config.canEdit ?? false,
        updateUrl: config.updateUrl ?? '',
        bodyRequired: config.bodyRequired ?? 'Body is required.',
        editSuccess: config.editSuccess ?? 'Comment updated',
        editError: config.editError ?? 'Failed to update comment',
        savingText: config.savingText ?? 'Saving...',
        editing: false,
        editBody: '',
        editErrorMsg: null,
        saving: false,
        files: config.files ?? [],
        attachUrl: config.attachUrl ?? '',
        maxFiles: config.maxFiles ?? 10,
        maxFileSizeMb: config.maxFileSizeMb ?? 10,
        acceptFileTypes: config.acceptFileTypes ?? '.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.tif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.heic',
        attachErrorMsg: null,
        attaching: false,
        attachSuccess: config.attachSuccess ?? 'File attached',
        attachError: config.attachError ?? 'Failed to attach file',
        attachLimitExceeded: config.attachLimitExceeded ?? 'Maximum files per comment.',
        toggle() {
            if (this.$store.commentExpanded.id === this.id) {
                this.$store.commentExpanded.id = null;
                this.editing = false;
                this.editErrorMsg = null;
            } else {
                this.$store.commentExpanded.id = this.id;
            }
        },
        startEdit() {
            this.editBody = this.body;
            this.editErrorMsg = null;
            this.editing = true;
        },
        cancelEdit() {
            this.editing = false;
            this.editErrorMsg = null;
        },
        async saveEdit() {
            if (!this.updateUrl || !String(this.editBody).trim()) {
                this.editErrorMsg = this.bodyRequired;
                return;
            }
            this.saving = true;
            this.editErrorMsg = null;
            try {
                const res = await fetch(this.updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ body: String(this.editBody).trim() }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    this.body = data.body ?? String(this.editBody).trim();
                    this.editing = false;
                    this.showToast(this.editSuccess, 'success');
                } else {
                    this.editErrorMsg = data.message ?? data.errors?.body?.[0] ?? this.editError;
                }
            } catch {
                this.editErrorMsg = this.editError;
            } finally {
                this.saving = false;
            }
        },
        showToast(message, type = 'success') {
            const el = document.createElement('div');
            el.className = `fixed bottom-4 ${document.documentElement.dir === 'rtl' ? 'left' : 'right'}-4 z-[60] px-4 py-3 rounded-lg text-sm font-medium shadow-lg ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
            el.textContent = message;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        },
        triggerAttach() {
            this.$refs.attachInput?.click();
        },
        async onAttachInputChange(e) {
            const input = e.target;
            const fileList = input?.files;
            if (!fileList?.length || !this.attachUrl) return;
            const maxBytes = this.maxFileSizeMb * 1024 * 1024;
            const remaining = Math.max(0, this.maxFiles - this.files.length);
            if (fileList.length > remaining) {
                this.attachErrorMsg = this.attachLimitExceeded.replace(':max', this.maxFiles);
                input.value = '';
                return;
            }
            for (const f of fileList) {
                if (f.size > maxBytes) {
                    this.attachErrorMsg = `${this.attachError} — ${f.name} exceeds ${this.maxFileSizeMb} MB`;
                    input.value = '';
                    return;
                }
            }
            this.attaching = true;
            this.attachErrorMsg = null;
            const formData = new FormData();
            for (const f of fileList) formData.append('files[]', f);
            formData.append('_token', this.csrfToken);
            try {
                const res = await fetch(this.attachUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success && Array.isArray(data.files)) {
                    this.files.push(...data.files);
                    this.showToast(data.message ?? this.attachSuccess, 'success');
                } else {
                    this.attachErrorMsg = data.message ?? this.attachError;
                }
            } catch {
                this.attachErrorMsg = this.attachError;
            } finally {
                this.attaching = false;
                input.value = '';
            }
        },
    }));

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
                if (this.items.length > 0 && !this.items.some(i => i._expanded)) this.items[0]._expanded = true;
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
