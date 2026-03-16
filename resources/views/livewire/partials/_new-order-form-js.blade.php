function newOrderForm(rates, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes, maxImagesPerItem, maxImagesPerOrder, msgMaxPerItem, msgMaxOrder, testOptions, allowedMimeTypes, maxFileSizeBytes) {
    maxImagesPerItem = maxImagesPerItem || 3;
    maxImagesPerOrder = maxImagesPerOrder || 10;
    msgMaxPerItem = msgMaxPerItem || @js(__('order_form.max_per_item_reached', ['max' => $maxImagesPerItem ?? 3]));
    msgMaxOrder = msgMaxOrder || @js(__('order_form.max_files', ['max' => $maxImagesPerOrder ?? 10]));
    testOptions = testOptions || { colors: [], sizes: [], notes: [] };
    const cs = commissionSettings || { threshold: 500, below_type: 'flat', below_value: 50, above_type: 'percent', above_value: 8 };
    function calcCommission(subtotalSar) {
        if (subtotalSar <= 0) return 0;
        const isAbove = subtotalSar >= (cs.threshold || 500);
        if (isAbove) {
            return cs.above_type === 'percent' ? subtotalSar * (cs.above_value / 100) : cs.above_value;
        }
        return cs.below_type === 'percent' ? subtotalSar * (cs.below_value / 100) : cs.below_value;
    }
    return {
        items: [],
        orderNotes: '',
        rates,
        currencyList,
        maxProducts,
        defaultCurrency,
        isLoggedIn,
        maxImagesPerItem,
        maxImagesPerOrder,
        msgMaxPerItem,
        msgMaxOrder,
        maxCharsMsg: @js(__('order_form.max_2000_chars')),
        testOptions,
        commissionSettings: cs,
        calcCommission,
        allowedMimeTypes: Array.isArray(allowedMimeTypes) && allowedMimeTypes.length > 0 ? allowedMimeTypes : ['image/jpeg','image/png','image/gif','image/webp','image/bmp','image/tiff','image/heic','application/pdf','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        maxFileSizeBytes: maxFileSizeBytes > 0 ? maxFileSizeBytes : (2 * 1024 * 1024),
        tipsOpen: false,
        tipsHidden: false,
        zoomedImage: null,
        totalSar: 0,
        filledCount: 0,
        submitting: false,
        openCurrencyRow: null,
        pasteFeedbackIdx: null,
        openFeedbackIdx: null,
        pasteLabel: @js(__('order_form.paste')),
        pastedLabel: @js(__('order_form.pasted')),
        openLabel: @js(__('order_form.open')),
        openedLabel: @js(__('order_form.opened')),
        pasteFailedMsg: @js(__('order_form.paste_failed')),
        doPasteForItem(idx, ev) {
            if (!navigator.clipboard?.readText) {
                this.focusUrlAndNotify(idx, ev);
                return;
            }
            navigator.clipboard.readText().then(t => {
                this.items[idx].url = t;
                this.calcTotals();
                this.saveDraft();
                const block = ev?.target?.closest?.('.order-cell-url');
                if (block) {
                    const ta = block.querySelector('textarea');
                    if (ta) ta.dispatchEvent(new Event('input', { bubbles: true }));
                }
                this.pasteFeedbackIdx = idx;
                setTimeout(() => { this.pasteFeedbackIdx = null; }, 1500);
            }).catch(() => {
                this.focusUrlAndNotify(idx, ev);
            });
        },
        focusUrlAndNotify(idx, ev) {
            const block = ev?.target?.closest?.('.order-cell-url');
            const ta = block?.querySelector('textarea');
            if (ta) ta.focus();
            this.showNotify('error', this.pasteFailedMsg);
        },
        doOpenForItem(idx) {
            const v = (this.items[idx]?.url || '').trim();
            if (!v) return;
            const url = v.startsWith('http') ? v : 'https://' + v;
            window.open(url, '_blank');
            this.openFeedbackIdx = idx;
            setTimeout(() => { this.openFeedbackIdx = null; }, 1500);
        },
        init() {
            this.checkTipsHidden();
            if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
                const isMobile = window.innerWidth < 1024;
                this.items = initialItems.map((d, i) => ({
                    url: d.url || '', qty: (d.qty || '1').toString(), color: d.color || '', size: d.size || '',
                    price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: isMobile ? (i === 0) : true, _focused: false, _showOptional: false,
                    _files: []
                }));
                this.orderNotes = initialOrderNotes || '';
            } else if (!this.loadDraft()) {
                const count = window.innerWidth >= 1024 ? 5 : 1;
                for (let i = 0; i < count; i++) this.items.push(this.emptyItem());
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

        hasUnsavedData() {
            return this.items.some(i =>
                (i.url || '').trim() ||
                (i.color || '').trim() ||
                (i.size || '').trim() ||
                (i.notes || '').trim() ||
                (parseFloat(i.price) > 0)
            ) || (this.orderNotes || '').trim();
        },

        emptyItem(cur) {
            return {
                _id: Math.random().toString(36).slice(2),
                url: '', qty: '1', color: '', size: '', price: '',
                currency: cur || this.defaultCurrency, notes: '',
                _expanded: true, _focused: false, _showOptional: false,
                _files: []
            };
        },

        totalFileCount() {
            return this.items.reduce((sum, i) => sum + (i._files ? i._files.length : 0), 0);
        },

        addProduct() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', @js(__('order_form.max_products', ['max' => $maxProducts ?? 30])));
                return;
            }
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;

            if (window.innerWidth < 1024 && this.$refs?.tableScrollContainer === undefined) {
                const open = this.items.findIndex(i => i._expanded);
                if (open !== -1) {
                    this.items[open]._expanded = false;
                }
            } else if (window.innerWidth >= 1024 && this.$refs?.tableScrollContainer) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const el = this.$refs.tableScrollContainer;
                        if (el) el.scrollTop = el.scrollHeight - el.clientHeight;
                    }, 150);
                });
            } else {
                const cards = document.querySelectorAll('#items-container > div');
                if (cards.length > 0) {
                    this.$nextTick(() => {
                        setTimeout(() => {
                            const last = cards[cards.length - 1];
                            if (last) last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 150);
                    });
                }
            }

            this.items.push(this.emptyItem(lastCur));
            this.saveDraft();
        },

        removeItem(idx) {
            this.$wire.removeItem(idx);
            this.items.splice(idx, 1);
            this.calcTotals();
            this.saveDraft();
        },

        toggleItem(idx) {
            this.items[idx]._expanded = !this.items[idx]._expanded;
        },

        getItemSite(item) {
            const url = (item.url || '').trim();
            if (!url) return '';
            let name = '';
            try {
                const host = new URL(url.startsWith('http') ? url : 'https://' + url).hostname.replace('www.', '');
                name = (host.split('.')[0] || host).replace(/^./, c => c.toUpperCase());
            } catch { name = url.substring(0, 6); }
            name = name.length > 10 ? name.substring(0, 10) : name;
            return '(' + name + ')';
        },

        itemSummary(idx, expanded) {
            const item = this.items[idx];
            const num = idx + 1;
            if (expanded) return '{{ __('order_form.product_num') }} ' + num;
            const short = this.getItemSite(item);
            return '{{ __('order_form.product_num') }} ' + num + (short ? '  ·  ' + short : '');
        },

        onCurrencyChange(idx) {
            if (this.items[idx].currency === 'OTHER') {
                this.showNotify('success', '{{ __('order_form.other_currency_note') }}');
            }
        },

        convertArabicNums(e) {
            const ar = '٠١٢٣٤٥٦٧٨٩';
            let v = e.target.value;
            let changed = false;
            v = v.replace(/[٠-٩]/g, (d) => {
                const idx = ar.indexOf(d);
                if (idx >= 0) { changed = true; return String(idx); }
                return d;
            });
            if (changed) e.target.value = v;
        },

        calcTotals() {
            let subtotal = 0;
            let filled = 0;
            this.items.forEach(item => {
                if (item.url.trim()) filled++;
                const q = Math.max(1, parseFloat(item.qty) || 1);
                const p = parseFloat(item.price) || 0;
                const r = this.rates[item.currency] || 0;
                if (p > 0 && r > 0) subtotal += (p * q * r);
            });
            const commission = this.calcCommission ? this.calcCommission(subtotal) : 0;
            this.totalSar = Math.round(subtotal + commission);
            this.filledCount = filled;
        },

        productCountText() {
            return '{{ __('order_form.products_count') }}: ' + this.filledCount;
        },

        totalText() {
            return '{{ __('order_form.products_value') }}: ' + this.totalSar.toLocaleString('en-US') + ' {{ __('SAR') }}';
        },

        saveDraft() {
            const data = this.items.map(i => ({
                url: i.url, qty: i.qty, color: i.color, size: i.size,
                price: i.price, currency: i.currency, notes: i.notes
            }));
            try {
                localStorage.setItem('wz_order_form_draft', JSON.stringify(data));
                localStorage.setItem('wz_order_form_notes', this.orderNotes);
            } catch {}
        },

        loadDraft() {
            try {
                let raw = localStorage.getItem('wz_order_form_draft');
                let notes = localStorage.getItem('wz_order_form_notes');
                if (!raw && localStorage.getItem('wz_opus46_draft')) {
                    raw = localStorage.getItem('wz_opus46_draft');
                    notes = localStorage.getItem('wz_opus46_notes');
                    localStorage.removeItem('wz_opus46_draft');
                    localStorage.removeItem('wz_opus46_notes');
                    if (raw) localStorage.setItem('wz_order_form_draft', raw);
                    if (notes) localStorage.setItem('wz_order_form_notes', notes);
                }
                if (notes) this.orderNotes = notes;
                if (!raw) return false;
                const data = JSON.parse(raw);
                if (!Array.isArray(data) || data.length === 0) return false;
                this.items = data.map(d => ({
                    url: d.url || '', qty: d.qty || '1', color: d.color || '',
                    size: d.size || '', price: d.price || '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: false, _focused: false, _showOptional: false,
                    _files: []
                }));
                if (this.items.length > 0) this.items[0]._expanded = true;
                return true;
            } catch { return false; }
        },

        clearDraft() {
            try {
                localStorage.removeItem('wz_order_form_draft');
                localStorage.removeItem('wz_order_form_notes');
                localStorage.removeItem('wz_opus46_draft');
                localStorage.removeItem('wz_opus46_notes');
            } catch {}
        },

        handleFileSelect(e, idx) {
            const rawFiles = Array.from(e.target.files || []);
            e.target.value = '';
            if (!rawFiles.length) return;

            if (!this.items[idx]._files) this.items[idx]._files = [];
            const existing = this.items[idx]._files;

            // Capacity checks
            const canAddItem  = this.maxImagesPerItem - existing.length;
            const canAddOrder = this.maxImagesPerOrder - this.totalFileCount();
            const canAdd      = Math.min(canAddItem, canAddOrder);

            if (canAdd <= 0) {
                this.showNotify('error', existing.length >= this.maxImagesPerItem ? this.msgMaxPerItem : this.msgMaxOrder);
                return;
            }

            // Validate each file
            const allowed  = this.allowedMimeTypes || [];
            const maxBytes = this.maxFileSizeBytes || (2 * 1024 * 1024);
            const valid    = [];
            let   skipped  = 0;

            for (const file of rawFiles) {
                if (valid.length >= canAdd) { skipped++; continue; }
                if (allowed.length && !allowed.includes(file.type)) { skipped++; continue; }
                if (file.size > maxBytes) { skipped++; continue; }
                valid.push(file);
            }

            if (skipped > 0) {
                this.showNotify('error', '{{ __('order_form.files_skipped_invalid') }}'.replace(':n', skipped));
            }
            if (!valid.length) return;

            // Classify + upload
            const classify = (file) => {
                if (file.type === 'application/pdf') return 'pdf';
                if (file.type.includes('excel') || file.type.includes('spreadsheetml') || file.type === 'text/csv') return 'xls';
                if (file.type.includes('word') || file.type === 'application/msword') return 'doc';
                return 'img';
            };

            let uploadedCount = 0;
            const total = valid.length;

            valid.forEach((file, i) => {
                const fileType = classify(file);
                const entry = { file, preview: null, fileType, fileName: file.name, uploadProgress: 0 };
                const entryIdx = this.items[idx]._files.push(entry) - 1;
                const wireKey  = 'itemFiles.' + idx + '.' + (existing.length - total + i);

                // Generate image preview immediately
                if (fileType === 'img') {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        if (this.items[idx]._files[entryIdx]) {
                            this.items[idx]._files[entryIdx].preview = ev.target.result;
                        }
                    };
                    reader.readAsDataURL(file);
                }

                this.$wire.upload(
                    wireKey,
                    file,
                    () => {
                        // Success
                        if (this.items[idx]._files[entryIdx]) {
                            this.items[idx]._files[entryIdx].uploadProgress = null;
                        }
                        uploadedCount++;
                        if (uploadedCount === total) {
                            this.showNotify('success',
                                total > 1
                                    ? '{{ __('order_form.files_attached') }}'.replace(':n', total)
                                    : '{{ __('order_form.file_attached') }}'
                            );
                        }
                    },
                    () => {
                        // Error
                        if (this.items[idx]._files[entryIdx]) {
                            this.items[idx]._files[entryIdx].uploadProgress = null;
                        }
                        this.items[idx]._files = this.items[idx]._files.filter((_, i) => i !== entryIdx);
                        this.showNotify('error', '{{ __('order_form.upload_failed') }}');
                    },
                    (event) => {
                        // Progress
                        const pct = event.detail.progress ?? 0;
                        if (this.items[idx]._files[entryIdx]) {
                            this.items[idx]._files[entryIdx].uploadProgress = pct >= 100 ? null : pct;
                        }
                    }
                );
            });
        },

        removeFile(itemIdx, fileIdx) {
            const files = this.items[itemIdx]?._files || [];
            if (fileIdx < 0 || fileIdx >= files.length) return;
            this.items[itemIdx]._files.splice(fileIdx, 1);
            this.$wire.removeItemFile(itemIdx, fileIdx);
        },

        closeZoom() {
            if (this.zoomedImage?.startsWith?.('blob:')) {
                try { URL.revokeObjectURL(this.zoomedImage); } catch (_) {}
            }
            this.zoomedImage = null;
        },

        openFileOrZoom(f) {
            if (!f) return;
            if (f.fileType === 'img') {
                const src = f.preview || (f.file ? URL.createObjectURL(f.file) : null);
                if (src) this.$dispatch('zoom-image', src);
            } else if (f.file) {
                window.open(URL.createObjectURL(f.file), '_blank');
            }
        },

        async submitOrder() {
            if (this.submitting) return;
            const cleanItems = this.items.map(i => ({
                url: i.url, qty: i.qty, color: i.color, size: i.size,
                price: i.price, currency: i.currency, notes: i.notes
            }));
            this.submitting = true;
            try {
                await this.$wire.set('items', cleanItems);
                await this.$wire.set('orderNotes', this.orderNotes);
                await this.$wire.submitOrder();
                if (this.$wire.showLoginModal) {
                    this.submitting = false;
                    return;
                }
                this.clearDraft();
            } catch (_) {}
            finally { this.submitting = false; }
        },

        checkTipsHidden() {
            try {
                let until = localStorage.getItem('wz_order_form_tips_until');
                if (!until) until = localStorage.getItem('wz_opus46_tips_until');
                if (until && Date.now() < parseInt(until)) this.tipsHidden = true;
                else { localStorage.removeItem('wz_order_form_tips_until'); localStorage.removeItem('wz_opus46_tips_until'); }
            } catch {}
        },

        hideTips30Days() {
            try {
                localStorage.setItem('wz_order_form_tips_until', (Date.now() + 30 * 24 * 60 * 60 * 1000).toString());
            } catch {}
            this.tipsHidden = true;
            this.showNotify('success', '{{ __('order_form.tips_hidden') }}');
        },

        showNotify(type, msg, duration) {
            const c = this.$refs.toasts;
            if (!c) return;
            const t = document.createElement('div');
            t.className = `toast ${type}`;
            const icon = type === 'error'
                ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:#ef4444;flex-shrink:0"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>'
                : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:#10b981;flex-shrink:0"><path d="M20 6L9 17l-5-5"/></svg>';
            const dur = duration ?? (type === 'error' ? 4000 : 700);
            const closeLabel = '{{ __("Close") }}';
            t.innerHTML = `${icon}<span style="flex:1">${msg}</span><button type="button" class="toast-close" aria-label="${closeLabel}">×</button>`;
            c.appendChild(t);
            const closeToast = () => {
                t.style.animation = 'toastOut 0.4s ease forwards';
                setTimeout(() => t.remove(), 400);
            };
            t.querySelector('.toast-close').addEventListener('click', (e) => { e.stopPropagation(); closeToast(); });
            t.addEventListener('click', closeToast);
            setTimeout(() => { if (t.parentElement) closeToast(); }, dur);
        }
    };
}
