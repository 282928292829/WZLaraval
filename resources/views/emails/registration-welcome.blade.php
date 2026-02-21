<x-emails.layout subject="مرحباً بك في واسطزون!">

    <p class="greeting">أهلاً وسهلاً {{ $user->name }} 🎉</p>

    <p class="intro">
        يسعدنا انضمامك إلى عائلة واسطزون! حسابك جاهز الآن ويمكنك البدء بالتسوق من المتاجر العالمية باحترافية وسهولة.
    </p>

    {{-- What you can do --}}
    <div class="card">
        <div class="card-title">ماذا يمكنك أن تفعل الآن؟</div>
        <table>
            <tbody>
                <tr>
                    <td style="width:32px;font-size:20px;">🛒</td>
                    <td>
                        <strong>أضف طلبك الأول</strong>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">الصقْ رابط المنتج من أي متجر عالمي ونحن نتولى الباقي</div>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:20px;">📦</td>
                    <td>
                        <strong>تابع حالة طلباتك</strong>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">تحديثات فورية من الشراء حتى التسليم</div>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:20px;">💬</td>
                    <td>
                        <strong>تواصل مع فريقنا</strong>
                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">متاحون لمساعدتك في أي وقت</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Account info --}}
    <div class="card">
        <div class="card-title">معلومات حسابك</div>
        <div class="info-row">
            <span class="info-label">الاسم</span>
            <span class="info-value">{{ $user->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني</span>
            <span class="info-value">{{ $user->email }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ التسجيل</span>
            <span class="info-value">{{ $user->created_at->format('Y/m/d') }}</span>
        </div>
    </div>

    {{-- CTA --}}
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/new-order') }}" class="btn">
            ابدأ طلبك الأول
        </a>
    </div>

    <hr class="divider">

    <p style="font-size:13px;color:#6b7280;line-height:1.7;">
        إذا لم تكن أنت من أنشأ هذا الحساب، يرجى تجاهل هذا البريد أو
        <a href="{{ url('/') }}" style="color:#f97316;">التواصل معنا</a>.
    </p>

</x-emails.layout>
