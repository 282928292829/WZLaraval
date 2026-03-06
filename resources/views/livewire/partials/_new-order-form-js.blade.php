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
            name = (name.length > 6 ? name.substring(0, 6) : name) + '..';
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
            if (!rawFiles.length) return;

            let files = this.items[idx]._files || [];
            if (files.length >= this.maxImagesPerItem) {
                this.showNotify('error', this.msgMaxPerItem);
                e.target.value = '';
                return;
            }
            if (this.totalFileCount() >= this.maxImagesPerOrder) {
                this.showNotify('error', this.msgMaxOrder);
                e.target.value = '';
                return;
            }

            const allowed = this.allowedMimeTypes || [];
            const maxSize = this.maxFileSizeBytes || (2 * 1024 * 1024);
            const toAdd = [];
            let skippedInvalid = 0;
            let canAddItem = this.maxImagesPerItem - files.length;
            let canAddOrder = this.maxImagesPerOrder - this.totalFileCount();

            if (rawFiles.length > canAddItem || rawFiles.length > canAddOrder) {
                this.showNotify('error', '{{ __('order_form.too_many_selected') }}'.replace(':max', this.maxImagesPerItem).replace(':avail', Math.min(canAddItem, canAddOrder)));
                e.target.value = '';
                return;
            }

            for (const file of rawFiles) {
                if (!allowed.includes(file.type)) { skippedInvalid++; continue; }
                if (file.size > maxSize) { skippedInvalid++; continue; }
                toAdd.push(file);
            }

            if (skippedInvalid > 0) {
                this.showNotify('error', skippedInvalid === 1 ? '{{ __('order_form.invalid_type') }}' : '{{ __('order_form.files_skipped_invalid') }}'.replace(':n', skippedInvalid));
            }

            if (!toAdd.length) { e.target.value = ''; return; }

            if (!this.items[idx]._files) this.items[idx]._files = [];
            const totalAdding = toAdd.length;
            let completed = 0;
            const uploadOne = (file, fileIdx) => {
                let fileType = 'img';
                if (file.type === 'application/pdf') fileType = 'pdf';
                else if (file.type.includes('excel') || file.type.includes('spreadsheetml') || file.type === 'text/csv') fileType = 'xls';
                else if (file.type.includes('word') || file.type === 'application/msword') fileType = 'doc';

                const entry = { file, preview: null, fileType, fileName: file.name, uploadProgress: 0 };
                this.items[idx]._files.push(entry);
                if (fileType === 'img') {
                    const entryIdx = this.items[idx]._files.length - 1;
                    const reader = new FileReader();
                    reader.onload = (ev) => { this.items[idx]._files[entryIdx].preview = ev.target.result; };
                    reader.readAsDataURL(file);
                }

                this.$wire.upload('itemFiles.' + idx + '.' + fileIdx, file,
                    () => {
                        entry.uploadProgress = null;
                        completed++;
                        if (completed === totalAdding) {
                            this.showNotify('success', totalAdding > 1 ? '{{ __('order_form.files_attached') }}'.replace(':n', String(totalAdding)) : '{{ __('order_form.file_attached') }}');
                        }
                    },
                    () => {
                        entry.uploadProgress = null;
                        this.items[idx]._files = this.items[idx]._files.filter(f => f !== entry);
                        this.showNotify('error', '{{ __('order_form.upload_failed') }}');
                    },
                    (event) => {
                        entry.uploadProgress = event.detail.progress;
                        if (event.detail.progress >= 100) entry.uploadProgress = null;
                    }
                );
            };

            let fileIdx = files.length;
            toAdd.forEach((file) => { uploadOne(file, fileIdx); fileIdx++; });
            e.target.value = '';
        },

        removeFile(idx, fileIdx) {
            const files = this.items[idx]._files || [];
            if (fileIdx < 0 || fileIdx >= files.length) return;
            this.items[idx]._files.splice(fileIdx, 1);
            this.$wire.removeItemFile(idx, fileIdx);
        },

        closeZoom() {
            if (this.zoomedImage && this.zoomedImage.startsWith('blob:')) {
                try { URL.revokeObjectURL(this.zoomedImage); } catch (_) {}
            }
            this.zoomedImage = null;
        },

        openFileOrZoom(f) {
            if (f.fileType === 'img') {
                const src = f.preview || (f.file ? URL.createObjectURL(f.file) : null);
                if (src) this.$dispatch('zoom-image', src);
            } else if ((f.fileType === 'pdf' || f.fileType === 'xls' || f.fileType === 'doc') && f.file) {
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
