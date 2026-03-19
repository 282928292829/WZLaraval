<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SeedOrderDevComments extends Command
{
    protected $signature = 'orders:seed-dev-comments {order : Order ID or order number}';

    protected $description = 'Add 20 fake customer/staff comments to an order (local dev only, for testing the order comments UI)';

    public function handle(): int
    {
        if (config('app.env') !== 'local') {
            $this->error('This command is only available in local environment.');

            return 1;
        }

        $identifier = $this->argument('order');
        $order = is_numeric($identifier)
            ? Order::with('user')->find($identifier)
            : Order::with('user')->where('order_number', $identifier)->first();

        if (! $order) {
            $this->error("Order not found: {$identifier}");

            return 1;
        }

        $customer = $order->user;
        $staff = User::staff()->first() ?? $customer;

        $messages = [
            'Hi, I just placed this order. Please confirm you received it.',
            'Order received! We will start processing within 24 hours.',
            'Can you check if these items are in stock?',
            'All items are in stock. We will proceed with the purchase.',
            'I need this by next week if possible.',
            'We will do our best to meet your deadline.',
            'Please use the exact colors I specified.',
            'Noted on the colors. We will match exactly.',
            'Is there any discount available for bulk order?',
            'Let me check with the team and get back to you.',
            'I added one more item - the blue one. Thanks!',
            'Got it! The blue item has been added.',
            'When will you start processing?',
            'Processing has started. You will get updates soon.',
            'I sent the payment via bank transfer. Reference: TXN123.',
            'Payment received. Thank you! Order is now confirmed.',
            'Can you combine shipping with my previous order?',
            'We can combine shipping. I will merge the orders.',
            'Please pack carefully - these are fragile.',
            'We use premium packaging for fragile items.',
        ];

        $withImage = [4, 8, 12, 16, 20];
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        for ($i = 0; $i < 20; $i++) {
            $isCustomer = ($i % 2) === 0;
            $author = $isCustomer ? $customer : $staff;
            $body = $messages[$i % count($messages)];

            $comment = OrderComment::create([
                'order_id' => $order->id,
                'user_id' => $author->id,
                'body' => $body,
                'is_internal' => false,
            ]);

            if (in_array($i + 1, $withImage, true) && $png !== false) {
                $path = "orders/{$order->id}/comment-{$comment->id}-".uniqid().'.png';
                Storage::disk('public')->put($path, $png);
                OrderFile::create([
                    'order_id' => $order->id,
                    'user_id' => $author->id,
                    'comment_id' => $comment->id,
                    'path' => $path,
                    'original_name' => 'attachment.png',
                    'mime_type' => 'image/png',
                    'size' => 100,
                    'type' => 'comment',
                ]);
            }
        }

        $this->info("Added 20 dev comments to order #{$order->order_number}.");

        return 0;
    }
}
