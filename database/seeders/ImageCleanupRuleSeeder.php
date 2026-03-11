<?php

namespace Database\Seeders;

use App\Models\ImageCleanupRule;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ImageCleanupRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (ImageCleanupRule::exists()) {
            return;
        }

        $statuses = Setting::get('image_cleanup_statuses', ['cancelled']);
        if (! is_array($statuses)) {
            $statuses = ['cancelled'];
        }

        $retentionCustomerProduct = (int) Setting::get('image_cleanup_retention_days_customer_product', 14);
        $retentionStaffProduct = (int) Setting::get('image_cleanup_retention_days_staff_product', 90);
        $retentionCustomerComment = (int) Setting::get('image_cleanup_retention_days_customer_comment', 14);
        $retentionStaffComment = (int) Setting::get('image_cleanup_retention_days_staff_comment', 90);

        $customerProduct = (bool) Setting::get('image_cleanup_customer_product', true);
        $staffProduct = (bool) Setting::get('image_cleanup_staff_product', false);
        $customerComment = (bool) Setting::get('image_cleanup_customer_comment', true);
        $staffComment = (bool) Setting::get('image_cleanup_staff_comment', false);
        $receipt = (bool) Setting::get('image_cleanup_receipt', false);
        $invoice = (bool) Setting::get('image_cleanup_invoice', false);
        $other = (bool) Setting::get('image_cleanup_other', false);

        $compressionQuality = (int) Setting::get('image_cleanup_compression_quality', 55);
        $compressionQuality = max(20, min(95, $compressionQuality));

        $scheduleEnabled = (bool) Setting::get('image_cleanup_schedule_enabled', false);
        $scheduleFrequency = Setting::get('image_cleanup_schedule_frequency', 'daily');
        $scheduleHour = (int) Setting::get('image_cleanup_schedule_hour', 2);
        $scheduleDay = (int) Setting::get('image_cleanup_schedule_day', 0);

        $base = [
            'statuses' => $statuses,
            'retention_days_customer_product' => $retentionCustomerProduct,
            'retention_days_staff_product' => $retentionStaffProduct,
            'retention_days_customer_comment' => $retentionCustomerComment,
            'retention_days_staff_comment' => $retentionStaffComment,
            'customer_product' => $customerProduct,
            'staff_product' => $staffProduct,
            'customer_comment' => $customerComment,
            'staff_comment' => $staffComment,
            'receipt' => $receipt,
            'invoice' => $invoice,
            'other' => $other,
            'is_active' => true,
            'sort_order' => 0,
        ];

        ImageCleanupRule::create(array_merge($base, [
            'rule_type' => ImageCleanupRule::TYPE_DELETE,
        ]));

        ImageCleanupRule::create(array_merge($base, [
            'rule_type' => ImageCleanupRule::TYPE_COMPRESS,
            'compression_quality' => $compressionQuality,
        ]));

        Setting::set('image_cleanup_delete_schedule_enabled', $scheduleEnabled, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_delete_schedule_frequency', $scheduleFrequency, 'string', 'image_cleanup');
        Setting::set('image_cleanup_delete_schedule_hour', $scheduleHour, 'integer', 'image_cleanup');
        Setting::set('image_cleanup_delete_schedule_day', $scheduleDay, 'integer', 'image_cleanup');

        Setting::set('image_cleanup_compress_schedule_enabled', $scheduleEnabled, 'boolean', 'image_cleanup');
        Setting::set('image_cleanup_compress_schedule_frequency', $scheduleFrequency, 'string', 'image_cleanup');
        Setting::set('image_cleanup_compress_schedule_hour', $scheduleHour, 'integer', 'image_cleanup');
        Setting::set('image_cleanup_compress_schedule_day', $scheduleDay, 'integer', 'image_cleanup');
    }
}
