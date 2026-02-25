<?php

namespace Database\Seeders;

use App\Models\ShippingCompany;
use Illuminate\Database\Seeder;

class ShippingCompanySeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            [
                'slug' => 'aramex',
                'name_ar' => 'أرامكس',
                'name_en' => 'Aramex',
                'note_ar' => 'اقتصادي',
                'note_en' => 'Economy',
                'icon' => '📦',
                'first_half_kg' => 119,
                'rest_half_kg' => 39,
                'over21_per_kg' => 59,
                'delivery_days' => '7-10',
                'tracking_url_template' => 'https://www.aramex.com/track/results?mode=0&ShipmentNumber={tracking}',
                'sort_order' => 1,
            ],
            [
                'slug' => 'dhl',
                'name_ar' => 'دي إتش إل',
                'name_en' => 'DHL',
                'note_ar' => 'سريع',
                'note_en' => 'Express',
                'icon' => '🚀',
                'first_half_kg' => 169,
                'rest_half_kg' => 43,
                'over21_per_kg' => 63,
                'delivery_days' => '7-10',
                'tracking_url_template' => 'https://www.dhl.com/sa-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}',
                'sort_order' => 2,
            ],
            [
                'slug' => 'domestic',
                'name_ar' => 'شحن داخلي',
                'name_en' => 'US Domestic',
                'note_ar' => 'داخل أمريكا',
                'note_en' => 'Within USA',
                'icon' => '🏠',
                'first_half_kg' => 69,
                'rest_half_kg' => 19,
                'over21_per_kg' => null,
                'delivery_days' => '4-7',
                'tracking_url_template' => null,
                'sort_order' => 3,
            ],
            [
                'slug' => 'smsa',
                'name_ar' => 'إس إم إس إيه',
                'name_en' => 'SMSA',
                'note_ar' => null,
                'note_en' => null,
                'icon' => '📦',
                'first_half_kg' => null,
                'rest_half_kg' => null,
                'over21_per_kg' => null,
                'delivery_days' => null,
                'tracking_url_template' => 'https://www.smsaexpress.com/track/?tracknumbers={tracking}',
                'sort_order' => 4,
            ],
            [
                'slug' => 'fedex',
                'name_ar' => 'فيديكس',
                'name_en' => 'FedEx',
                'note_ar' => null,
                'note_en' => null,
                'icon' => '📦',
                'first_half_kg' => null,
                'rest_half_kg' => null,
                'over21_per_kg' => null,
                'delivery_days' => null,
                'tracking_url_template' => 'https://www.fedex.com/fedextrack/?trknbr={tracking}',
                'sort_order' => 5,
            ],
            [
                'slug' => 'ups',
                'name_ar' => 'يو بي إس',
                'name_en' => 'UPS',
                'note_ar' => null,
                'note_en' => null,
                'icon' => '📦',
                'first_half_kg' => null,
                'rest_half_kg' => null,
                'over21_per_kg' => null,
                'delivery_days' => null,
                'tracking_url_template' => 'https://www.ups.com/track?tracknum={tracking}',
                'sort_order' => 6,
            ],
        ];

        foreach ($carriers as $data) {
            ShippingCompany::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
