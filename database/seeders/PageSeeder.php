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
                'slug'               => 'how-to-order',
                'title_ar'           => 'كيف تطلب؟',
                'title_en'           => 'How to Order',
                'body_ar'            => '
<div class="ordering-methods">
  <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">طرق الطلب المتاحة</h2>
  <p class="text-gray-600 text-center mb-10">نوفر لك عدة طرق سهلة ومرنة لتقديم طلبك</p>

  <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-4">الطلب عبر الموقع (الطريقة الموصى بها)</h3>
    <p class="text-gray-600 mb-4">استخدم موقعنا الإلكتروني للحصول على تجربة طلب سلسة ومنظمة</p>
    <ul class="space-y-3 mb-6">
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> <span><strong>تصفح المنتجات:</strong> استعرض جداول المنتجات والأسعار والمعلومات التفصيلية بكل سهولة</span></li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> <span><strong>أضف إلى الطلب:</strong> اختر المنتجات التي تحتاجها مباشرة من الموقع</span></li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> <span><strong>إرسال الطلب:</strong> أكمل معلومات الطلب وأرسله مباشرة</span></li>
    </ul>
    <a href="/new-order" class="inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-primary-700 transition">
      ابدأ طلبك الآن ←
    </a>
  </div>

  <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-4">الطلب عبر واتساب</h3>
    <p class="text-gray-600 mb-4">تواصل معنا مباشرة عبر واتساب</p>
    <ul class="space-y-2 mb-6">
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> أرسل لنا قائمة المنتجات التي تحتاجها</li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> يمكنك إرسال ملف Excel أو صور أو قائمة نصية</li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> سنقوم بمساعدتك في إتمام الطلب</li>
    </ul>
    <a href="https://wa.me/00966556063500?text=مرحباً، أود تقديم طلب" class="inline-flex items-center gap-2 bg-green-500 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-600 transition" target="_blank" rel="noopener">
      تواصل عبر واتساب
    </a>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 rounded-xl p-6">
    <h3 class="col-span-full text-xl font-bold text-center mb-2">لماذا نوصي بالطلب عبر الموقع؟</h3>
    <div class="bg-white p-4 rounded-lg text-center"><div class="text-3xl mb-2">📋</div><h4 class="font-bold mb-1">سهولة التصفح</h4><p class="text-gray-600 text-sm">جداول منظمة تحتوي على جميع المعلومات والأسعار</p></div>
    <div class="bg-white p-4 rounded-lg text-center"><div class="text-3xl mb-2">⏱️</div><h4 class="font-bold mb-1">توفير الوقت</h4><p class="text-gray-600 text-sm">إرسال الطلب مباشرة دون الحاجة للانتظار</p></div>
    <div class="bg-white p-4 rounded-lg text-center"><div class="text-3xl mb-2">👁️</div><h4 class="font-bold mb-1">متابعة الطلب</h4><p class="text-gray-600 text-sm">تتبع حالة طلبك بكل سهولة من لوحة التحكم</p></div>
    <div class="bg-white p-4 rounded-lg text-center"><div class="text-3xl mb-2">📊</div><h4 class="font-bold mb-1">رفع ملفات Excel</h4><p class="text-gray-600 text-sm">إمكانية رفع قائمة كاملة بالمنتجات دفعة واحدة</p></div>
  </div>

  <div class="mt-10 bg-primary-600 text-white rounded-xl p-8 text-center">
    <h3 class="text-2xl font-bold mb-2">جاهز لتقديم طلبك؟</h3>
    <p class="mb-6 opacity-90">اختر الطريقة الأنسب لك وابدأ الآن</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/new-order" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-50 transition">طلب عبر الموقع</a>
      <a href="https://wa.me/00966556063500?text=مرحباً، أود تقديم طلب" class="bg-green-500 text-white px-8 py-3 rounded-lg font-bold hover:bg-green-600 transition" target="_blank" rel="noopener">طلب عبر واتساب</a>
    </div>
  </div>
</div>',
                'body_en'            => '
<div class="ordering-methods">
  <h2 class="text-2xl font-bold text-gray-900 mb-4 text-center">Available Ordering Methods</h2>
  <p class="text-gray-600 text-center mb-10">We offer several easy and flexible ways to place your order</p>

  <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-4">Order via Website (Recommended)</h3>
    <p class="text-gray-600 mb-4">Use our website for a smooth and organized ordering experience</p>
    <ul class="space-y-3 mb-6">
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> <span><strong>Browse products:</strong> View product tables with prices and detailed information easily</span></li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> <span><strong>Add to order:</strong> Select the products you need directly from the site</span></li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> <span><strong>Submit order:</strong> Complete your order details and submit directly</span></li>
    </ul>
    <a href="/new-order" class="inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-primary-700 transition">
      Start Your Order →
    </a>
  </div>

  <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-4">Order via WhatsApp</h3>
    <p class="text-gray-600 mb-4">Contact us directly via WhatsApp</p>
    <ul class="space-y-2 mb-6">
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> Send us a list of products you need</li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> You can send an Excel file, images, or a text list</li>
      <li class="flex gap-2"><span class="text-green-600 font-bold">✓</span> We will help you complete your order</li>
    </ul>
    <a href="https://wa.me/00966556063500?text=Hello, I would like to place an order" class="inline-flex items-center gap-2 bg-green-500 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-600 transition" target="_blank" rel="noopener">
      Contact via WhatsApp
    </a>
  </div>

  <div class="mt-10 bg-primary-600 text-white rounded-xl p-8 text-center">
    <h3 class="text-2xl font-bold mb-2">Ready to Place Your Order?</h3>
    <p class="mb-6 opacity-90">Choose the most suitable method for you and start now</p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="/new-order" class="bg-white text-primary-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-50 transition">Order via Website</a>
      <a href="https://wa.me/00966556063500?text=Hello, I would like to place an order" class="bg-green-500 text-white px-8 py-3 rounded-lg font-bold hover:bg-green-600 transition" target="_blank" rel="noopener">Order via WhatsApp</a>
    </div>
  </div>
</div>',
                'seo_title_ar'       => 'كيف تطلب من وسيطزون؟',
                'seo_title_en'       => 'How to Order from Wasetzon',
                'seo_description_ar' => 'تعرف على خطوات الطلب من وسيطزون — الخدمة الأسرع لشراء المنتجات من أمريكا والعالم.',
                'seo_description_en' => 'Learn how to place an order on Wasetzon — the fastest product sourcing service from the US and worldwide.',
                'is_published'       => true,
                'show_in_header'     => true,
                'show_in_footer'     => true,
                'menu_order'         => 1,
            ],

            // ─── FAQ ────────────────────────────────────────────────────────
            [
                'slug'               => 'faq',
                'title_ar'           => 'الأسئلة الشائعة',
                'title_en'           => 'FAQ',
                'body_ar'            => 'faq-template',
                'body_en'            => 'faq-template',
                'seo_title_ar'       => 'الأسئلة الشائعة — وسيطزون',
                'seo_title_en'       => 'FAQ — Wasetzon',
                'seo_description_ar' => 'الأسئلة الشائعة والمتكررة عن أمازون ووسيط أمازون والشحن والعمولة والدفع.',
                'seo_description_en' => 'Frequently asked questions about ordering, shipping, commissions, and payment on Wasetzon.',
                'is_published'       => true,
                'show_in_header'     => false,
                'show_in_footer'     => true,
                'menu_order'         => 2,
            ],

            // ─── Payment Methods ─────────────────────────────────────────────
            [
                'slug'               => 'payment-methods',
                'title_ar'           => 'طرق الدفع',
                'title_en'           => 'Payment Methods',
                'body_ar'            => 'payment-methods-template',
                'body_en'            => 'payment-methods-template',
                'seo_title_ar'       => 'طرق الدفع — وسيطزون',
                'seo_title_en'       => 'Payment Methods — Wasetzon',
                'seo_description_ar' => 'طرق الدفع المتاحة لدى وسيطزون — تحويل بنكي عبر الراجحي والأهلي والبلاد والإنماء والسعودي الأول والسعودي للاستثمار.',
                'seo_description_en' => 'Available payment methods on Wasetzon — bank transfers via Al Rajhi, Al Ahli, Al Bilad, Al Inma, SABB, and SAIB.',
                'is_published'       => true,
                'show_in_header'     => false,
                'show_in_footer'     => true,
                'menu_order'         => 3,
            ],

            // ─── Refund Policy ───────────────────────────────────────────────
            [
                'slug'               => 'refund-policy',
                'title_ar'           => 'سياسة الإرجاع والاستبدال',
                'title_en'           => 'Refund & Return Policy',
                'body_ar'            => '<p>إن وسيط زون لا يملك أيًّا من المنتجات، وجميع الطلبات تتم بطلبٍ مخصص بناءً على اختيار العميل نفسه، ويقتصر دور وسيط زون على الشراء والشحن بالنيابة عن العميل دون أي مسؤولية عن جودة المنتج أو قابليته للإرجاع أو الاستبدال.</p>

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
                'body_en'            => '<p>Wasetzon does not own any of the products. All orders are custom purchases based solely on the customer\'s own selection. Wasetzon\'s role is limited to purchasing and shipping on behalf of the customer, with no responsibility for product quality, returnability, or exchangeability.</p>

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
                'seo_title_ar'       => 'سياسة الإرجاع والاستبدال — وسيطزون',
                'seo_title_en'       => 'Refund & Return Policy — Wasetzon',
                'seo_description_ar' => 'سياسة الإرجاع والاستبدال لخدمة وسيطزون — تعرف على شروط الإرجاع واسترداد المبالغ.',
                'seo_description_en' => 'Wasetzon\'s refund and return policy — understand the terms for returns and refunds.',
                'is_published'       => true,
                'show_in_header'     => false,
                'show_in_footer'     => true,
                'menu_order'         => 4,
            ],

            // ─── Testimonials ────────────────────────────────────────────────
            [
                'slug'               => 'testimonials',
                'title_ar'           => 'آراء العملاء',
                'title_en'           => 'Customer Reviews',
                'body_ar'            => 'testimonials-template',
                'body_en'            => 'testimonials-template',
                'seo_title_ar'       => 'آراء العملاء — وسيطزون',
                'seo_title_en'       => 'Customer Reviews — Wasetzon',
                'seo_description_ar' => 'شاهد آراء وتجارب عملائنا الكرام مع خدمة وسيطزون.',
                'seo_description_en' => 'See real customer reviews and experiences with Wasetzon service.',
                'is_published'       => true,
                'show_in_header'     => false,
                'show_in_footer'     => true,
                'menu_order'         => 5,
            ],

            // ─── Wasetamazon → Wasetzon ──────────────────────────────────────
            [
                'slug'               => 'wasetamazon-to-wasetzon',
                'title_ar'           => 'وسيط أمازون أصبح وسيط زون',
                'title_en'           => 'Wasetamazon Is Now Wasetzon',
                'body_ar'            => '<p>عندما بدأنا «وسيط أمازون» في عام 2014، حرصنا على اختيار اسم سهل وبسيط يعكس طبيعة الخدمة، وكان الخيار الأمثل حينها هو «وسيط أمازون». وفي شهر أكتوبر 2020، تواصلت معنا شركة أمازون الأمريكية وطلبت منا التوقف عن استخدام كلمة «أمازون» في اسم موقعنا، نظرًا لامتلاكهم حقوق الملكية للاسم. وبناءً على ذلك، قمنا بتغيير اسمنا إلى «وسيط زون»، مع تحديث النطاق (رابط الموقع) وحسابات التواصل الاجتماعي الخاصة بنا.</p>

<p>كانت رحلتنا مع اسم «وسيط أمازون» مميزة من عام 2014 وحتى 2020، حيث قمنا خلالها بشراء وتوصيل عشرات الآلاف من المنتجات من أمازون ومن مختلف المتاجر ومواقع التسوق حول العالم، لعملائنا في المملكة العربية السعودية وفي العديد من الدول.</p>

<p>واليوم، يعكس اسمنا الجديد «وسيط زون» نطاق خدماتنا بشكل أدق؛ ففي عام 2014 كانت أغلب الطلبات من أمازون أمريكا، لكن مع مرور الوقت أصبحت الطلبات تأتي من مواقع ومتاجر عالمية في أوروبا، والصين، واليابان، وكوريا، وأستراليا، وكندا، وغيرها من دول العالم.</p>

<p>يمكن لجميع العملاء المسجلين في موقعنا السابق الدخول إلى موقع «وسيط زون» باستخدام نفس اسم المستخدم وكلمة المرور، وستظهر جميع طلباتهم السابقة في لوحة التحكم كما كانت.</p>

<p class="mt-8"><strong>فريق وسيط زون</strong><br><span class="text-gray-500 text-sm">(وسيط أمازون سابقًا)</span></p>',
                'body_en'            => '<p>When we started «Waset Amazon» in 2014, we chose a simple name that reflected the nature of the service. In October 2020, Amazon USA contacted us and requested that we stop using the word «Amazon» in our site name, as they hold the trademark rights. Accordingly, we changed our name to «Wasetzon» and updated our domain and social media accounts.</p>

<p>Our journey under the name «Waset Amazon» from 2014 to 2020 was remarkable. We purchased and delivered tens of thousands of products from Amazon and various stores and shopping sites around the world to our customers in Saudi Arabia and many other countries.</p>

<p>Today, our new name «Wasetzon» more accurately reflects the scope of our services. In 2014, most orders came from Amazon USA, but over time orders began coming from global stores in Europe, China, Japan, Korea, Australia, Canada, and many other countries.</p>

<p>All customers registered on our previous site can log in to «Wasetzon» using the same username and password, and all their previous orders will appear in the dashboard as before.</p>

<p class="mt-8"><strong>The Wasetzon Team</strong><br><span class="text-gray-500 text-sm">(formerly Waset Amazon)</span></p>',
                'seo_title_ar'       => 'وسيط أمازون أصبح وسيط زون',
                'seo_title_en'       => 'Wasetamazon Is Now Wasetzon',
                'seo_description_ar' => 'قصة تغيير اسم وسيط أمازون إلى وسيط زون في عام 2020.',
                'seo_description_en' => 'The story of how Waset Amazon became Wasetzon in 2020.',
                'is_published'       => true,
                'show_in_header'     => false,
                'show_in_footer'     => false,
                'menu_order'         => 6,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
