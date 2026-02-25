{{-- /new-order — Production order form --}}

@php
    $isLoggedIn = auth()->check();
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp

{{-- ============================================================ --}}
{{-- SUCCESS SCREEN — shown for the first 3 orders               --}}
{{-- ============================================================ --}}
@if ($showSuccessScreen)
<div
    class="wz-order-page"
    style="min-height:100dvh;min-height:100vh;display:flex;align-items:flex-start;justify-content:center;background:linear-gradient(135deg,#fef3f2 0%,#fef7f5 100%);padding:16px;box-sizing:border-box;padding-top:48px;"
>
    <div class="wz-success-content" style="text-align:center;max-width:420px;width:100%;">
        {{-- Checkmark --}}
        <div class="wz-success-icon" style="width:56px;height:56px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;animation:successScale 0.5s ease-out;">
            <svg style="width:28px;height:28px;color:#fff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="wz-success-title" style="font-size:1.4rem;font-weight:700;color:#1e293b;margin:0 0 6px;">
            {{ __('order.success_title') }}
        </h1>
        <div class="wz-success-subtitle" style="font-size:1.1rem;font-weight:600;color:#f97316;margin-bottom:10px;">
            {{ __('order.success_subtitle', ['number' => $createdOrderNumber]) }}
        </div>
        <p class="wz-success-message" style="color:#475569;line-height:1.5;font-size:0.9rem;margin:0 0 14px;white-space:pre-line;">
            {{ __('order.success_message') }}
        </p>
        <a
            href="{{ route('orders.show', $createdOrderId) }}"
            class="wz-success-btn"
            style="display:inline-block;background:#f97316;color:#fff;font-weight:600;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:1rem;margin-bottom:10px;"
        >
            {{ __('order.success_go_to_order') }}
        </a>
        <div class="wz-success-countdown" style="color:#64748b;font-size:0.9rem;">
            {{ __('order.success_redirect_countdown_prefix') }}<span id="wz-countdown-seconds">45</span>{{ __('order.success_redirect_countdown_suffix') }}
        </div>
    </div>
    @script
    <script>
        (function() {
            const url = @js(route('orders.show', $createdOrderId));
            function start() {
                const span = document.getElementById('wz-countdown-seconds');
                if (!span) return setTimeout(start, 50);
                let s = 45;
                const t = setInterval(function() {
                    s--;
                    span.textContent = s;
                    if (s <= 0) { clearInterval(t); window.location.href = url; }
                }, 1000);
            }
            setTimeout(start, 0);
        })();
    </script>
    @endscript
    <style>
        @keyframes successScale {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        @media (min-width: 768px) {
            .wz-success-icon { width: 72px !important; height: 72px !important; margin-bottom: 16px !important; }
            .wz-success-icon svg { width: 36px !important; height: 36px !important; }
            .wz-success-title { font-size: 1.75rem !important; margin-bottom: 8px !important; }
            .wz-success-subtitle { font-size: 1.35rem !important; margin-bottom: 14px !important; }
            .wz-success-message { font-size: 1.05rem !important; line-height: 1.6 !important; margin-bottom: 18px !important; }
            .wz-success-btn { font-size: 1.1rem !important; padding: 14px 28px !important; margin-bottom: 14px !important; }
            .wz-success-countdown { font-size: 1.05rem !important; }
            .wz-success-content { max-width: 560px !important; }
        }
    </style>
</div>
@else
{{-- ============================================================ --}}
{{-- ORDER FORM                                                   --}}
{{-- ============================================================ --}}
<div
    x-data="newOrderForm(
        @js($exchangeRates),
        0.03,
        @js($currencies),
        {{ $maxProducts }},
        @js($defaultCurrency),
        {{ $isLoggedIn ? 'true' : 'false' }},
        @js($commissionSettings),
        @js(($editingOrderId || $productUrl || $duplicateFrom) ? $items : null),
        @js($editingOrderId ? $orderNotes : null)
    )"
    x-init="
        init();
        @if ($duplicateFrom)
        $nextTick(() => showNotify('success', @js(__('order.duplicate_prefilled'))));
        @endif
        @if ($editingOrderId)
        $nextTick(() => showNotify('success', @js(__('orders.edit_prefilled'))));
        @endif
    "
    @notify.window="showNotify($event.detail.type, $event.detail.message)"
    class="wz-order-page"
>

{{-- Toast Container --}}
<div x-ref="toasts" id="toast-container"></div>

<div class="order-page-container">
<main id="main-content">

    {{-- Tips Box --}}
    <section class="tips-box" x-show="!tipsHidden" x-cloak>
        <div class="tips-header" @click="tipsOpen = !tipsOpen">
            <h2>{{ __('opus46.tips_title') }}</h2>
            <span x-text="tipsOpen ? '▲' : '▼'" style="color:#c2a08a;font-size:0.8rem;"></span>
        </div>
        <div x-show="tipsOpen" x-collapse class="tips-content">
            <ul class="tips-list">
                @for ($i = 1; $i <= 7; $i++)
                    <li>{{ __("opus46.tip_{$i}") }}</li>
                @endfor
            </ul>
            <div style="margin-top:15px;padding-top:15px;border-top:1px solid #fef0e8;">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:#64748b;cursor:pointer;">
                    <input type="checkbox" @change="hideTips30Days()" style="cursor:pointer;">
                    <span>{{ __('opus46.tips_dont_show') }}</span>
                </label>
            </div>
        </div>
    </section>

    {{-- Order Form --}}
    <div id="order-form">

        @if ($editingOrderId)
        <section class="order-card" style="padding:12px;margin-bottom:12px;background:#fef3c7;border:1px solid #fcd34d;">
            <h2 style="font-size:1.1rem;font-weight:600;color:#92400e;margin:0;">
                {{ __('orders.edit_order_title', ['number' => $editingOrderNumber]) }}
            </h2>
            <p style="font-size:0.85rem;color:#b45309;margin:6px 0 0 0;">{{ __('orders.edit_resubmit_deadline_hint') }}</p>
        </section>
        @endif

        {{-- Products Section --}}
        <section class="order-card" style="padding:10px;">

            {{-- Desktop Table Header --}}
            <div class="table-header">
                <div>{{ __('opus46.th_num') }}</div>
                <div>{{ __('opus46.th_url') }} ({{ __('opus46.optional') }})</div>
                <div>{{ __('opus46.th_qty') }} ({{ __('opus46.optional') }})</div>
                <div>{{ __('opus46.th_color') }} ({{ __('opus46.optional') }})</div>
                <div>{{ __('opus46.th_size') }} ({{ __('opus46.optional') }})</div>
                <div>{{ __('opus46.th_price') }} ({{ __('opus46.optional') }})</div>
                <div>{{ __('opus46.th_currency') }} ({{ __('opus46.optional') }})</div>
                <div>{{ __('opus46.th_notes') }} ({{ __('opus46.optional') }})</div>
                <div>{{ __('opus46.th_files') }} ({{ __('opus46.optional') }})</div>
            </div>

            {{-- Items --}}
            <div id="items-container-wrapper">
                <div id="items-container">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="item-card"
                             :class="{
                                 'expanded': item._expanded,
                                 'is-valid': item.url.trim().length > 0,
                                 'is-minimized': !item._expanded
                             }">

                            {{-- Mobile Summary Bar --}}
                            <div class="item-summary" @click="toggleItem(idx)">
                                <div class="item-summary-text" x-text="itemSummary(idx)"></div>
                                <div class="item-summary-actions" @click.stop>
                                    <button type="button" class="btn btn-sm btn-primary toggle-details-btn"
                                            @click="item._expanded = !item._expanded">
                                        {{ __('opus46.show_edit') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                            @click="removeItem(idx)">
                                        {{ __('opus46.remove') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Item Fields Grid --}}
                            <div class="item-details">
                                {{-- Row number (desktop only) --}}
                                <div class="cell-num">
                                    <span x-text="idx + 1"></span>
                                </div>

                                {{-- URL --}}
                                <div class="cell-url">
                                    <span class="label-mobile">{{ __('opus46.lbl_url') }} ({{ __('opus46.optional') }})</span>
                                    <input type="text"
                                           x-model="item.url"
                                           @blur="calcTotals(); saveDraft()"
                                           :placeholder="idx === 0 ? '{{ __('opus46.url_placeholder') }}' : ''"
                                           class="form-control item-url">
                                </div>

                                {{-- Qty --}}
                                <div class="cell-qty">
                                    <span class="label-mobile">{{ __('opus46.lbl_qty') }} ({{ __('opus46.optional') }})</span>
                                    <input type="tel"
                                           x-model="item.qty"
                                           @input="convertArabicNums($event)"
                                           @blur="calcTotals(); saveDraft()"
                                           value="1" placeholder="1"
                                           class="form-control item-qty" style="direction:rtl;">
                                </div>

                                {{-- Color --}}
                                <div class="cell-col">
                                    <span class="label-mobile">{{ __('opus46.lbl_color') }} ({{ __('opus46.optional') }})</span>
                                    <input type="text"
                                           x-model="item.color"
                                           @blur="saveDraft()"
                                           class="form-control item-color">
                                </div>

                                {{-- Size --}}
                                <div class="cell-siz">
                                    <span class="label-mobile">{{ __('opus46.lbl_size') }} ({{ __('opus46.optional') }})</span>
                                    <input type="text"
                                           x-model="item.size"
                                           @blur="saveDraft()"
                                           class="form-control item-size">
                                </div>

                                {{-- Price --}}
                                <div class="cell-prc">
                                    <span class="label-mobile">{{ __('opus46.lbl_price') }} ({{ __('opus46.optional') }})</span>
                                    <input type="text"
                                           x-model="item.price"
                                           @input="convertArabicNums($event)"
                                           @blur="calcTotals(); saveDraft()"
                                           inputmode="decimal" placeholder="{{ __('placeholder.amount') }}"
                                           class="form-control item-price">
                                </div>

                                {{-- Currency --}}
                                <div class="cell-cur">
                                    <span class="label-mobile">{{ __('opus46.lbl_currency') }} ({{ __('opus46.optional') }})</span>
                                    <select x-model="item.currency"
                                            @change="onCurrencyChange(idx)"
                                            @blur="calcTotals(); saveDraft()"
                                            class="form-control item-currency"
                                            style="padding:0 4px;font-size:0.8rem;">
                                        <template x-for="(cur, code) in currencyList" :key="code">
                                            <option :value="code" x-text="cur.label" :selected="code === item.currency"></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- Optional Section (notes + file) always visible --}}
                                <div class="optional-section">

                                    <div class="cell-not">
                                        <span class="label-mobile">{{ __('opus46.lbl_notes') }} ({{ __('opus46.optional') }})</span>
                                        <input type="text"
                                               x-model="item.notes"
                                               @blur="saveDraft()"
                                               :placeholder="idx === 0 ? '{{ __('opus46.notes_placeholder') }}' : ''"
                                               class="form-control item-notes">
                                    </div>

                                    <div class="upload-container-new">
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <template x-if="!item._file">
                                                <button type="button" class="upload-btn upload-trigger"
                                                        @click.stop="triggerUpload(idx)"
                                                        title="{{ __('opus46.attach') }}">
                                                    <span>📎 {{ __('opus46.attach') }}</span>
                                                </button>
                                            </template>
                                            <template x-if="item._file">
                                                <div class="preview-container">
                                                    <div class="preview-item">
                                                        <template x-if="item._preview">
                                                            <img :src="item._preview">
                                                        </template>
                                                        <template x-if="!item._preview && item._fileType === 'pdf'">
                                                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#fee2e2;color:#ef4444;font-size:10px;font-weight:bold;">PDF</div>
                                                        </template>
                                                        <template x-if="!item._preview && item._fileType === 'xls'">
                                                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#dcfce7;color:#16a34a;font-size:10px;font-weight:bold;">XLS</div>
                                                        </template>
                                                        <button type="button" class="remove-img" @click="removeFile(idx)">×</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <template x-if="item._uploadProgress !== null">
                                            <div class="upload-progress">
                                                <div class="upload-progress-bar" :style="'width:' + item._uploadProgress + '%'"></div>
                                            </div>
                                        </template>
                                        <div class="upload-info">{{ __('opus46.file_info') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Add Product Button --}}
            <button type="button" @click="addProduct()" class="btn btn-secondary"
                    style="width:100%;margin-top:15px;padding:12px;">
                + {{ __('opus46.add_product') }}
            </button>

            @if (config('app.env') === 'local')
            {{-- Temp: Add 4 test items for packing order testing --}}
            <button type="button" @click="addFourTestItems()" class="btn btn-outline-secondary"
                    style="width:100%;margin-top:8px;padding:10px;font-size:0.9rem;border-style:dashed;">
                🧪 {{ __('order.dev_add_4_test_items') }}
            </button>
            @endif
        </section>

        {{-- General Notes --}}
        <section class="order-card">
            <h3 style="font-size:1rem;margin-bottom:10px;">{{ __('opus46.general_notes') }}</h3>
            <textarea x-model="orderNotes"
                      @input.debounce.500ms="saveDraft()"
                      wire:model.blur="orderNotes"
                      placeholder="{{ __('opus46.general_notes_ph') }}"
                      class="form-control" style="min-height:80px;resize:vertical;"></textarea>
        </section>

        {{-- Fixed Footer --}}
        <div class="summary-card">
            <div class="summary-info">
                <span id="items-count" x-text="productCountText()"></span>
                <span class="summary-total" x-text="totalText()"></span>
            </div>
            <button type="button" @click="submitOrder()" :disabled="submitting"
                    id="submit-order" class="btn btn-success">
                @if ($editingOrderId)
                <span x-show="!submitting">{{ __('orders.save_changes') }}</span>
                @else
                <span x-show="!submitting">{{ __('opus46.confirm_order') }}</span>
                @endif
                <span x-show="submitting" x-cloak>{{ __('opus46.submitting') }}...</span>
            </button>
        </div>

        {{-- Reset Link --}}
        <div style="text-align:start;margin-top:10px;padding-inline-start:20px;">
            <button type="button" @click="resetAll()" class="reset-link">
                {{ __('opus46.reset_all') }}
            </button>
        </div>
    </div>
</main>
</div>

{{-- Hidden File Input --}}
<input type="file" x-ref="fileInput" class="hidden-file-input"
       accept="image/*,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
       @change="handleFileSelect($event)">

{{-- Login Modal --}}
<div class="login-modal-overlay"
     :class="{ 'show': $wire.showLoginModal }"
     @click.self="$wire.closeModal()">
    <div class="login-modal">
        <div class="login-modal-header">
            <button type="button" class="login-modal-close" @click="$wire.closeModal()">&times;</button>
            <h2 class="login-modal-title">
                <span x-show="$wire.loginModalReason === 'submit'">{{ __('opus46.modal_title') }}</span>
                <span x-show="$wire.loginModalReason === 'attach'" x-cloak>{{ __('opus46.modal_title_attach') }}</span>
            </h2>
            <p class="login-modal-subtitle" x-show="$wire.loginModalReason === 'submit'">✅ {{ __('opus46.data_saved') }}</p>
            <p class="login-modal-subtitle" x-show="$wire.loginModalReason === 'attach'" x-cloak>✅ {{ __('opus46.modal_subtitle_attach') }}</p>
        </div>
        <div class="login-modal-body" x-data="{ showPassword: false }">
            {{-- Error --}}
            <div class="modal-alert error"
                 :class="{ 'show': $wire.modalError }"
                 x-show="$wire.modalError">
                ❌ <span x-text="$wire.modalError"></span>
            </div>

            {{-- Step: Email --}}
            <form class="modal-form" :class="{ 'active': $wire.modalStep === 'email' }"
                  x-show="$wire.modalStep === 'email'"
                  @submit.prevent="$wire.checkModalEmail()">
                <div class="form-group">
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" wire:model="modalEmail" required autocomplete="email" class="form-control">
                </div>
                <button type="submit" class="btn btn-success">{{ __('Continue') }}</button>
            </form>

            {{-- Step: Login --}}
            <form class="modal-form" :class="{ 'active': $wire.modalStep === 'login' }"
                  x-show="$wire.modalStep === 'login'" x-cloak
                  @submit.prevent="$wire.loginFromModal()">
                <div class="form-group">
                    <label class="form-label">{{ __('opus46.welcome_back') }}</label>
                    <div style="font-size:0.85rem;color:#64748b;margin-bottom:10px;">
                        <strong x-text="$wire.modalEmail"></strong>
                        <a href="#" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', '')"
                           style="margin-inline-start:10px;color:var(--primary);">{{ __('Change') }}</a>
                    </div>
                    <div class="password-toggle" style="display:flex;align-items:center;gap:8px;">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="modalPassword" required autocomplete="current-password"
                               class="form-control password-input" style="flex:1;">
                        <button type="button" class="btn-password-visibility" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"
                                style="flex-shrink:0;padding:8px 12px;font-size:0.8rem;color:#64748b;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer;">
                            <span x-text="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"></span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">{{ __('Log in') }}</button>
                <div style="text-align:center;margin-top:15px;">
                    <a href="#" class="form-link" @click.prevent="$wire.set('modalStep', 'reset')">{{ __('Forgot password?') }}</a>
                </div>
            </form>

            {{-- Step: Register --}}
            <form class="modal-form" :class="{ 'active': $wire.modalStep === 'register' }"
                  x-show="$wire.modalStep === 'register'" x-cloak
                  @submit.prevent="$wire.registerFromModal()">
                <div class="form-group">
                    <label class="form-label">{{ __('opus46.no_account') }}</label>
                    <div style="font-size:0.85rem;color:#64748b;margin-bottom:10px;">
                        <strong x-text="$wire.modalEmail"></strong>
                        <a href="#" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', '')"
                           style="margin-inline-start:10px;color:var(--primary);">{{ __('Change') }}</a>
                    </div>
                    <p class="modal-register-hint" style="font-size:0.85rem;color:#64748b;margin:10px 0 8px 0;">{{ __('opus46.password_create_hint') }}</p>
                    <label class="form-label">{{ __('Password') }}</label>
                    <div class="password-toggle" style="display:flex;align-items:center;gap:8px;">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="modalPassword" required autocomplete="new-password" minlength="4"
                               class="form-control password-input" style="flex:1;">
                        <button type="button" class="btn-password-visibility" @click="showPassword = !showPassword"
                                :aria-label="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"
                                style="flex-shrink:0;padding:8px 12px;font-size:0.8rem;color:#64748b;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer;">
                            <span x-text="showPassword ? '{{ __('opus46.hide_password') }}' : '{{ __('opus46.show_password') }}'"></span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">{{ __('Create account and continue') }}</button>
            </form>

            {{-- Step: Reset --}}
            <form class="modal-form" :class="{ 'active': $wire.modalStep === 'reset' }"
                  x-show="$wire.modalStep === 'reset'" x-cloak
                  @submit.prevent="$wire.sendModalResetLink()">
                <div class="form-group">
                    <p style="font-size:0.9rem;color:#475569;margin-bottom:12px;">{{ __('opus46.reset_desc') }}</p>
                    <input type="email" wire:model="modalEmail" required autocomplete="email"
                           class="form-control" placeholder="{{ __('Email') }}">
                    @if ($modalError && $modalStep === 'reset')
                        <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $modalError }}</p>
                    @endif
                    @if ($modalSuccess)
                        <p style="color:#16a34a;font-size:0.8rem;margin-top:4px;">{{ $modalSuccess }}</p>
                    @endif
                </div>
                <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="sendModalResetLink">{{ __('opus46.reset_send_link') }}</span>
                    <span wire:loading wire:target="sendModalResetLink">...</span>
                </button>
                <div style="text-align:center;margin-top:15px;">
                    <a href="#" class="form-link" @click.prevent="$wire.set('modalStep', 'email'); $wire.set('modalError', ''); $wire.set('modalSuccess', '')">{{ __('Return') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
@endif

@push('scripts')
<style>
:root {
  --accent-amber: #b45309;
  --accent-amber-light: #d97706;
  --secondary: #1e293b;
  --success: #10b981;
  --danger: #ef4444;
  --light: #fef7f5;
  --border: #f5e6e0;
}

.wz-order-page {
  font-family: "IBM Plex Sans Arabic", "Inter", sans-serif;
  background: #fff;
  color: var(--secondary);
}

.order-page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 15px;
}

/* Toast */
#toast-container {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2000;
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 90%;
  max-width: 500px;
  pointer-events: none;
}

.toast {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  padding: 12px 15px;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
  border-right: 5px solid var(--primary);
  font-weight: 600;
  font-size: 0.9rem;
  pointer-events: auto;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: toastIn 0.5s cubic-bezier(0.18,0.89,0.32,1.28) forwards;
  opacity: 0;
  cursor: pointer;
}
.toast.success { border-color: var(--success); }
.toast.error { border-color: var(--danger); }
.toast-close {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  padding: 0;
  margin-left: 4px;
  border: none;
  background: rgba(0,0,0,0.06);
  border-radius: 8px;
  font-size: 1.25rem;
  line-height: 1;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
}
.toast-close:hover { background: rgba(0,0,0,0.12); color: #334155; }

@keyframes toastIn {
  from { opacity:0; transform:translateY(-20px) scale(0.9); }
  to { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes toastOut {
  from { opacity:1; transform:translateY(0) scale(1); }
  to { opacity:0; transform:translateY(-20px) scale(0.8); }
}

/* Cards */
.order-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
  padding: 15px;
  margin-bottom: 15px;
  border: 1px solid rgba(245,210,195,0.5);
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  font-family: inherit;
  font-size: 0.9rem;
  transition: background 0.2s;
}
.btn-sm { padding:5px 10px; font-size:0.8rem; }

.btn-primary {
  background: rgba(180,83,9,0.08);
  color: var(--accent-amber);
  border: 1px solid rgba(180,83,9,0.2);
}
.btn-primary:hover {
  background: rgba(180,83,9,0.15);
  border-color: var(--accent-amber-light);
  color: var(--accent-amber-light);
}

.btn-secondary {
  background: linear-gradient(135deg, rgba(146,64,14,0.08), rgba(194,65,12,0.05));
  color: var(--accent-amber);
  border: 1.5px solid rgba(146,64,14,0.25);
  font-weight: 600;
  transition: all 0.3s;
}
.btn-secondary:hover {
  background: linear-gradient(135deg, rgba(146,64,14,0.15), rgba(194,65,12,0.12));
  border-color: var(--accent-amber-light);
  transform: translateY(-1px);
}

.btn-danger {
  background: rgba(254,226,226,0.3);
  color: #dc2626;
  border: 1px solid rgba(254,226,226,0.8);
}
.btn-danger:hover { background:#fee2e2; color:#b91c1c; }

.btn-success {
  background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
  color: #fff;
  width: 100%;
  font-size: 1rem;
  padding: 12px;
  box-shadow: 0 4px 12px rgba(249,115,22,0.25);
  transition: all 0.3s;
}
.btn-success:hover {
  background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
  box-shadow: 0 6px 20px rgba(249,115,22,0.35);
  transform: translateY(-2px);
}
.btn-success:disabled { opacity: 0.6; pointer-events: none; }

.reset-link {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 0.85rem;
  text-decoration: underline;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
}
.reset-link:hover { color: #dc2626; }

/* Form Elements */
.form-control {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #f5e6e0;
  border-radius: 8px;
  font-family: inherit;
  font-size: 0.9rem;
  background: #fff;
  height: 40px;
  transition: border-color 0.1s ease;
}
textarea.form-control { height:auto; min-height:80px; resize:vertical; }
.form-control:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(249,115,22,0.1);
}

.label-mobile {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 3px;
  font-weight: 500;
}

/* Items Container */
#items-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.item-card {
  background: #fff;
  border: 1px solid rgba(245,210,195,0.8);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
  position: relative;
  scroll-margin-bottom: 150px;
}
.item-card:focus-within {
  box-shadow: 0 4px 20px rgba(249,115,22,0.15);
  border-color: rgba(249,115,22,0.4);
  transform: translateY(-2px);
  z-index: 10;
}
.item-card.is-valid { border-color: rgba(16,185,129,0.3); }
.item-card.is-minimized { background:#f5e6e0 !important; opacity:0.9; }

/* Mobile Summary Bar */
.item-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 12px;
  background: #fef7f5;
  cursor: pointer;
  user-select: none;
}
.item-card.expanded .item-summary {
  border-bottom: 1px solid #f5e6e0;
  background: #fff;
}
.item-summary-text {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}
.item-summary-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

/* Item Details Grid */
.item-details {
  padding: 12px;
  display: none;
  grid-template-columns: repeat(6, 1fr);
  gap: 10px;
}
.item-card.expanded .item-details { display: grid; }

/* Grid Cell Positioning (Mobile) */
.cell-num { display: none; }
.cell-url { grid-column: span 6; }
.cell-qty { grid-column: span 2; }
.cell-col { grid-column: span 2; }
.cell-siz { grid-column: span 2; }
.cell-prc { grid-column: span 3; }
.cell-cur { grid-column: span 3; }
.cell-not { grid-column: span 6; }

.optional-section {
  grid-column: span 6;
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 10px;
  margin-top: 5px;
  border-top: 1px dashed #f5e6e0;
}

/* File Upload */
.upload-container-new { display:flex; flex-direction:column; gap:5px; }
.upload-btn {
  border: 1px dashed var(--border);
  color: #64748b;
  background-color: #fef7f5;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
}
.upload-btn:hover { border-color:var(--primary); background-color:#fffaf5; color:var(--primary); }
.hidden-file-input { display:none; }
.preview-container { display:flex; flex-wrap:nowrap; overflow-x:auto; gap:8px; }
.preview-item {
  position:relative;
  width:44px; height:44px;
  flex-shrink:0;
  border-radius:6px;
  overflow:hidden;
  border:1px solid #f5e6e0;
}
.preview-item img { width:100%; height:100%; object-fit:cover; }
.preview-item .remove-img {
  position:absolute; top:0; left:0;
  background:rgba(239,68,68,0.9);
  color:white; border:none; border-radius:50%;
  width:16px; height:16px; font-size:10px;
  cursor:pointer; display:flex; align-items:center; justify-content:center;
}
.upload-info { text-align:start; font-size:0.7rem; color:#a8a29e; }

.upload-progress {
  width:100%; height:4px; background:#fef0e8; border-radius:2px; overflow:hidden; margin-top:4px;
}
.upload-progress-bar {
  height:100%; background:var(--primary); border-radius:2px;
  transition:width 0.2s ease;
}

/* Footer Summary (Fixed) */
.summary-card {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(255,255,255,0.98), rgba(255,247,237,0.95));
  backdrop-filter: blur(20px);
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  box-shadow: 0 -4px 16px rgba(0,0,0,0.06), 0 -1px 4px rgba(0,0,0,0.03);
  border-top: 1px solid rgba(245,210,195,0.6);
  z-index: 100;
}
@supports (padding-bottom: env(safe-area-inset-bottom)) {
  .summary-card { padding-bottom: calc(15px + env(safe-area-inset-bottom)); }
}

#order-form { padding-bottom: 140px; }
@supports (padding-bottom: env(safe-area-inset-bottom)) {
  #order-form { padding-bottom: calc(140px + env(safe-area-inset-bottom)); }
}

.summary-info { display:flex; flex-direction:column; gap:2px; flex:1; min-width:0; }
#items-count {
  font-size: 0.7rem;
  font-weight: 400;
  color: #a8a29e;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.summary-total {
  color: #a8a29e;
  font-weight: 400;
  font-size: 0.7rem;
  white-space: nowrap;
}
#submit-order {
  flex-shrink: 0;
  min-width: 120px;
  max-width: 180px;
  width: auto;
}

/* Tips Box */
.tips-box {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border-inline-start: 4px solid var(--primary);
  margin-bottom: 20px;
  overflow: hidden;
}
.tips-header {
  padding: 12px 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  border-bottom: 1px solid #fef0e8;
}
.tips-header h2 { font-size:0.9rem; color:var(--secondary); font-weight:600; margin:0; }
.tips-content { padding:15px; font-size:0.85rem; line-height:1.6; color:#475569; }
.tips-list { list-style:none; padding:0; margin:0; }
.tips-list li { margin-bottom:10px; position:relative; padding-inline-start:18px; }
.tips-list li::before {
  content:"•";
  position:absolute;
  inset-inline-start:0;
  color:var(--primary);
  font-weight:bold;
}

/* Login Modal */
.login-modal-overlay {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.7);
  z-index: 9999;
  align-items: center;
  justify-content: center;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}
.login-modal-overlay.show { display: flex; }
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

.login-modal {
  background: #fff;
  border-radius: 16px;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  animation: slideUp 0.3s ease;
}
@keyframes slideUp {
  from { opacity:0; transform:translateY(30px); }
  to { opacity:1; transform:translateY(0); }
}

.login-modal-header {
  padding: 30px 30px 20px;
  border-bottom: 1px solid #f5e6e0;
  position: relative;
}
.login-modal-close {
  position: absolute;
  top: 20px;
  inset-inline-start: 20px;
  background: none;
  border: none;
  font-size: 28px;
  color: #94a3b8;
  cursor: pointer;
  width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
}
.login-modal-close:hover { background:rgba(0,0,0,0.05); color:var(--secondary); }
.login-modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--secondary);
  margin: 0 0 10px;
  text-align: center;
}
.login-modal-subtitle {
  font-size: 0.95rem;
  color: #64748b;
  text-align: center;
  margin: 0;
}
.login-modal-body { padding: 30px; }

.modal-form { display: none; }
.modal-form.active { display: block; }

.modal-alert {
  padding:12px 15px; border-radius:8px; margin-bottom:20px;
  font-weight:500; font-size:0.9rem; display:none;
}
.modal-alert.show { display:block; }
.modal-alert.error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

.modal-form .form-group { margin-bottom: 20px; }
.modal-form .form-label {
  display:block; font-weight:600; font-size:0.9rem; color:var(--secondary); margin-bottom:8px;
}
.modal-form .form-control {
  width:100%; padding:12px 15px; border:1px solid var(--border); border-radius:8px;
  font-family:inherit; font-size:0.95rem;
}
.modal-form .form-control:focus {
  outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(249,115,22,0.1);
}
.modal-form .form-link {
  color:var(--primary); text-decoration:none; font-weight:500; font-size:0.9rem;
}
.modal-form .form-link:hover { color:var(--primary-hover); text-decoration:underline; }
.modal-form .btn { margin-top: 20px; }

/* Large Mobile / Small Tablet */
@media (min-width: 640px) {
  .summary-card { padding:10px 20px; gap:20px; }
  .form-control { height:44px; font-size:0.95rem; padding:9px 13px; }
  textarea.form-control { font-size:0.95rem; }
  .btn { font-size:0.95rem; padding:11px 17px; min-height:44px; }
  .btn-sm { font-size:0.85rem; padding:7px 12px; min-height:38px; }
  .label-mobile { font-size:0.8rem; }
  select.form-control { font-size:0.95rem; padding:9px 8px; }
}

/* Desktop */
@media (min-width: 1024px) {
  .item-summary { display: none !important; }
  .label-mobile { display: none; }

  .item-details {
    display: grid !important;
    grid-template-columns: 0.35fr 2fr 0.5fr 0.6fr 0.6fr 0.7fr 0.8fr 1.2fr 0.8fr;
    gap: 8px;
    padding: 10px;
    align-items: center;
  }

  .summary-card {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
  }

  #order-form { padding-bottom: 120px; }

  .optional-section { display: contents !important; }
  .upload-container-new { margin-top:0; }

  .cell-num {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--secondary);
  }

  .cell-url, .cell-qty, .cell-col, .cell-siz,
  .cell-prc, .cell-cur, .cell-not, .cell-num,
  .upload-container-new { grid-column: auto; }

  .upload-info { display: none !important; }
  .upload-btn { padding:6px 8px; font-size:0.75rem; }

  #items-container-wrapper { overflow-x:auto; -webkit-overflow-scrolling:touch; }

  #items-container {
    gap: 0;
    border: 1px solid #f5e6e0;
    border-radius: 8px;
    position: relative;
    min-width: 900px;
  }

  .item-card {
    border-radius: 0;
    border: none;
    border-bottom: 1px solid #fef0e8;
    transition: background-color 0.15s ease;
  }
  .item-card:hover { background-color:#fffaf7; }
  .item-card::before {
    content:'';
    position:absolute;
    inset-inline-start:0;
    top:0; bottom:0;
    width:4px;
    background:var(--primary);
    opacity:0;
    transition:opacity 0.15s;
  }
  .item-card:hover::before { opacity:1; }
  .item-card.is-valid::before { background:var(--success); }
  .item-card:last-child { border-bottom:none; }
  .item-card.is-minimized { background:#fff !important; opacity:1; }

  .table-header {
    display: grid !important;
    grid-template-columns: 0.35fr 2fr 0.5fr 0.6fr 0.6fr 0.7fr 0.8fr 1.2fr 0.8fr;
    gap: 8px;
    padding: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    color: var(--secondary);
    background-color: #fef7f5;
    border-radius: 6px;
    margin-bottom: 0;
  }
}

/* Hide table header on mobile */
.table-header { display: none; }
</style>

<script>
function newOrderForm(rates, margin, currencyList, maxProducts, defaultCurrency, isLoggedIn, commissionSettings, initialItems, initialOrderNotes) {
    return {
        items: [],
        orderNotes: '',
        rates,
        margin,
        currencyList,
        maxProducts,
        defaultCurrency,
        isLoggedIn,
        commissionSettings: commissionSettings || { threshold: 500, below_type: 'flat', below_value: 50, above_type: 'percent', above_value: 8 },
        tipsOpen: false,
        tipsHidden: false,
        totalSar: 0,
        filledCount: 0,
        submitting: false,
        _uploadIdx: null,

        init() {
            this.checkTipsHidden();
            if (initialItems && Array.isArray(initialItems) && initialItems.length > 0) {
                const isMobile = window.innerWidth < 1024;
                this.items = initialItems.map((d, i) => ({
                    url: d.url || '', qty: (d.qty || '1').toString(), color: d.color || '', size: d.size || '',
                    price: (d.price !== null && d.price !== undefined) ? String(d.price) : '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: isMobile ? (i === 0) : true, _focused: false, _showOptional: false,
                    _file: null, _preview: null, _fileType: null, _fileName: null, _uploadProgress: null
                }));
                this.orderNotes = initialOrderNotes || '';
            } else if (!this.loadDraft()) {
                const count = window.innerWidth >= 1024 ? 5 : 1;
                for (let i = 0; i < count; i++) this.items.push(this.emptyItem());
            }
            this.calcTotals();

            window.addEventListener('beforeunload', (e) => {
                if (this.submitting || !this.hasUnsavedData()) return;
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
                _file: null, _preview: null, _fileType: null, _fileName: null,
                _uploadProgress: null
            };
        },

        addProduct() {
            if (this.items.length >= this.maxProducts) {
                this.showNotify('error', this.maxProducts + ' {{ __('opus46.max_limit_suffix') }}');
                return;
            }
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;

            if (window.innerWidth < 1024) {
                const open = this.items.findIndex(i => i._expanded);
                if (open !== -1) {
                    this.items[open]._expanded = false;
                    if (open === 0 && this.items.length === 1) {
                        this.showNotify('success', '{{ __('opus46.item_saved_collapsed_tip') }}', 10000);
                    } else {
                        this.showNotify('success', '{{ __('opus46.item_minimized_prefix') }} ' + (open + 1) + ' {{ __('opus46.item_minimized_suffix') }}');
                    }
                }
            }

            this.items.push(this.emptyItem(lastCur));
            this.saveDraft();
            this.$nextTick(() => {
                setTimeout(() => {
                    const cards = document.querySelectorAll('#items-container > div');
                    if (window.innerWidth < 1024 && cards.length >= 3) {
                        cards[cards.length - 3].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else if (window.innerWidth < 1024 && cards.length >= 2) {
                        cards[cards.length - 2].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        const last = cards[cards.length - 1];
                        if (last) last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }, 150);
            });
        },

        addFourTestItems() {
            const urls = [
                'https://www.amazon.com/dp/B0BSHF7LLL',
                'https://www.ebay.com/itm/' + Math.floor(100000000 + Math.random() * 900000000),
                'https://www.walmart.com/ip/' + Math.floor(100000 + Math.random() * 900000),
                'https://www.target.com/p/product-' + Math.floor(100 + Math.random() * 900),
            ];
            const colors = ['Red', 'Blue', 'Black', 'White', 'Navy', 'Gray', 'Green'];
            const sizes = ['S', 'M', 'L', 'XL', 'US 8', 'US 10', 'One Size'];
            const currencies = ['USD', 'EUR', 'GBP'];
            const lastCur = this.items.length > 0 ? this.items[this.items.length - 1].currency : this.defaultCurrency;
            for (let i = 0; i < 4; i++) {
                if (this.items.length >= this.maxProducts) break;
                const cur = currencies[i % currencies.length] || lastCur;
                this.items.push({
                    url: urls[i],
                    qty: String(Math.floor(Math.random() * 2) + 1),
                    color: colors[Math.floor(Math.random() * colors.length)],
                    size: sizes[Math.floor(Math.random() * sizes.length)],
                    price: String((Math.random() * 80 + 15).toFixed(2)),
                    currency: cur,
                    notes: 'Test item ' + (i + 1),
                    _expanded: true, _focused: false, _showOptional: false,
                    _file: null, _preview: null, _fileType: null, _fileName: null, _uploadProgress: null
                });
            }
            this.calcTotals();
            this.saveDraft();
            this.showNotify('success', '{{ __('order.dev_4_items_added') }}');
        },

        removeItem(idx) {
            this.$wire.shiftFileIndex(idx);
            this.items.splice(idx, 1);
            if (this.items.length === 0) this.items.push(this.emptyItem());
            this.calcTotals();
            this.saveDraft();
        },

        toggleItem(idx) {
            this.items[idx]._expanded = !this.items[idx]._expanded;
        },

        itemSummary(idx) {
            const item = this.items[idx];
            const num = idx + 1;
            const url = (item.url || '').trim();
            if (!url) return '{{ __('opus46.product_num') }} ' + num;
            try {
                const host = new URL(url.startsWith('http') ? url : 'https://' + url).hostname.replace('www.', '');
                return '{{ __('opus46.product_num') }} ' + num + ': ' + host;
            } catch { return '{{ __('opus46.product_num') }} ' + num + ': ' + url.substring(0, 30); }
        },

        onCurrencyChange(idx) {
            if (this.items[idx].currency === 'OTHER') {
                this.showNotify('success', '{{ __('opus46.other_currency_note') }}');
            }
        },

        convertArabicNums(e) {
            const ar = '٠١٢٣٤٥٦٧٨٩';
            let v = e.target.value;
            let changed = false;
            v = v.replace(/[٠-٩]/g, d => { changed = true; return ar.indexOf(d); });
            if (changed) e.target.value = v;
        },

        calcTotals() {
            let total = 0;
            let filled = 0;
            this.items.forEach(item => {
                if (item.url.trim()) filled++;
                const q = Math.max(1, parseFloat(item.qty) || 1);
                const p = parseFloat(item.price) || 0;
                const r = this.rates[item.currency] || 0;
                if (p > 0 && r > 0) total += (p * q * r);
            });
            this.totalSar = Math.floor(total * (1 + this.margin));
            this.filledCount = filled;
        },

        productCountText() {
            return '{{ __('opus46.products_count') }}: ' + this.filledCount;
        },

        totalText() {
            return '{{ __('opus46.products_value') }}: ' + this.totalSar.toLocaleString('en-US') + ' {{ __('SAR') }}';
        },

        saveDraft() {
            const data = this.items.map(i => ({
                url: i.url, qty: i.qty, color: i.color, size: i.size,
                price: i.price, currency: i.currency, notes: i.notes
            }));
            try {
                localStorage.setItem('wz_opus46_draft', JSON.stringify(data));
                localStorage.setItem('wz_opus46_notes', this.orderNotes);
            } catch {}
        },

        loadDraft() {
            try {
                const raw = localStorage.getItem('wz_opus46_draft');
                const notes = localStorage.getItem('wz_opus46_notes');
                if (notes) this.orderNotes = notes;
                if (!raw) return false;
                const data = JSON.parse(raw);
                if (!Array.isArray(data) || data.length === 0) return false;
                this.items = data.map(d => ({
                    url: d.url || '', qty: d.qty || '1', color: d.color || '',
                    size: d.size || '', price: d.price || '',
                    currency: d.currency || this.defaultCurrency, notes: d.notes || '',
                    _expanded: false, _focused: false, _showOptional: false,
                    _file: null, _preview: null, _fileType: null, _fileName: null,
                    _uploadProgress: null
                }));
                if (this.items.length > 0) this.items[0]._expanded = true;
                return true;
            } catch { return false; }
        },

        clearDraft() {
            try {
                localStorage.removeItem('wz_opus46_draft');
                localStorage.removeItem('wz_opus46_notes');
            } catch {}
        },

        resetAll() {
            if (!confirm('{{ __('opus46.reset_confirm') }}')) return;
            this.items = [];
            this.orderNotes = '';
            this.clearDraft();
            const count = window.innerWidth >= 1024 ? 5 : 1;
            for (let i = 0; i < count; i++) this.items.push(this.emptyItem());
            this.calcTotals();
            this.showNotify('success', '{{ __('opus46.cleared') }}');
        },

        triggerUpload(idx) {
            if (!this.isLoggedIn) {
                this.$wire.openLoginModalForAttach();
                return;
            }
            if (this.items[idx]._file) {
                this.showNotify('error', '{{ __('opus46.one_file') }}');
                return;
            }
            const totalFiles = this.items.filter(i => i._file).length;
            if (totalFiles >= 10) {
                this.showNotify('error', '{{ __('opus46.max_files') }}');
                return;
            }
            this._uploadIdx = idx;
            this.$refs.fileInput.click();
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file || this._uploadIdx === null) return;
            const idx = this._uploadIdx;

            const allowed = ['image/jpeg','image/png','image/gif','application/pdf',
                'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (!allowed.includes(file.type)) {
                this.showNotify('error', '{{ __('opus46.invalid_type') }}');
                e.target.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.showNotify('error', '{{ __('opus46.file_too_large') }}');
                e.target.value = '';
                return;
            }

            this.items[idx]._file = file;
            this.items[idx]._fileName = file.name;

            if (file.type === 'application/pdf') {
                this.items[idx]._fileType = 'pdf';
            } else if (file.type.includes('excel') || file.type.includes('spreadsheetml')) {
                this.items[idx]._fileType = 'xls';
            } else {
                this.items[idx]._fileType = 'img';
                const reader = new FileReader();
                reader.onload = (ev) => { this.items[idx]._preview = ev.target.result; };
                reader.readAsDataURL(file);
            }

            this.items[idx]._uploadProgress = 0;
            this.$wire.upload(
                'itemFiles.' + idx,
                file,
                () => {
                    this.items[idx]._uploadProgress = null;
                    this.showNotify('success', '{{ __('opus46.file_attached') }}');
                },
                () => {
                    this.items[idx]._uploadProgress = null;
                    this.showNotify('error', '{{ __('opus46.upload_failed') }}');
                },
                (event) => {
                    this.items[idx]._uploadProgress = event.detail.progress;
                }
            );
            e.target.value = '';
        },

        removeFile(idx) {
            this.items[idx]._file = null;
            this.items[idx]._preview = null;
            this.items[idx]._fileType = null;
            this.items[idx]._fileName = null;
            this.$wire.set('itemFiles.' + idx, null);
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
            } catch (_) {
                // validation errors are handled by Livewire
            } finally {
                this.submitting = false;
            }
        },

        checkTipsHidden() {
            try {
                const until = localStorage.getItem('wz_opus46_tips_until');
                if (until && Date.now() < parseInt(until)) this.tipsHidden = true;
                else localStorage.removeItem('wz_opus46_tips_until');
            } catch {}
        },

        hideTips30Days() {
            try {
                localStorage.setItem('wz_opus46_tips_until', (Date.now() + 30*24*60*60*1000).toString());
            } catch {}
            this.tipsHidden = true;
            this.showNotify('success', '{{ __('opus46.tips_hidden') }}');
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
            setTimeout(() => {
                if (t.parentElement) closeToast();
            }, dur);
        }
    };
}
</script>
@endpush
