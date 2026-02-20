<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Contact / Footer ──────────────────────────────────────────────────
        Setting::set('whatsapp',                '966500000000',              'string', 'contact');
        Setting::set('contact_email',           'info@wasetzon.com',         'string', 'contact');
        Setting::set('commercial_registration', '1234567890',                'string', 'contact');
        Setting::set('show_partners',           '1',                         'boolean', 'general');

        // ── Default order form field configuration ────────────────────────────
        // sort_order  — display order (1 = first)
        // optional    — true = collapsed under "show more" toggle on mobile
        // enabled     — false = hidden entirely from the form
        Setting::set('order_form_fields', [
            [
                'key'        => 'url',
                'label_ar'   => 'رابط المنتج أو وصفه',
                'label_en'   => 'Product URL or Description',
                'sort_order' => 1,
                'optional'   => false,
                'enabled'    => true,
            ],
            [
                'key'        => 'qty',
                'label_ar'   => 'الكمية',
                'label_en'   => 'Qty',
                'sort_order' => 2,
                'optional'   => false,
                'enabled'    => true,
            ],
            [
                'key'        => 'size',
                'label_ar'   => 'المقاس',
                'label_en'   => 'Size',
                'sort_order' => 3,
                'optional'   => false,
                'enabled'    => true,
            ],
            [
                'key'        => 'color',
                'label_ar'   => 'اللون',
                'label_en'   => 'Color',
                'sort_order' => 4,
                'optional'   => false,
                'enabled'    => true,
            ],
            [
                'key'        => 'price',
                'label_ar'   => 'السعر',
                'label_en'   => 'Price',
                'sort_order' => 5,
                'optional'   => false,
                'enabled'    => true,
            ],
            [
                'key'        => 'currency',
                'label_ar'   => 'العملة',
                'label_en'   => 'Currency',
                'sort_order' => 6,
                'optional'   => false,
                'enabled'    => true,
            ],
            [
                'key'        => 'notes',
                'label_ar'   => 'ملاحظات',
                'label_en'   => 'Notes',
                'sort_order' => 7,
                'optional'   => true,
                'enabled'    => true,
            ],
            [
                'key'        => 'file',
                'label_ar'   => 'ملف/صورة',
                'label_en'   => 'File / Image',
                'sort_order' => 8,
                'optional'   => true,
                'enabled'    => true,
            ],
        ], 'json', 'orders');
    }
}
