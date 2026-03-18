<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── General (branding, site name) ──────────────────────────────────────
        Setting::set('site_name', 'Wasetzon', 'string', 'general');
        Setting::set('site_name_ar', 'وسيط زون', 'string', 'general');

        // ── Contact / Footer ──────────────────────────────────────────────────
        Setting::set('whatsapp', '966556063500', 'string', 'contact');
        Setting::set('contact_email', 'info@wasetzon.com', 'string', 'contact');

        // ── Payment (bank accounts for payment-methods page) ────────────────────
        // Source: wasetzon.com/payment-methods/
        Setting::set('payment_company_name', 'مؤسسة جسور الاستيراد للتجارة', 'string', 'payment');
        Setting::set('payment_banks', [
            [
                'name' => 'Al Rajhi Bank',
                'logo' => '/images/banks/rajhi.svg',
                'account' => '624608010055610',
                'iban' => 'SA4180000624608010055610',
                'beneficiary' => '',
            ],
            [
                'name' => 'SNB AlAhli',
                'logo' => '/images/banks/snb.svg',
                'account' => '26561106000110',
                'iban' => 'SA9710000026561106000110',
                'beneficiary' => '',
            ],
            [
                'name' => 'Bank Albilad',
                'logo' => '/images/banks/albilad.svg',
                'account' => '436117332070002',
                'iban' => 'SA9315000436117332070002',
                'beneficiary' => '',
            ],
            [
                'name' => 'Alinma Bank',
                'logo' => '/images/banks/alinma.svg',
                'account' => '68222222010000',
                'iban' => 'SA8905000068222222010000',
                'beneficiary' => '',
            ],
            [
                'name' => 'SAB (Saudi Awwal Bank)',
                'logo' => '/images/banks/sab.svg',
                'account' => '611065905001',
                'iban' => 'SA8345000000611065905001',
                'beneficiary' => '',
            ],
            [
                'name' => 'SAIB (Saudi Investment Bank)',
                'logo' => '/images/banks/saib.svg',
                'account' => '0128605051001',
                'iban' => 'SA4465000000128605051001',
                'beneficiary' => '',
            ],
            [
                'name' => 'Riyad Bank',
                'logo' => '/images/banks/riyad.svg',
                'account' => '3374435439940',
                'iban' => 'SA6120000003374435439940',
                'beneficiary' => '',
            ],
        ], 'json', 'payment');
        Setting::set('commercial_registration', '', 'string', 'contact');
        Setting::set('show_partners', '1', 'boolean', 'certification');

        // ── Hero Section ──────────────────────────────────────────────────────
        Setting::set('hero_title', '', 'string', 'hero');
        Setting::set('hero_subtitle', '', 'string', 'hero');
        Setting::set('hero_input_placeholder', '', 'string', 'hero');
        Setting::set('hero_button_text', '', 'string', 'hero');
        Setting::set('hero_show_whatsapp', '1', 'boolean', 'hero');
        Setting::set('hero_whatsapp_button_text', '', 'string', 'hero');
        Setting::set('hero_whatsapp_number', '', 'string', 'hero');
        Setting::set('hero_show_name_change_notice', '1', 'boolean', 'hero');
        Setting::set('hero_input_required', '0', 'boolean', 'hero');

        // ── Default order form field configuration ────────────────────────────
        // sort_order  — display order (1 = first)
        // optional    — true = collapsed under "show more" toggle on mobile
        // enabled     — false = hidden entirely from the form
        Setting::set('order_form_fields', [
            [
                'key' => 'url',
                'label_ar' => 'رابط المنتج أو وصفه',
                'label_en' => 'Product URL or Description',
                'sort_order' => 1,
                'optional' => false,
                'enabled' => true,
            ],
            [
                'key' => 'qty',
                'label_ar' => 'الكمية',
                'label_en' => 'Qty',
                'sort_order' => 2,
                'optional' => false,
                'enabled' => true,
            ],
            [
                'key' => 'size',
                'label_ar' => 'المقاس',
                'label_en' => 'Size',
                'sort_order' => 3,
                'optional' => false,
                'enabled' => true,
            ],
            [
                'key' => 'color',
                'label_ar' => 'اللون',
                'label_en' => 'Color',
                'sort_order' => 4,
                'optional' => false,
                'enabled' => true,
            ],
            [
                'key' => 'price',
                'label_ar' => 'السعر',
                'label_en' => 'Price',
                'sort_order' => 5,
                'optional' => false,
                'enabled' => true,
            ],
            [
                'key' => 'currency',
                'label_ar' => 'العملة',
                'label_en' => 'Currency',
                'sort_order' => 6,
                'optional' => false,
                'enabled' => true,
            ],
            [
                'key' => 'notes',
                'label_ar' => 'ملاحظات',
                'label_en' => 'Notes',
                'sort_order' => 7,
                'optional' => true,
                'enabled' => true,
            ],
            [
                'key' => 'file',
                'label_ar' => 'ملف/صورة',
                'label_en' => 'File / Image',
                'sort_order' => 8,
                'optional' => true,
                'enabled' => true,
            ],
        ], 'json', 'orders');

        // ── Order form layout (hybrid, table, cards, wizard, cart) ──
        Setting::set('order_new_layout', 'hybrid', 'string', 'orders');

        // ── Order form dev helpers ────────────────────────────────────────────
        Setting::set('order_form_show_add_test_items', true, 'boolean', 'orders');

        // ── Image cleanup (super admin only) ──────────────────────────────────
        Setting::set('image_cleanup_statuses', ['cancelled'], 'json', 'image_cleanup');
        Setting::set('image_cleanup_retention_days_customer_product', 14, 'integer', 'image_cleanup');
        Setting::set('image_cleanup_retention_days_staff_product', 90, 'integer', 'image_cleanup');
        Setting::set('image_cleanup_retention_days_customer_comment', 14, 'integer', 'image_cleanup');
        Setting::set('image_cleanup_retention_days_staff_comment', 90, 'integer', 'image_cleanup');
        Setting::set('image_cleanup_action', 'delete', 'string', 'image_cleanup'); // delete | compress
        Setting::set('image_cleanup_compression_quality', 55, 'integer', 'image_cleanup'); // 55 = Aggressive
        Setting::set('image_cleanup_customer_product', true, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_staff_product', false, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_customer_comment', true, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_staff_comment', false, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_receipt', false, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_invoice', false, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_other', false, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_schedule_enabled', false, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_schedule_frequency', 'daily', 'string', 'image_cleanup'); // daily | weekly
        Setting::set('image_cleanup_schedule_hour', 2, 'integer', 'image_cleanup'); // 0-23
        Setting::set('image_cleanup_schedule_day', 0, 'integer', 'image_cleanup'); // 0=Sunday for weekly
    }
}
