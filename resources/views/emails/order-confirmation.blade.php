<x-emails.layout :subject="'تأكيد الطلب #' . $order->order_number">

    <p class="greeting">مرحباً {{ $order->user->name ?? 'عزيزنا العميل' }}،</p>

    <p class="intro">
        شكراً على ثقتك بواسطزون! تم استلام طلبك بنجاح وسيقوم فريقنا بمراجعته في أقرب وقت.
    </p>

    {{-- Order summary card --}}
    <div class="card">
        <div class="card-title">تفاصيل الطلب</div>
        <div class="info-row">
            <span class="info-label">رقم الطلب</span>
            <span class="info-value">#{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الطلب</span>
            <span class="info-value">{{ $order->created_at->format('Y/m/d H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الحالة</span>
            <span class="info-value">
                <span class="badge badge-orange">{{ __('order.status.' . $order->status) }}</span>
            </span>
        </div>
        @if($order->total_amount)
        <div class="info-row">
            <span class="info-label">المبلغ الإجمالي</span>
            <span class="info-value">{{ number_format($order->total_amount, 2) }} {{ $order->currency ?? 'SAR' }}</span>
        </div>
        @endif
    </div>

    {{-- Items table --}}
    @if($order->items->count())
    <div class="card">
        <div class="card-title">المنتجات</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $i => $item)
                <tr>
                    <td style="color:#9ca3af;font-size:12px;">{{ $i + 1 }}</td>
                    <td>
                        @if($item->url)
                            <a href="{{ $item->url }}" style="color:#f97316;text-decoration:none;font-size:12px;word-break:break-all;">{{ $item->url }}</a>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                        @if($item->notes)
                            <div style="font-size:11px;color:#6b7280;margin-top:4px;">{{ $item->notes }}</div>
                        @endif
                    </td>
                    <td>{{ $item->qty ?? 1 }}</td>
                    <td>
                        @if($item->price)
                            {{ number_format($item->price, 2) }} {{ $item->currency ?? '' }}
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- CTA --}}
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/orders/' . $order->id) }}" class="btn">
            عرض الطلب
        </a>
    </div>

    <hr class="divider">

    <p style="font-size:13px;color:#6b7280;line-height:1.7;">
        يمكنك متابعة حالة طلبك في أي وقت من خلال صفحة طلباتك على الموقع.
        إذا كان لديك أي استفسار، لا تتردد في التواصل معنا عبر واتساب.
    </p>

</x-emails.layout>
