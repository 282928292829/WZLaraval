<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            // ─── How to Order ────────────────────────────────────────────────
            [
                'slug' => 'how-to-order',
                'title_ar' => 'طريقة الطلب',
                'title_en' => 'How to Order',
                'body_ar' => '
<div class="space-y-10">
  <p class="text-gray-600 leading-relaxed">يحرص <strong>وسيط زون</strong> على تقديم تجربة شرائية سهلة وممتعة لعملائنا. يمكنك التسجيل في موقعنا ثم إنشاء طلب جديد وإضافة روابط المنتجات المراد شراؤها مع التفاصيل المطلوبة مثل اللون، المقاس، العدد، وغيرها من الخيارات.</p>

  <div class="space-y-5">
    <h3 class="text-lg font-bold text-gray-900 border-r-4 border-primary-500 pr-3">خطوات الطلب</h3>
    <ol class="space-y-4 text-gray-600 list-none">
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">١</span><span>قم بالدخول إلى موقعنا والتسجيل في حسابك أو إنشاء حساب جديد.</span></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">٢</span><span>انتقل إلى صفحة <strong>طلب جديد</strong>، ثم أدخل روابط المنتجات والتفاصيل الخاصة بكل منتج.</span></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">٣</span><span>يمكنك إرفاق صور توضيحية أمام كل رابط أو رفع ملف يحتوي على جميع التفاصيل في قسم التعليقات داخل صفحة الطلب.</span></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">٤</span><span>في حال عدم رغبتك بتعبئة النموذج الإلكتروني، يمكنك رفع ملف <strong>Word</strong> أو <strong>Excel</strong> يحتوي على تفاصيل المنتجات وإرفاقه في قسم التعليقات.</span></li>
    </ol>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-r-4 border-primary-500 pr-3">مثال على تعبئة الطلب</h3>
    <p class="text-gray-600 text-sm">على سبيل المثال، إذا رغبت بشراء المنتج في الرابط أدناه (يمكنك تعبئة البيانات بالعربية أو الإنجليزية، ويفضّل إدخال اللون أو المقاس باللغة الإنجليزية عند توفرها):</p>
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-2 text-sm text-gray-700">
      <div><span class="font-semibold">رابط المنتج:</span> <a href="http://www.Amazon.com/item/2014-single-copy-hot-sale-protective-for-iphpne-5c-5c-iphone-cover-drit-resistant-case-for/1589889236.html" target="_blank" rel="noreferrer noopener" class="text-primary-600 hover:underline break-all">http://www.Amazon.com/item/…/1589889236.html</a></div>
      <div class="flex gap-6 flex-wrap">
        <span><span class="font-semibold">المقاس:</span> لا يوجد</span>
        <span><span class="font-semibold">اللون:</span> أسود (Black)</span>
        <span><span class="font-semibold">العدد:</span> ٢</span>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-r-4 border-primary-500 pr-3">بعد إرسال الطلب</h3>
    <p class="text-gray-600 text-sm">سيقوم فريقنا بمراجعة الطلب وحساب التكلفة الإجمالية بما في ذلك العمولة ورسوم الشحن إلى عنوانك. بعد ذلك يمكنك تحويل المبلغ إلى حسابنا البنكي، ثم إبلاغنا عبر:</p>
    <ul class="space-y-1 text-gray-600 text-sm">
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5">•</span> كتابة ملاحظة في الطلب.</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5">•</span> إرسال رسالة عبر واتساب.</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5">•</span> أو التواصل عبر البريد الإلكتروني.</li>
    </ul>
    <div class="bg-primary-50 border border-primary-200 rounded-lg px-4 py-3 text-sm text-primary-800">
      يبدأ فريق وسيط زون بتنفيذ طلبك خلال <strong>24 إلى 72 ساعة</strong> من وقت استلام الحوالة.
    </div>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-r-4 border-primary-500 pr-3">الشحن والمتابعة</h3>
    <p class="text-gray-600 text-sm">يرجى متابعة حالة طلبك بشكل مستمر، حيث ستصلك إشعارات عبر البريد الإلكتروني عند وجود أي تحديث. بعد شراء المنتجات، سيقوم فريقنا بتجميعها في طرد واحد ليتم شحنها إلى عنوانك.</p>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-r-4 border-primary-500 pr-3">مدة التوصيل</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <div class="text-sm text-gray-500 mb-1">الوصول إلى مقرنا</div>
        <div class="font-bold text-gray-800">٤ – ١٠ أيام عمل</div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <div class="text-sm text-gray-500 mb-1">الشحن إلى السعودية</div>
        <div class="font-bold text-gray-800">٦ – ١٤ يوم عمل</div>
      </div>
      <div class="bg-primary-50 rounded-lg p-3 text-center border border-primary-200">
        <div class="text-sm text-primary-600 mb-1">المدة الإجمالية</div>
        <div class="font-bold text-primary-800">١٠ – ٣٠ يوم عمل</div>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-r-4 border-primary-500 pr-3">للعملاء الذين لديهم حساب في مواقع التسوّق</h3>
    <p class="text-gray-600 text-sm">إذا كان لديك حساب في موقع التسوّق وتريد أن يساعدك فريق الطلب في نسخ المنتجات مباشرة، يُرجى اتباع أحد الخيارات الآمنة التالية:</p>
    <ul class="space-y-2 text-gray-600 text-sm">
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5 flex-shrink-0">•</span> إنشئ حسابًا مؤقتًا على موقع المتجر وأضف المنتجات إلى سلة التسوق أو قائمة المفضلات، ثم زوّدنا باسم المستخدم وكلمة المرور المؤقتة (وقم بتغييرها بعد اكتمال الطلب).</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5 flex-shrink-0">•</span> أو شارك معنا رابط السلة أو رابط قائمة المفضلات (إن وفّر المتجر ميزة مشاركة السلة)، وبهذا لا تحتاج لمشاركة كلمة المرور.</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5 flex-shrink-0">•</span> كبديل، يمكنك إرسال روابط المنتجات مع التفاصيل اللازمة (اللون، المقاس، الكمية) عبر نموذج الطلب أو كمرفق في قسم التعليقات.</li>
    </ul>
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
      نرجو عدم مشاركة كلمات المرور الدائمة لحسابك؛ وفي حال زوّدتنا بوصول مؤقت، سنستخدمه فقط لنسخ المنتجات المطلوبة ولن نحتفظ بكلمة المرور بعد إتمام الطلب.
    </div>
  </div>

  <div class="space-y-6">
    <h2 class="text-lg font-bold text-gray-900 border-r-4 border-primary-500 pr-3">طرق الطلب المتاحة</h2>
    <p class="text-gray-600 text-sm">نوفر لك عدة طرق سهلة ومرنة لتقديم طلبك:</p>

    <div class="space-y-4">

      <div class="border border-gray-200 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-xs font-semibold bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">موصى به</span>
          <h3 class="font-bold text-gray-900">الطلب عبر الموقع</h3>
        </div>
        <p class="text-gray-500 text-sm mb-3">استخدم موقعنا الإلكتروني للحصول على تجربة طلب سلسة ومنظمة:</p>
        <ul class="space-y-2 text-sm text-gray-700 mb-4">
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>تصفح المنتجات:</strong> استعرض جداول المنتجات والأسعار والمعلومات التفصيلية بكل سهولة</span></li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>أضف إلى الطلب:</strong> اختر المنتجات التي تحتاجها مباشرة من الموقع</span></li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>رفع ملف Excel:</strong> إذا كنت تفضل ذلك، يمكنك رفع ملف Excel يحتوي على جميع المنتجات التي تحتاجها</span></li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>إرسال الطلب:</strong> أكمل معلومات الطلب وأرسله مباشرة</span></li>
        </ul>
        <a href="/new-order" class="inline-flex items-center gap-2 bg-primary-600 text-white text-sm px-5 py-2.5 rounded-lg font-semibold hover:bg-primary-700 transition">ابدأ طلبك الآن</a>
      </div>

      <div class="border border-gray-200 rounded-xl p-5">
        <h3 class="font-bold text-gray-900 mb-3">الطلب عبر واتساب</h3>
        <p class="text-gray-500 text-sm mb-3">تواصل معنا مباشرة عبر واتساب:</p>
        <ul class="space-y-2 text-sm text-gray-700 mb-4">
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span> أرسل لنا قائمة المنتجات التي تحتاجها</li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span> يمكنك إرسال ملف Excel أو صور أو قائمة نصية</li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span> سنقوم بمساعدتك في إتمام الطلب</li>
        </ul>
        <a href="https://wa.me/00966556063500?text=مرحباً، أود تقديم طلب" class="inline-flex items-center gap-2 bg-green-500 text-white text-sm px-5 py-2.5 rounded-lg font-semibold hover:bg-green-600 transition" target="_blank" rel="noopener">تواصل عبر واتساب</a>
      </div>

    </div>

    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
      <span class="font-semibold text-gray-800">📄 رفع ملف Excel —</span> يمكنك تحضير قائمة المنتجات في ملف Excel ورفعه مباشرة عبر الموقع أو إرساله عبر واتساب. هذا يوفر عليك الوقت خاصة عند طلب كميات كبيرة.
    </div>

    <div class="space-y-3">
      <h3 class="text-base font-bold text-gray-900">لماذا نوصي بالطلب عبر الموقع؟</h3>
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">✓</span><div class="font-semibold text-gray-800 mt-1">سهولة التصفح</div><div class="text-gray-500 text-xs mt-0.5">جداول منظمة تحتوي على جميع المعلومات والأسعار</div></div>
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">⏱️</span><div class="font-semibold text-gray-800 mt-1">توفير الوقت</div><div class="text-gray-500 text-xs mt-0.5">إرسال الطلب مباشرة دون الحاجة للانتظار</div></div>
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">📊</span><div class="font-semibold text-gray-800 mt-1">رفع ملفات Excel</div><div class="text-gray-500 text-xs mt-0.5">إمكانية رفع قائمة كاملة بالمنتجات دفعة واحدة</div></div>
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">👁️</span><div class="font-semibold text-gray-800 mt-1">متابعة الطلب</div><div class="text-gray-500 text-xs mt-0.5">تتبع حالة طلبك بكل سهولة من لوحة التحكم</div></div>
      </div>
    </div>

    <div class="bg-gray-900 text-white rounded-xl p-6 text-center space-y-4">
      <div>
        <div class="font-bold text-lg">جاهز لتقديم طلبك؟</div>
        <div class="text-gray-400 text-sm mt-1">اختر الطريقة الأنسب لك وابدأ الآن</div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="/new-order" class="bg-primary-500 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-primary-400 transition">طلب عبر الموقع</a>
        <a href="https://wa.me/00966556063500?text=مرحباً، أود تقديم طلب" class="bg-green-500 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-green-400 transition" target="_blank" rel="noopener">طلب عبر واتساب</a>
      </div>
    </div>

  </div>
</div>',
                'body_en' => '
<div class="space-y-10">
  <p class="text-gray-600 leading-relaxed"><strong>Wasetzon</strong> is committed to providing an easy and enjoyable shopping experience for our customers. You can register on our site, then create a new order and add product links with the required details such as color, size, quantity, and other options.</p>

  <div class="space-y-5">
    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-primary-500 pl-3">Order Steps</h3>
    <ol class="space-y-4 text-gray-600 list-none">
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">1</span><span>Visit our site and sign in to your account or create a new account.</span></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">2</span><span>Go to the <strong>New Order</strong> page, then enter product links and details for each product.</span></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">3</span><span>You can attach images next to each link or upload a file with all details in the order comments section.</span></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-sm font-bold flex items-center justify-center">4</span><span>If you prefer not to fill out the form, you can upload a <strong>Word</strong> or <strong>Excel</strong> file and attach it in the comments section.</span></li>
    </ol>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-primary-500 pl-3">Example Order</h3>
    <p class="text-gray-600 text-sm">For example, if you want to buy the product at the link below (data can be in Arabic or English; entering color or size in English is preferred when available):</p>
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-2 text-sm text-gray-700">
      <div><span class="font-semibold">Product link:</span> <a href="http://www.Amazon.com/item/2014-single-copy-hot-sale-protective-for-iphpne-5c-5c-iphone-cover-drit-resistant-case-for/1589889236.html" target="_blank" rel="noreferrer noopener" class="text-primary-600 hover:underline break-all">http://www.Amazon.com/item/…/1589889236.html</a></div>
      <div class="flex gap-6 flex-wrap">
        <span><span class="font-semibold">Size:</span> N/A</span>
        <span><span class="font-semibold">Color:</span> Black</span>
        <span><span class="font-semibold">Quantity:</span> 2</span>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-primary-500 pl-3">After Submitting Your Order</h3>
    <p class="text-gray-600 text-sm">Our team will review your order and calculate the total cost including commission and shipping fees to your address. You can then transfer the amount to our bank account and notify us via:</p>
    <ul class="space-y-1 text-gray-600 text-sm">
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5">•</span> Writing a note in the order.</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5">•</span> Sending a message via WhatsApp.</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5">•</span> Or contacting us via email.</li>
    </ul>
    <div class="bg-primary-50 border border-primary-200 rounded-lg px-4 py-3 text-sm text-primary-800">
      The Wasetzon team begins processing your order within <strong>24 to 72 hours</strong> of receiving the transfer.
    </div>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-primary-500 pl-3">Shipping and Follow-up</h3>
    <p class="text-gray-600 text-sm">Please follow up on your order status regularly, as you will receive email notifications when there are any updates. After purchasing the products, our team will consolidate them into one package to be shipped to your address.</p>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-primary-500 pl-3">Delivery Time</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <div class="text-sm text-gray-500 mb-1">Arrival at our facility</div>
        <div class="font-bold text-gray-800">4 – 10 business days</div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <div class="text-sm text-gray-500 mb-1">Shipping to Saudi Arabia</div>
        <div class="font-bold text-gray-800">6 – 14 business days</div>
      </div>
      <div class="bg-primary-50 rounded-lg p-3 text-center border border-primary-200">
        <div class="text-sm text-primary-600 mb-1">Total duration</div>
        <div class="font-bold text-primary-800">10 – 30 business days</div>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 border-l-4 border-primary-500 pl-3">For Customers With Shopping Site Accounts</h3>
    <p class="text-gray-600 text-sm">If you have an account on a shopping site and want our order team to copy products directly, please follow one of these secure options:</p>
    <ul class="space-y-2 text-gray-600 text-sm">
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5 flex-shrink-0">•</span> Create a temporary account on the store, add products to the cart or wishlist, then share the temporary username and password (change it after the order is complete).</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5 flex-shrink-0">•</span> Or share the cart or wishlist link (if the store supports link sharing) — no password sharing needed.</li>
      <li class="flex gap-2"><span class="text-primary-500 mt-0.5 flex-shrink-0">•</span> Alternatively, send product links with the necessary details (color, size, quantity) via the order form or as an attachment in the comments.</li>
    </ul>
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
      Please do not share your permanent account passwords. If you provide temporary access, we will use it only to copy the requested products and will not keep the password after the order is complete.
    </div>
  </div>

  <div class="space-y-6">
    <h2 class="text-lg font-bold text-gray-900 border-l-4 border-primary-500 pl-3">Ordering Methods</h2>
    <p class="text-gray-600 text-sm">We offer several easy and flexible ways to place your order:</p>

    <div class="space-y-4">

      <div class="border border-gray-200 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-xs font-semibold bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">Recommended</span>
          <h3 class="font-bold text-gray-900">Order via Website</h3>
        </div>
        <p class="text-gray-500 text-sm mb-3">Use our website for a smooth and organized ordering experience:</p>
        <ul class="space-y-2 text-sm text-gray-700 mb-4">
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>Browse products:</strong> View product tables with prices and detailed information easily</span></li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>Add to order:</strong> Select the products you need directly from the site</span></li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>Upload Excel file:</strong> If you prefer, you can upload an Excel file containing all the products you need</span></li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span><span><strong>Submit order:</strong> Complete your order details and submit directly</span></li>
        </ul>
        <a href="/new-order" class="inline-flex items-center gap-2 bg-primary-600 text-white text-sm px-5 py-2.5 rounded-lg font-semibold hover:bg-primary-700 transition">Start Your Order</a>
      </div>

      <div class="border border-gray-200 rounded-xl p-5">
        <h3 class="font-bold text-gray-900 mb-3">Order via WhatsApp</h3>
        <p class="text-gray-500 text-sm mb-3">Contact us directly via WhatsApp:</p>
        <ul class="space-y-2 text-sm text-gray-700 mb-4">
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span> Send us a list of products you need</li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span> You can send an Excel file, images, or a text list</li>
          <li class="flex gap-2"><span class="text-green-500 flex-shrink-0">✓</span> We will help you complete your order</li>
        </ul>
        <a href="https://wa.me/00966556063500?text=Hello, I would like to place an order" class="inline-flex items-center gap-2 bg-green-500 text-white text-sm px-5 py-2.5 rounded-lg font-semibold hover:bg-green-600 transition" target="_blank" rel="noopener">Contact via WhatsApp</a>
      </div>

    </div>

    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
      <span class="font-semibold text-gray-800">📄 Upload Excel File —</span> You can prepare your product list in an Excel file and upload it directly via the website or send it via WhatsApp. This saves you time, especially when ordering large quantities.
    </div>

    <div class="space-y-3">
      <h3 class="text-base font-bold text-gray-900">Why order via the website?</h3>
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">✓</span><div class="font-semibold text-gray-800 mt-1">Easy Browsing</div><div class="text-gray-500 text-xs mt-0.5">Organized tables with all information and prices</div></div>
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">⏱️</span><div class="font-semibold text-gray-800 mt-1">Save Time</div><div class="text-gray-500 text-xs mt-0.5">Submit your order directly without waiting</div></div>
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">📊</span><div class="font-semibold text-gray-800 mt-1">Upload Excel Files</div><div class="text-gray-500 text-xs mt-0.5">Upload a complete product list in one go</div></div>
        <div class="bg-gray-50 rounded-lg p-3 text-sm"><span class="text-gray-400 text-lg">👁️</span><div class="font-semibold text-gray-800 mt-1">Track Your Order</div><div class="text-gray-500 text-xs mt-0.5">Follow your order status easily from the dashboard</div></div>
      </div>
    </div>

    <div class="bg-gray-900 text-white rounded-xl p-6 text-center space-y-4">
      <div>
        <div class="font-bold text-lg">Ready to place your order?</div>
        <div class="text-gray-400 text-sm mt-1">Choose the method that works best for you</div>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="/new-order" class="bg-primary-500 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-primary-400 transition">Order via Website</a>
        <a href="https://wa.me/00966556063500?text=Hello, I would like to place an order" class="bg-green-500 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-green-400 transition" target="_blank" rel="noopener">Order via WhatsApp</a>
      </div>
    </div>

  </div>
</div>',
                'seo_title_ar' => 'كيف تطلب من وسيط زون؟',
                'seo_title_en' => 'How to Order from Wasetzon',
                'seo_description_ar' => 'تعرف على خطوات الطلب من وسيط زون — الخدمة الأسرع لشراء المنتجات من أمريكا والعالم.',
                'seo_description_en' => 'Learn how to place an order on Wasetzon — the fastest product sourcing service from the US and worldwide.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'menu_order' => 1,
            ],

            // ─── FAQ ────────────────────────────────────────────────────────
            [
                'slug' => 'faq',
                'title_ar' => 'الأسئلة الشائعة',
                'title_en' => 'FAQ',
                'body_ar' => 'faq-template',
                'body_en' => 'faq-template',
                'seo_title_ar' => 'الأسئلة الشائعة — وسيط زون',
                'seo_title_en' => 'FAQ — Wasetzon',
                'seo_description_ar' => 'الأسئلة الشائعة والمتكررة عن أمازون ووسيط أمازون والشحن والعمولة والدفع.',
                'seo_description_en' => 'Frequently asked questions about ordering, shipping, commissions, and payment on Wasetzon.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'menu_order' => 2,
            ],

            // ─── Payment Methods ─────────────────────────────────────────────
            [
                'slug' => 'payment-methods',
                'title_ar' => 'طرق الدفع',
                'title_en' => 'Payment Methods',
                'body_ar' => 'payment-methods-template',
                'body_en' => 'payment-methods-template',
                'seo_title_ar' => 'طرق الدفع — وسيط زون',
                'seo_title_en' => 'Payment Methods — Wasetzon',
                'seo_description_ar' => 'طرق الدفع المتاحة لدى وسيط زون — تحويل بنكي عبر الراجحي والأهلي والبلاد والإنماء والسعودي الأول والسعودي للاستثمار.',
                'seo_description_en' => 'Available payment methods on Wasetzon — bank transfers via Al Rajhi, Al Ahli, Al Bilad, Al Inma, SABB, and SAIB.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'menu_order' => 3,
            ],

            // ─── Contact Us ───────────────────────────────────────────────────
            [
                'slug' => 'contact-us',
                'title_ar' => 'تواصل معنا',
                'title_en' => 'Contact Us',
                'body_ar' => 'contact-us-template',
                'body_en' => 'contact-us-template',
                'seo_title_ar' => 'تواصل معنا — وسيط زون',
                'seo_title_en' => 'Contact Us — Wasetzon',
                'seo_description_ar' => 'تواصل مع فريق وسيط زون عبر البريد الإلكتروني أو واتساب أو نموذج التواصل.',
                'seo_description_en' => 'Contact the Wasetzon team via email, WhatsApp, or the contact form.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'menu_order' => 4,
            ],

            // ─── Terms and Conditions (footer policies) ───────────────────────
            [
                'slug' => 'terms-and-conditions',
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms & Conditions',
                'body_ar' => '<p>الشروط والأحكام لاستخدام خدمة وسيط زون. يرجى الاطلاع على هذه الشروط قبل تقديم أي طلب.</p>
<p>باستخدام الموقع وخدماتنا، فإنك توافق على الالتزام بهذه الشروط. نحتفظ بحق تعديلها في أي وقت.</p>
<p><strong>للاستفسارات:</strong> <a href="mailto:info@wasetzon.com" class="text-primary-600 hover:underline">info@wasetzon.com</a></p>',
                'body_en' => '<p>Terms and conditions for using Wasetzon service. Please review these terms before placing any order.</p>
<p>By using our site and services, you agree to comply with these terms. We reserve the right to modify them at any time.</p>
<p><strong>For inquiries:</strong> <a href="mailto:info@wasetzon.com" class="text-primary-600 hover:underline">info@wasetzon.com</a></p>',
                'seo_title_ar' => 'الشروط والأحكام — وسيط زون',
                'seo_title_en' => 'Terms & Conditions — Wasetzon',
                'seo_description_ar' => 'الشروط والأحكام لاستخدام خدمة وسيط زون.',
                'seo_description_en' => 'Terms and conditions for using Wasetzon service.',
                'is_published' => true,
                'show_in_header' => false,
                'show_in_footer' => true,
                'menu_order' => 10,
            ],

            // ─── Privacy Policy (footer policies) ─────────────────────────────
            [
                'slug' => 'privacy-policy',
                'title_ar' => 'سياسة الخصوصية',
                'title_en' => 'Privacy Policy',
                'body_ar' => '<p>سياسة الخصوصية تحدد كيف نجمع ونستخدم ونحمي معلوماتك الشخصية.</p>
<p>نلتزم بحماية خصوصيتك وفقًا لأفضل الممارسات والقوانين المعمول بها. لا نبيع ولا نشارك بياناتك مع أطراف ثالثة لأغراض تسويقية.</p>
<p><strong>للاستفسارات:</strong> <a href="mailto:info@wasetzon.com" class="text-primary-600 hover:underline">info@wasetzon.com</a></p>',
                'body_en' => '<p>Our privacy policy describes how we collect, use, and protect your personal information.</p>
<p>We are committed to protecting your privacy in accordance with best practices and applicable laws. We do not sell or share your data with third parties for marketing purposes.</p>
<p><strong>For inquiries:</strong> <a href="mailto:info@wasetzon.com" class="text-primary-600 hover:underline">info@wasetzon.com</a></p>',
                'seo_title_ar' => 'سياسة الخصوصية — وسيط زون',
                'seo_title_en' => 'Privacy Policy — Wasetzon',
                'seo_description_ar' => 'سياسة الخصوصية لخدمة وسيط زون.',
                'seo_description_en' => 'Privacy policy for Wasetzon service.',
                'is_published' => true,
                'show_in_header' => false,
                'show_in_footer' => true,
                'menu_order' => 11,
            ],

            // ─── Refund Policy (footer only, not in header dropdown) ─────────
            [
                'slug' => 'refund-policy',
                'title_ar' => 'سياسة الإرجاع والاستبدال',
                'title_en' => 'Refund & Return Policy',
                'body_ar' => '<p>إن وسيط زون لا يملك أيًّا من المنتجات، وجميع الطلبات تتم بطلبٍ مخصص بناءً على اختيار العميل نفسه، ويقتصر دور وسيط زون على الشراء والشحن بالنيابة عن العميل دون أي مسؤولية عن جودة المنتج أو قابليته للإرجاع أو الاستبدال.</p>

<p>نظرًا لطبيعة الخدمة، وأن المنتجات يتم شراؤها خصيصًا بناءً على طلب العميل من متاجر خارجية، لا يمكن إرجاع أو استبدال أي منتج بعد استلامه، وذلك للأسباب التالية:</p>

<ul class="list-disc pr-6 my-4 space-y-2">
  <li>ارتفاع تكاليف الإرجاع الدولي والرسوم الجمركية مقارنة بقيمة المنتجات.</li>
  <li>عدم تحمّل البائعين الخارجيين تكاليف الشحن الدولي، مثل البائعين في أمريكا الذين لا يتحملون تكلفة إعادة الشحن من السعودية إلى أمريكا أو العكس.</li>
  <li>احتمالية رفض الجمارك إعادة إدخال الطرود أو تأخرها.</li>
  <li>انتهاء المدة القانونية المحددة للإرجاع من قبل البائع (عادةً من ٣ إلى ٧ أيام محليًا)، خصوصًا أن مدة الشحن عبر وسيط زون قد تستغرق في المتوسط ١٥ يومًا للوصول إلى السعودية، مما يؤدي غالبًا إلى انتهاء مهلة الإرجاع لدى البائع قبل استلام العميل لطلبه.</li>
</ul>

<p>يُستثنى فقط من ذلك الحالات التي يتم فيها إلغاء الطلب من المتجر قبل الشحن بسبب نفاد المنتج أو عدم توفره، حيث يمكن للعميل طلب استبداله أو استرداد المبلغ بعد خصم الرسوم والعمولات المطبقة.</p>

<p>يتم استرجاع المبالغ عبر التحويل البنكي المحلي داخل السعودية، أو عبر PayPal في حال عدم توفر حساب بنكي محلي، وذلك بعد خصم رسوم التحويل.</p>

<p>يُرجى قراءة شروط الإرجاع والاستبدال بعناية قبل إتمام الطلب، إذ يُعد تنفيذ الطلب موافقة صريحة من العميل على هذه السياسة، ولا يحق له لاحقًا المطالبة بإرجاع أو استبدال أي منتج.</p>

<p class="mt-6"><strong>للاستفسارات أو طلب المساعدة، يُرجى التواصل مع فريق الدعم عبر البريد الإلكتروني:</strong><br><a href="mailto:info@wasetzon.com" class="text-primary-600 hover:underline">info@wasetzon.com</a></p>',
                'body_en' => '<p>Wasetzon does not own any of the products. All orders are custom purchases based solely on the customer\'s own selection. Wasetzon\'s role is limited to purchasing and shipping on behalf of the customer, with no responsibility for product quality, returnability, or exchangeability.</p>

<p>Due to the nature of the service — products are purchased specifically per customer request from external stores — no product can be returned or exchanged after receipt, for the following reasons:</p>

<ul class="list-disc pl-6 my-4 space-y-2">
  <li>High international return costs and customs fees compared to product value.</li>
  <li>External sellers do not cover international shipping costs for returns.</li>
  <li>Customs may reject or delay re-entry of returned parcels.</li>
  <li>The seller\'s return window (typically 3–7 days locally) typically expires before the customer receives the shipment, as Wasetzon\'s average shipping time to Saudi Arabia is ~15 days.</li>
</ul>

<p>The only exception is when an order is cancelled by the store before shipping due to stock unavailability, in which case the customer may request a replacement or refund minus applicable fees and commissions.</p>

<p>Refunds are issued via local bank transfer within Saudi Arabia, or via PayPal if no local bank account is available, after deducting transfer fees.</p>

<p>Please read the return and exchange policy carefully before completing your order. Placing an order constitutes explicit acceptance of this policy.</p>

<p class="mt-6"><strong>For inquiries or assistance, contact our support team:</strong><br><a href="mailto:info@wasetzon.com" class="text-primary-600 hover:underline">info@wasetzon.com</a></p>',
                'seo_title_ar' => 'سياسة الإرجاع والاستبدال — وسيط زون',
                'seo_title_en' => 'Refund & Return Policy — Wasetzon',
                'seo_description_ar' => 'سياسة الإرجاع والاستبدال لخدمة وسيط زون — تعرف على شروط الإرجاع واسترداد المبالغ.',
                'seo_description_en' => 'Wasetzon\'s refund and return policy — understand the terms for returns and refunds.',
                'is_published' => true,
                'show_in_header' => false,
                'show_in_footer' => true,
                'menu_order' => 12,
            ],

            // ─── Testimonials ────────────────────────────────────────────────
            [
                'slug' => 'testimonials',
                'title_ar' => 'آراء العملاء',
                'title_en' => 'Customer Reviews',
                'body_ar' => 'testimonials-template',
                'body_en' => 'testimonials-template',
                'seo_title_ar' => 'آراء العملاء — وسيط زون',
                'seo_title_en' => 'Customer Reviews — Wasetzon',
                'seo_description_ar' => 'شاهد آراء وتجارب عملائنا الكرام مع خدمة وسيط زون.',
                'seo_description_en' => 'See real customer reviews and experiences with Wasetzon service.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'menu_order' => 5,
            ],

            // ─── Calculator ──────────────────────────────────────────────────
            [
                'slug' => 'calculator',
                'title_ar' => 'حاسبة التكلفة والعمولة',
                'title_en' => 'Cost & Commission Calculator',
                'body_ar' => 'calculator-template',
                'body_en' => 'calculator-template',
                'seo_title_ar' => 'حاسبة التكلفة والعمولة — وسيط زون',
                'seo_title_en' => 'Cost & Commission Calculator — Wasetzon',
                'seo_description_ar' => 'احسب تكلفة طلبك الإجمالية مع العمولة وسعر الصرف قبل تقديم طلبك على وسيط زون.',
                'seo_description_en' => 'Calculate your total order cost including commission and exchange rate before placing your order on Wasetzon.',
                'is_published' => true,
                'show_in_header' => false,
                'show_in_footer' => true,
                'menu_order' => 7,
            ],

            // ─── Shipping Calculator ─────────────────────────────────────────
            [
                'slug' => 'shipping-calculator',
                'title_ar' => 'حاسبة أسعار الشحن',
                'title_en' => 'Shipping Price Calculator',
                'body_ar' => 'shipping-calculator-template',
                'body_en' => 'shipping-calculator-template',
                'seo_title_ar' => 'حاسبة أسعار الشحن — وسيط زون',
                'seo_title_en' => 'Shipping Price Calculator — Wasetzon',
                'seo_description_ar' => 'احسب تكلفة الشحن الدولي من أمريكا إلى السعودية عبر أرامكس أو DHL.',
                'seo_description_en' => 'Calculate international shipping costs from the USA to Saudi Arabia via Aramex or DHL.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'menu_order' => 8,
            ],

            // ─── Membership ──────────────────────────────────────────────────
            [
                'slug' => 'membership',
                'title_ar' => 'عضوية وسيط زون',
                'title_en' => 'Wasetzon Membership',
                'body_ar' => 'membership-template',
                'body_en' => 'membership-template',
                'seo_title_ar' => 'عضوية وسيط زون — مزايا حصرية',
                'seo_title_en' => 'Wasetzon Membership — Exclusive Benefits',
                'seo_description_ar' => 'اشترك في عضوية وسيط زون واستمتع بخصومات على العمولة وأولوية في معالجة الطلبات.',
                'seo_description_en' => 'Subscribe to Wasetzon membership and enjoy commission discounts and priority order processing.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'menu_order' => 9,
            ],

            // ─── Wasetamazon → Wasetzon ──────────────────────────────────────
            [
                'slug' => 'wasetamazon-to-wasetzon',
                'title_ar' => 'وسيط أمازون أصبح وسيط زون',
                'title_en' => 'Wasetamazon Is Now Wasetzon',
                'body_ar' => '<p>عندما بدأنا «وسيط أمازون» في عام 2014، حرصنا على اختيار اسم سهل وبسيط يعكس طبيعة الخدمة، وكان الخيار الأمثل حينها هو «وسيط أمازون». وفي شهر أكتوبر 2020، تواصلت معنا شركة أمازون الأمريكية وطلبت منا التوقف عن استخدام كلمة «أمازون» في اسم موقعنا، نظرًا لامتلاكهم حقوق الملكية للاسم. وبناءً على ذلك، قمنا بتغيير اسمنا إلى «وسيط زون»، مع تحديث النطاق (رابط الموقع) وحسابات التواصل الاجتماعي الخاصة بنا.</p>

<p>كانت رحلتنا مع اسم «وسيط أمازون» مميزة من عام 2014 وحتى 2020، حيث قمنا خلالها بشراء وتوصيل عشرات الآلاف من المنتجات من أمازون ومن مختلف المتاجر ومواقع التسوق حول العالم، لعملائنا في المملكة العربية السعودية وفي العديد من الدول.</p>

<p>واليوم، يعكس اسمنا الجديد «وسيط زون» نطاق خدماتنا بشكل أدق؛ ففي عام 2014 كانت أغلب الطلبات من أمازون أمريكا، لكن مع مرور الوقت أصبحت الطلبات تأتي من مواقع ومتاجر عالمية في أوروبا، والصين، واليابان، وكوريا، وأستراليا، وكندا، وغيرها من دول العالم.</p>

<p>يمكن لجميع العملاء المسجلين في موقعنا السابق الدخول إلى موقع «وسيط زون» باستخدام نفس اسم المستخدم وكلمة المرور، وستظهر جميع طلباتهم السابقة في لوحة التحكم كما كانت.</p>

<p class="mt-8"><strong>فريق وسيط زون</strong><br><span class="text-gray-500 text-sm">(وسيط أمازون سابقًا)</span></p>',
                'body_en' => '<p>When we started «Waset Amazon» in 2014, we chose a simple name that reflected the nature of the service. In October 2020, Amazon USA contacted us and requested that we stop using the word «Amazon» in our site name, as they hold the trademark rights. Accordingly, we changed our name to «Wasetzon» and updated our domain and social media accounts.</p>

<p>Our journey under the name «Waset Amazon» from 2014 to 2020 was remarkable. We purchased and delivered tens of thousands of products from Amazon and various stores and shopping sites around the world to our customers in Saudi Arabia and many other countries.</p>

<p>Today, our new name «Wasetzon» more accurately reflects the scope of our services. In 2014, most orders came from Amazon USA, but over time orders began coming from global stores in Europe, China, Japan, Korea, Australia, Canada, and many other countries.</p>

<p>All customers registered on our previous site can log in to «Wasetzon» using the same username and password, and all their previous orders will appear in the dashboard as before.</p>

<p class="mt-8"><strong>The Wasetzon Team</strong><br><span class="text-gray-500 text-sm">(formerly Waset Amazon)</span></p>',
                'seo_title_ar' => 'وسيط أمازون أصبح وسيط زون',
                'seo_title_en' => 'Wasetamazon Is Now Wasetzon',
                'seo_description_ar' => 'قصة تغيير اسم وسيط أمازون إلى وسيط زون في عام 2020.',
                'seo_description_en' => 'The story of how Waset Amazon became Wasetzon in 2020.',
                'is_published' => true,
                'show_in_header' => false,
                'show_in_footer' => false,
                'menu_order' => 6,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
