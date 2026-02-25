<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateSampleOrders extends Command
{
    protected $signature = 'orders:create-sample {--count=3 : Number of orders} {--items=4 : Items per order}';

    protected $description = 'Create sample orders with URLs and product images';

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

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $itemsPerOrder = (int) $this->option('items');

        $user = User::where('email', 'customer@wasetzon.test')->first();
        if (! $user) {
            $user = User::query()->whereHas('roles')->first();
        }
        if (! $user) {
            $this->error('No user found. Run db:seed first.');

            return 1;
        }

        $maxNum = 900000;
        if (DB::getDriverName() === 'mysql') {
            $maxNum = max($maxNum, (int) Order::query()->whereRaw("order_number REGEXP '^[0-9]+$'")->max(DB::raw('CAST(order_number AS UNSIGNED)')));
        }

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $maxNum++;
            $order = $this->createOrder($user, (string) $maxNum);
            $this->createItems($order, $itemsPerOrder);
            $created++;
        }

        $this->info("Created {$created} orders with {$itemsPerOrder} items each (all with images).");

        return 0;
    }

    private function createOrder(User $user, string $orderNumber): Order
    {
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'status' => 'pending',
            'layout_option' => 1,
            'notes' => null,
            'subtotal' => 0,
            'total_amount' => 0,
            'currency' => 'SAR',
        ]);

        OrderTimeline::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => 'status_change',
            'status_to' => 'pending',
        ]);

        OrderComment::create([
            'order_id' => $order->id,
            'user_id' => null,
            'body' => __('orders.auto_comment_no_price'),
            'is_system' => true,
        ]);

        return $order;
    }

    private function createItems(Order $order, int $count): void
    {
        $urls = array_slice(
            array_merge(self::PRODUCT_URLS, self::PRODUCT_URLS),
            $order->id % 10,
            $count
        );
        $urls = array_slice(array_values(array_unique($urls)), 0, $count);
        while (count($urls) < $count) {
            $urls[] = self::PRODUCT_URLS[count($urls) % count(self::PRODUCT_URLS)];
        }
        $urls = array_slice($urls, 0, $count);

        foreach ($urls as $i => $url) {
            $qty = 1 + ($i % 3);
            $price = 10 + ($i * 15) + ($order->id % 20);

            $item = OrderItem::create([
                'order_id' => $order->id,
                'url' => $url,
                'is_url' => true,
                'qty' => $qty,
                'color' => ['Red', 'Blue', 'Black', 'White'][$i % 4],
                'size' => ['S', 'M', 'L', 'XL'][$i % 4],
                'notes' => null,
                'currency' => 'SAR',
                'unit_price' => $price,
                'sort_order' => $i,
            ]);

            $path = $this->storePlaceholderImage($order->id, "product-{$item->id}");
            if ($path) {
                $item->update(['image_path' => $path]);
                OrderFile::create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'path' => $path,
                    'original_name' => 'product-image.png',
                    'mime_type' => 'image/png',
                    'size' => 100,
                    'type' => 'product_image',
                ]);
            }
        }

        $subtotal = $order->items()->get()->sum(fn ($i) => (float) ($i->unit_price ?? 0) * $i->qty);
        $commission = \App\Services\CommissionCalculator::calculate($subtotal);
        $order->update([
            'subtotal' => round($subtotal, 2),
            'total_amount' => round($subtotal + $commission, 2),
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
