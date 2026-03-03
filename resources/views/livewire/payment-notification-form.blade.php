<div class="bg-white border border-gray-200 rounded-xl p-5 mt-3">

    @if($submitted)
        @if(session('payment_notify_max_exceeded'))
        <div class="mb-4 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm" role="alert">
            {{ session('payment_notify_max_exceeded') }}
        </div>
        @endif
        <div class="text-center py-6">
            <div class="text-5xl mb-3">✅</div>
            <h3 class="text-lg font-bold text-green-700 mb-2">{{ __('payment_notify.success_title') }}</h3>
            <p class="text-gray-500 text-sm">{{ __('payment_notify.success_message') }}</p>
            <button wire:click="$set('submitted', false)" class="mt-4 px-4 py-2 text-sm text-primary-600 underline">{{ __('payment_notify.submit_new') }}</button>
        </div>
    @else
        <h3 class="text-base font-bold text-gray-900 mb-4">📝 {{ __('payment_notify.title') }}</h3>

        <form wire:submit="submit" class="space-y-4">

            {{-- Guest fields (shown only when not logged in) --}}
            @if($isGuest)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('payment_notify.guest_name') }} <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        wire:model="guest_name"
                        placeholder="{{ __('payment_notify.guest_name') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    @error('guest_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('payment_notify.guest_phone') }} <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        wire:model="guest_phone"
                        placeholder="{{ __('payment_notify.phone_placeholder') }}"
                        dir="ltr"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    @error('guest_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            @endif

            {{-- Amount --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('payment_notify.amount_label') }} <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    inputmode="decimal"
                    wire:model="amount"
                    placeholder="{{ __('payment_notify.amount_placeholder') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Payment Method --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('payment_notify.method_label') }} <span class="text-red-500">*</span></label>
                <select
                    wire:model="payment_method"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
                >
                    <option value="">{{ __('payment_notify.method_placeholder') }}</option>
                    <option value="alrajhi">{{ __('orders.banks.alrajhi') }}</option>
                    <option value="alahli">{{ __('orders.banks.alahli') }}</option>
                    <option value="albilad">{{ __('orders.banks.albilad') }}</option>
                    <option value="alinma">{{ __('orders.banks.alinma') }}</option>
                    <option value="samba">{{ __('orders.banks.samba') }}</option>
                    <option value="saib">{{ __('orders.banks.saib') }}</option>
                    <option value="riyad">{{ __('orders.banks.riyad') }}</option>
                    <option value="visa_mastercard">{{ __('orders.payment_method_visa_mastercard') }}</option>
                    <option value="mada">{{ __('orders.banks.mada') }}</option>
                    <option value="international_bank_transfer">{{ __('orders.payment_method_international_bank_transfer') }}</option>
                    <option value="credit_card">{{ __('orders.payment_method_credit_card') }}</option>
                    <option value="paypal">{{ __('orders.payment_method_paypal') }}</option>
                    <option value="other">{{ __('orders.payment_method_other') }}</option>
                </select>
                @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Order Number --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('payment_notify.order_number_label') }}</label>
                @if($userOrders->isNotEmpty())
                    <select
                        wire:model="order_number"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
                    >
                        <option value="">{{ __('payment_notify.order_select') }}</option>
                        @foreach($userOrders as $index => $order)
                            <option value="{{ $order->id }}">
                                {{ __('payment_notify.order_label') }} {{ $order->order_number }}
                                @if($order->total_amount) — {{ number_format($order->total_amount, 2) }} {{ __('orders.sar') }} @endif
                                @if($index === 0) {{ __('payment_notify.last_order') }} @endif
                            </option>
                        @endforeach
                        <option value="other">{{ __('payment_notify.other_order') }}</option>
                    </select>
                @else
                    <input
                        type="text"
                        wire:model="order_number"
                        placeholder="{{ __('placeholder.order_number') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <p class="text-gray-400 text-xs mt-1" style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">{{ __('payment_notify.order_hint') }}</p>
                @endif
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('payment_notify.notes_label') }}</label>
                <textarea
                    wire:model="notes"
                    rows="3"
                    placeholder="{{ __('payment_notify.notes_placeholder') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y"
                ></textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @if($maxStandaloneFiles > 0)
            {{-- Attachments --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('payment_notify.attachments_label') }} ({{ __('payment_notify.optional') }})</label>
                <input type="file" wire:model="receipts" multiple
                    accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.tif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.heic"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 file:me-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 file:text-xs file:font-medium"
                />
                @error('receipts') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('receipts.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endif

            {{-- Submit --}}
            <div class="flex gap-3 flex-wrap">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex-1 min-w-[160px] px-5 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-lg hover:bg-primary-700 disabled:opacity-60 transition"
                >
                    <span wire:loading.remove>✅ {{ __('payment_notify.submit_btn') }}</span>
                    <span wire:loading>⏳ {{ __('payment_notify.submitting') }}</span>
                </button>
            </div>

            <p class="text-xs text-gray-400 text-center">
                {{ __('payment_notify.inquiry') }}
                <a href="https://wa.me/00966556063500" class="text-primary-600 font-semibold hover:underline" target="_blank" rel="noopener">{{ __('payment_notify.contact_whatsapp') }}</a>
            </p>

        </form>
    @endif

</div>
