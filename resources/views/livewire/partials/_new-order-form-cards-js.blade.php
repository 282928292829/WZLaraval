function newOrderFormCards(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes) {
    return {
        ...newOrderForm(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes),

        // Cards-specific state
        showDraftPrompt: false,
        pendingDraftItems: null,
        pendingDraftNotes: '',

        // Override init() — show draft prompt instead of silently restoring
        init() {
            this.checkTipsHidden();

            if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
                // Pre-filled from duplicate/edit — load directly, no prompt
                this.items = initialItems.map((d, i) => ({
                    url: d.url || '', qty: (d.qty || '1').toString(), color: d.color || '', size: d.size || '',
                    price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: i === 0, _focused: false, _showOptional: false,
                    _files: []
                }));
                this.orderNotes = initialOrderNotes || '';
            } else {
                // Check for saved draft — show prompt instead of silently loading
                const draft = this.peekDraft();
                if (draft) {
                    this.pendingDraftItems = draft.items;
                    this.pendingDraftNotes = draft.notes;
                    this.showDraftPrompt = true;
                    // Start with 1 empty card while prompt is visible
                    this.items = [this.emptyItem()];
                } else {
                    // No draft — start with 1 empty card (cards layout is mobile-first)
                    this.items = [this.emptyItem()];
                }
            }

            this.calcTotals();

            window.addEventListener('beforeunload', (e) => {
                if (this.submitting || !this.hasUnsavedData()) return;
                @if (config('app.env') === 'local')
                return;
                @endif
                e.preventDefault();
            });
        },

        // Read draft from localStorage without loading it — only return if it has real content
        peekDraft() {
            try {
                let raw = localStorage.getItem('wz_order_form_draft');
                let notes = localStorage.getItem('wz_order_form_notes');
                // Legacy key migration
                if (!raw && localStorage.getItem('wz_opus46_draft')) {
                    raw = localStorage.getItem('wz_opus46_draft');
                    notes = localStorage.getItem('wz_opus46_notes');
                    localStorage.removeItem('wz_opus46_draft');
                    localStorage.removeItem('wz_opus46_notes');
                    if (raw) localStorage.setItem('wz_order_form_draft', raw);
                    if (notes) localStorage.setItem('wz_order_form_notes', notes);
                }
                if (!raw) return null;
                const data = JSON.parse(raw);
                if (!Array.isArray(data) || data.length === 0) return null;
                // Only prompt if at least one item has meaningful content
                const hasMeaningfulContent = data.some(d =>
                    (d.url || '').trim() || (d.color || '').trim() ||
                    (d.size || '').trim() || (d.notes || '').trim() ||
                    (parseFloat(d.price) > 0)
                );
                if (!hasMeaningfulContent) return null;
                return { items: data, notes: notes || '' };
            } catch {
                return null;
            }
        },

        // Restore draft items
        restoreDraft() {
            if (!this.pendingDraftItems) {
                this.showDraftPrompt = false;
                return;
            }
            this.items = this.pendingDraftItems.map((d) => ({
                _id: Math.random().toString(36).slice(2),
                url: d.url || '', qty: d.qty || '1', color: d.color || '',
                size: d.size || '', price: d.price || '',
                currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                _expanded: false, _focused: false, _showOptional: false,
                _files: []
            }));
            this.orderNotes = this.pendingDraftNotes || '';
            this.pendingDraftItems = null;
            this.pendingDraftNotes = '';
            this.showDraftPrompt = false;
            this.calcTotals();
            this.showNotify('success', @js(__('order_form.draft_restored')));
        },

        // Discard draft and start fresh
        discardDraft() {
            this.clearDraft();
            this.pendingDraftItems = null;
            this.pendingDraftNotes = '';
            this.showDraftPrompt = false;
            this.items = [this.emptyItem()];
            this.orderNotes = '';
            this.calcTotals();
            this.$nextTick(() => this.saveDraft());
        },

        // Override removeItem() — undo toast + auto-add empty card when last deleted
        removeItem(idx) {
            if (this.items.length === 0) return;
            const removed = { ...this.items[idx] };
            this.items.splice(idx, 1);
            this.calcTotals();
            this.saveDraft();
            if (this.items.length === 0) {
                this.items = [this.emptyItem()];
                this.calcTotals();
            }
            // Undo toast
            const c = this.$refs.toasts;
            if (c) {
                const t = document.createElement('div');
                t.className = 'toast success';
                const label = @js(__('order_form.item_removed'));
                const undoLabel = @js(__('order_form.undo'));
                t.innerHTML = `<span style="flex:1">${label}</span><button type="button" class="toast-close" style="font-weight:600;color:var(--color-primary-600,#7c3aed)">${undoLabel}</button>`;
                let undone = false;
                const undo = t.querySelector('.toast-close');
                const closeToast = () => {
                    t.style.animation = 'toastOut 0.4s ease forwards';
                    setTimeout(() => t.remove(), 400);
                };
                undo.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (!undone) {
                        undone = true;
                        if (this.items.length === 1 && !this.items[0].url && !this.items[0].color) {
                            this.items = [];
                        }
                        this.items.splice(idx, 0, { ...removed, _expanded: true });
                        this.calcTotals();
                        this.saveDraft();
                    }
                    closeToast();
                });
                c.appendChild(t);
                setTimeout(() => { if (t.parentElement && !undone) closeToast(); }, 4000);
            }
        },

        // Override addProduct() — always collapse previous card on ALL screen sizes
        addProduct() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', @js(__('order_form.max_products', ['max' => $maxProducts ?? 30])));
                return;
            }

            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;

            // Collapse the currently expanded card (all screen sizes — cards layout only)
            const openIdx = this.items.findIndex(i => i._expanded);
            if (openIdx !== -1) {
                this.items[openIdx]._expanded = false;
            }

            this.items.push(this.emptyItem(lastCur));
            this.saveDraft();

            // Scroll new card into view
            this.$nextTick(() => {
                setTimeout(() => {
                    const cards = document.querySelectorAll('#items-container > div');
                    const last = cards[cards.length - 1];
                    if (last) {
                        last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }, 150);
            });
        },

        // Cards-specific header: label (normal) + domain (muted when collapsed)
        itemHeaderLabel(idx) {
            const item = this.items[idx];
            if (!item) return '';
            return '{{ __('order_form.product_num') }} ' + (idx + 1);
        },
        itemHeaderDomain(idx) {
            const item = this.items[idx];
            if (!item || item._expanded) return '';
            const site = this.getItemSite(item);
            const noUrl = @js(__('order_form.no_url'));
            return site ? ' ' + site : ' ' + noUrl;
        },

        // Override addFiveTestItems — collapse all, only last expanded
        addFiveTestItems() {
            const urls = [
                'https://www.amazon.com/dp/B0BSHF7LLL',
                'https://www.ebay.com/itm/' + Math.floor(100000000 + Math.random() * 900000000),
                'https://www.walmart.com/ip/' + Math.floor(100000 + Math.random() * 900000),
                'https://www.target.com/p/product-' + Math.floor(100 + Math.random() * 900),
                'https://www.aliexpress.com/item/' + Math.floor(1000000000 + Math.random() * 9000000000) + '.html',
            ];
            const sizes = this.testOptions?.sizes || ['S', 'M', 'L', 'XL', 'US 8'];
            const currencies = ['USD', 'EUR', 'GBP'];
            const colors = this.testOptions?.colors || ['White', 'Black', 'Navy', 'Red', 'Beige'];
            const notes = this.testOptions?.notes || ['Same as picture', 'Please send photo', 'Exact match', 'As shown', 'Confirm color'];
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;
            const isEmpty = (item) => !(item.url || '').trim() && !(item.color || '').trim() && !(item.size || '').trim() && !parseFloat(item.price) && !(item.notes || '').trim();

            // Collapse all existing cards first
            this.items.forEach(i => { i._expanded = false; });

            for (let i = 0; i < 5; i++) {
                const cur = currencies[i % currencies.length] || lastCur;
                const testData = {
                    url: urls[i], qty: String(Math.floor(Math.random() * 2) + 1),
                    color: colors[i % colors.length], size: sizes[Math.floor(Math.random() * sizes.length)],
                    price: String((Math.random() * 80 + 15).toFixed(2)), currency: cur,
                    notes: notes[i % notes.length],
                    _expanded: false, _focused: false, _showOptional: false, _files: []
                };
                const emptyIdx = this.items.findIndex(isEmpty);
                if (emptyIdx !== -1) {
                    Object.assign(this.items[emptyIdx], testData);
                } else if (this.items.length < this.maxProducts) {
                    this.items.push(testData);
                } else { break; }
            }

            // Expand only the last card
            if (this.items.length > 0) {
                this.items[this.items.length - 1]._expanded = true;
            }

            this.calcTotals();
            this.saveDraft();
            this.showNotify('success', '{{ __('order.dev_5_items_added') }}');

            this.$nextTick(() => {
                setTimeout(() => {
                    const cards = document.querySelectorAll('#items-container > div');
                    const last = cards[cards.length - 1];
                    if (last) last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 150);
            });
        },

        // Override resetAll — cards layout starts with 1 item (not 5)
        resetAll() {
            if (!confirm('{{ __('order_form.reset_confirm') }}')) return;
            this.items = [this.emptyItem()];
            this.orderNotes = '';
            this.clearDraft();
            this.calcTotals();
            this.showNotify('success', '{{ __('order_form.cleared') }}');
        },
    };
}
