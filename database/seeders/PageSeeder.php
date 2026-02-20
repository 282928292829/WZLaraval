<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug'                => 'how-to-order',
                'title_ar'            => 'كيف تطلب؟',
                'title_en'            => 'How to Order',
                'body_ar'             => '<h2>خطوات الطلب</h2>
<ol>
<li>أضف رابط المنتج أو وصفه في نموذج الطلب.</li>
<li>حدد الكمية والمواصفات (المقاس، اللون، الملاحظات).</li>
<li>أرسل الطلب وانتظر عرض السعر من فريقنا.</li>
<li>بعد الموافقة، أرسل إيصال الدفع لاستكمال الطلب.</li>
</ol>
<p>للاستفسار تواصل معنا عبر واتساب.</p>',
                'body_en'             => '<h2>Order Steps</h2>
<ol>
<li>Add the product URL or description in the order form.</li>
<li>Specify quantity and specs (size, color, notes).</li>
<li>Submit and wait for a price quote from our team.</li>
<li>After approval, send your payment receipt to complete the order.</li>
</ol>
<p>For inquiries, contact us via WhatsApp.</p>',
                'seo_title_ar'        => 'كيف تطلب من وسيطزون؟',
                'seo_title_en'        => 'How to Order from Wasetzon',
                'seo_description_ar'  => 'تعرف على خطوات الطلب من وسيطزون — الخدمة الأسرع لشراء المنتجات.',
                'seo_description_en'  => 'Learn how to place an order on Wasetzon — the fastest product sourcing service.',
                'is_published'        => true,
                'show_in_header'      => true,
                'show_in_footer'      => true,
                'menu_order'          => 1,
            ],
            [
                'slug'                => 'faq',
                'title_ar'            => 'الأسئلة الشائعة',
                'title_en'            => 'FAQ',
                'body_ar'             => '<h2>أسئلة شائعة</h2>
<p>سيتم إضافة الأسئلة الشائعة قريباً.</p>',
                'body_en'             => '<h2>Frequently Asked Questions</h2>
<p>FAQ content coming soon.</p>',
                'seo_title_ar'        => 'الأسئلة الشائعة — وسيطزون',
                'seo_title_en'        => 'FAQ — Wasetzon',
                'seo_description_ar'  => 'إجابات على أكثر الأسئلة شيوعاً حول خدمات وسيطزون.',
                'seo_description_en'  => 'Answers to the most common questions about Wasetzon services.',
                'is_published'        => true,
                'show_in_header'      => false,
                'show_in_footer'      => true,
                'menu_order'          => 2,
            ],
            [
                'slug'                => 'payment-methods',
                'title_ar'            => 'طرق الدفع',
                'title_en'            => 'Payment Methods',
                'body_ar'             => '<p>نقبل التحويل البنكي وبعض المحافظ الإلكترونية. تواصل معنا للتفاصيل.</p>',
                'body_en'             => '<p>We accept bank transfers and select digital wallets. Contact us for details.</p>',
                'seo_title_ar'        => null,
                'seo_title_en'        => null,
                'seo_description_ar'  => null,
                'seo_description_en'  => null,
                'is_published'        => true,
                'show_in_header'      => false,
                'show_in_footer'      => true,
                'menu_order'          => 3,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
