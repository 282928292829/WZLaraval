<div class="bg-white border border-gray-200 rounded-xl p-5 mt-3">

    @if($submitted)
        <div class="text-center py-6">
            <div class="text-5xl mb-3">✅</div>
            <h3 class="text-lg font-bold text-green-700 mb-2">تم إرسال الإبلاغ بنجاح!</h3>
            <p class="text-gray-500 text-sm">شكراً لك، سنقوم بمراجعة الدفع وتأكيده في أقرب وقت ممكن.</p>
            <button wire:click="$set('submitted', false)" class="mt-4 px-4 py-2 text-sm text-primary-600 underline">إرسال إبلاغ جديد</button>
        </div>
    @else
        <h3 class="text-base font-bold text-gray-900 mb-4">📝 أبلغنا بالدفع</h3>

        <form wire:submit="submit" class="space-y-4">

            {{-- Guest fields (shown only when not logged in) --}}
            @if($isGuest)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">الاسم <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        wire:model="guest_name"
                        placeholder="الاسم الكامل"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    @error('guest_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الجوال / واتساب <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        wire:model="guest_phone"
                        placeholder="05xxxxxxxx"
                        dir="ltr"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    @error('guest_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            @endif

            {{-- Amount --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">المبلغ المدفوع (ريال سعودي) <span class="text-red-500">*</span></label>
                <input
                    type="number"
                    wire:model="amount"
                    step="0.01"
                    min="1"
                    placeholder="مثال: 500.00"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Payment Method --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">طريقة الدفع / البنك <span class="text-red-500">*</span></label>
                <select
                    wire:model="payment_method"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
                >
                    <option value="">-- اختر طريقة الدفع --</option>
                    <option value="مصرف الراجحي">مصرف الراجحي</option>
                    <option value="البنك الأهلي التجاري">البنك الأهلي التجاري</option>
                    <option value="بنك البلاد">بنك البلاد</option>
                    <option value="بنك الإنماء">بنك الإنماء</option>
                    <option value="البنك السعودي الأول">البنك السعودي الأول</option>
                    <option value="البنك السعودي للإستثمار">البنك السعودي للإستثمار</option>
                    <option value="بنك الرياض">بنك الرياض</option>
                    <option value="بطاقة ائتمانية">بطاقة ائتمانية (Credit Card)</option>
                    <option value="مدى">مدى (Mada)</option>
                    <option value="باي بال">باي بال (PayPal)</option>
                    <option value="أخرى">أخرى</option>
                </select>
                @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Order Number --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الطلب (اختياري)</label>
                @if($userOrders->isNotEmpty())
                    <select
                        wire:model="order_number"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
                    >
                        <option value="">-- اختر رقم الطلب --</option>
                        @foreach($userOrders as $index => $order)
                            <option value="{{ $order->id }}">
                                طلب {{ $order->order_number }}
                                @if($order->total_amount) — {{ number_format($order->total_amount, 2) }} ريال @endif
                                @if($index === 0) (الطلب الأخير) @endif
                            </option>
                        @endforeach
                        <option value="other">رقم طلب آخر</option>
                    </select>
                @else
                    <input
                        type="text"
                        wire:model="order_number"
                        placeholder="مثال: 12345"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <p class="text-gray-400 text-xs mt-1" style="font-family: 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;">أدخل رقم الطلب إذا كنت تعرفه، وإلا سنقوم بالبحث عن طلبك من خلال حسابك.</p>
                @endif
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">ملاحظات إضافية (اختياري)</label>
                <textarea
                    wire:model="notes"
                    rows="3"
                    placeholder="أي معلومات إضافية تود إضافتها..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y"
                ></textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Submit --}}
            <div class="flex gap-3 flex-wrap">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex-1 min-w-[160px] px-5 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-lg hover:bg-primary-700 disabled:opacity-60 transition"
                >
                    <span wire:loading.remove>✅ إرسال الإبلاغ</span>
                    <span wire:loading>⏳ جاري الإرسال...</span>
                </button>
            </div>

            <p class="text-xs text-gray-400 text-center">
                هل لديك استفسار؟
                <a href="https://wa.me/00966556063500" class="text-primary-600 font-semibold hover:underline" target="_blank" rel="noopener">تواصل معنا عبر الواتساب</a>
            </p>

        </form>
    @endif

</div>
