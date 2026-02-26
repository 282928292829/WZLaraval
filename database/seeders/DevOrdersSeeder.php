<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DevOrdersSeeder extends Seeder
{
    private const PRODUCT_URLS = [
        'https://www.amazon.com/dp/B0BSHF7LLL',
        'https://www.amazon.com/dp/B09V3KXJPB',
        'https://www.ebay.com/itm/123456789',
        'https://www.walmart.com/ip/987654321',
        'https://www.target.com/p/product-123',
        'https://www.bestbuy.com/site/product/456',
        'https://www.nike.com/t/air-max-90',
        'https://www.adidas.com/us/shoes',
        'https://www.apple.com/shop/product/iphone',
        'https://www.samsung.com/us/mobile/galaxy',
    ];

    private const COLORS = [
        'Red', 'Blue', 'Black', 'White', 'Navy', 'Gray', 'Green', 'Brown',
        'Beige', 'Pink', 'Purple', 'Orange', 'Yellow', 'Maroon', 'Teal',
    ];

    private const SIZES = [
        'S', 'M', 'L', 'XL', 'XXL', '32', '34', '36', '38', '40',
        '42', 'US 8', 'US 10', 'One Size', 'Small', 'Medium', 'Large',
    ];

    private const CURRENCIES = ['USD', 'EUR', 'GBP', 'SAR'];

    private const CUSTOMER_MESSAGES = [
        'Hi, I just placed this order. Please confirm you received it.',
        'Can you check if these items are in stock?',
        'I need this by next week if possible.',
        'Please use the exact colors I specified.',
        'Is there any discount available for bulk order?',
        'I added one more item - the blue one. Thanks!',
        'When will you start processing?',
        'I sent the payment via bank transfer. Reference: TXN123.',
        'Can you combine shipping with my previous order?',
        'Please pack carefully - these are fragile.',
    ];

    private const STAFF_REPLIES = [
        'Order received! We will start processing within 24 hours.',
        'All items are in stock. We will proceed with the purchase.',
        'We will do our best to meet your deadline.',
        'Noted on the colors. We will match exactly.',
        'Let me check with the team and get back to you.',
        'Got it! The blue item has been added.',
        'Processing has started. You will get updates soon.',
        'Payment received. Thank you! Order is now confirmed.',
        'We can combine shipping. I will merge the orders.',
        'We use premium packaging for fragile items.',
    ];

    private const INTERNAL_NOTES = [
        'Customer is VIP - prioritize this order.',
        'Check stock before purchasing.',
        'Customer requested express shipping.',
        'Merge with order #12345 if possible.',
        'Payment verified - proceed.',
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->warn('DevOrdersSeeder only runs in local environment.');

            return;
        }

        $users = User::whereIn('email', [
            'customer@wasetzon.test',
            'editor@wasetzon.test',
            'admin@wasetzon.test',
            'superadmin@wasetzon.test',
        ])->get()->keyBy('email');

        if ($users->isEmpty()) {
            $this->command->error('Dev users not found. Run: php artisan db:seed --class=RoleAndPermissionSeeder');

            return;
        }

        $customer = $users->get('customer@wasetzon.test');
        $staffUser = $users->get('editor@wasetzon.test');
        $admin = $users->get('admin@wasetzon.test');
        $staff = $staffUser ?? $admin ?? $users->first();

        $maxNum = 900000;
        if (DB::getDriverName() === 'mysql') {
            $maxNum = max($maxNum, (int) Order::query()->whereRaw("order_number REGEXP '^[0-9]+$'")->max(DB::raw('CAST(order_number AS UNSIGNED)')));
        }

        $created = 0;

        foreach ($users as $email => $user) {
            for ($o = 0; $o < 5; $o++) {
                $maxNum++;
                $order = $this->createOrder($user, (string) $maxNum);
                $this->createItems($order, 5);
                $this->createComments($order, $user, $staff, 15);
                $created++;
            }
        }

        $this->command->info("Created {$created} dev orders with items and comments.");
    }

    private function createOrder(User $user, string $orderNumber): Order
    {
        $statuses = ['pending', 'needs_payment', 'processing', 'purchasing', 'shipped', 'delivered', 'completed'];
        $status = fake()->randomElement($statuses);

        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'status' => $status,
            'layout_option' => 1,
            'notes' => fake()->optional(0.6)->sentence(),
            'subtotal' => fake()->randomFloat(2, 100, 2000),
            'total_amount' => fake()->randomFloat(2, 150, 2500),
            'currency' => 'SAR',
        ]);

        OrderTimeline::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => 'status_change',
            'status_to' => $status,
        ]);

        return $order;
    }

    private function createItems(Order $order, int $count): void
    {
        $urls = fake()->randomElements(self::PRODUCT_URLS, min($count, count(self::PRODUCT_URLS)));
        $colors = fake()->randomElements(self::COLORS, $count, true);
        $sizes = fake()->randomElements(self::SIZES, $count, true);
        $currencies = fake()->randomElements(self::CURRENCIES, $count, true);

        foreach (range(0, $count - 1) as $i) {
            $url = $urls[$i] ?? fake()->url();
            $qty = fake()->numberBetween(1, 3);
            $price = fake()->randomFloat(2, 10, 150);

            OrderItem::create([
                'order_id' => $order->id,
                'url' => $url,
                'is_url' => str_starts_with($url, 'http'),
                'qty' => $qty,
                'color' => $colors[$i] ?? null,
                'size' => $sizes[$i] ?? null,
                'notes' => fake()->optional(0.5)->sentence(),
                'currency' => $currencies[$i] ?? 'USD',
                'unit_price' => $price,
                'sort_order' => $i,
            ]);
        }

        $this->maybeAddProductImage($order);
    }

    private function createComments(Order $order, User $customer, User $staff, int $count): void
    {
        $messages = array_merge(self::CUSTOMER_MESSAGES, self::STAFF_REPLIES);
        $commentBodies = fake()->randomElements($messages, min($count, count($messages)), true);

        $wa = \App\Models\Setting::get('whatsapp', '');
        $systemBody = __('orders.auto_comment_no_price', ['whatsapp' => $wa ?: '-']);
        OrderComment::create([
            'order_id' => $order->id,
            'user_id' => null,
            'body' => $systemBody,
            'is_system' => true,
        ]);

        $created = 1;
        $withImage = [3, 7, 12];

        foreach (array_slice($commentBodies, 0, $count - 1) as $i => $body) {
            $isCustomer = $created % 3 !== 2;
            $author = $isCustomer ? $customer : $staff;
            $isInternal = ! $isCustomer && fake()->boolean(0.2);

            $comment = OrderComment::create([
                'order_id' => $order->id,
                'user_id' => $author->id,
                'body' => $body,
                'is_internal' => $isInternal,
            ]);

            if (in_array($created, $withImage, true)) {
                $this->attachCommentImage($order, $comment, $author);
            }

            $created++;
        }
    }

    private function maybeAddProductImage(Order $order): void
    {
        $item = $order->items()->first();
        if (! $item) {
            return;
        }

        $path = $this->storePlaceholderImage($order->id, 'product');
        if ($path) {
            $item->update(['image_path' => $path]);
            OrderFile::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'path' => $path,
                'original_name' => 'product-sample.png',
                'mime_type' => 'image/png',
                'size' => 100,
                'type' => 'product_image',
            ]);
        }
    }

    private function attachCommentImage(Order $order, OrderComment $comment, User $user): void
    {
        $path = $this->storePlaceholderImage($order->id, "comment-{$comment->id}");
        if (! $path) {
            return;
        }

        $type = 'comment';
        if (DB::getDriverName() !== 'mysql') {
            $type = 'other';
        }

        OrderFile::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'comment_id' => $comment->id,
            'path' => $path,
            'original_name' => 'attachment.png',
            'mime_type' => 'image/png',
            'size' => 100,
            'type' => $type,
        ]);
    }

    private function storePlaceholderImage(int $orderId, string $suffix): ?string
    {
        $dir = "orders/{$orderId}";
        $filename = "{$suffix}-".uniqid().'.png';

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        if ($png === false) {
            return null;
        }

        $path = "{$dir}/{$filename}";
        Storage::disk('public')->put($path, $png);

        return $path;
    }
}
