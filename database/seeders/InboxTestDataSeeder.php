<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class InboxTestDataSeeder extends Seeder
{
    /**
     * Creates 5 orders and many comments with Activity records for inbox testing.
     */
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command->error('No user found. Run UserSeeder first.');

            return;
        }

        $commentBodies = [
            'Can you confirm the product is in stock?',
            'I have sent the payment. Please check.',
            'When will this ship?',
            'I need to add one more item to this order.',
            'Thank you for the update!',
            'Please use DHL for faster delivery.',
            'I updated my shipping address.',
            'Is there any discount available?',
            'Order received, thank you!',
            'Can I get an invoice for this order?',
        ];

        for ($i = 1; $i <= 5; $i++) {
            $order = Order::create([
                'order_number' => 'TEST-'.str_pad((string) (Order::count() + 1), 6, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'status' => 'pending',
                'layout_option' => '1',
                'subtotal' => 100,
                'total_amount' => 150,
                'currency' => 'SAR',
            ]);

            Activity::create([
                'type' => 'new_order',
                'subject_type' => Order::class,
                'subject_id' => $order->id,
                'causer_id' => $user->id,
                'data' => ['order_number' => $order->order_number, 'note' => null],
                'created_at' => now()->subMinutes($i * 10),
            ]);

            $numComments = random_int(2, 5);
            for ($j = 0; $j < $numComments; $j++) {
                $body = $commentBodies[array_rand($commentBodies)];
                $comment = $order->comments()->create([
                    'user_id' => $user->id,
                    'body' => $body,
                    'is_internal' => false,
                ]);

                Activity::create([
                    'type' => 'comment',
                    'subject_type' => Order::class,
                    'subject_id' => $order->id,
                    'causer_id' => $user->id,
                    'data' => [
                        'order_number' => $order->order_number,
                        'note' => \Illuminate\Support\Str::limit($body, 100),
                    ],
                    'created_at' => now()->subMinutes($i * 10 + $j * 2),
                ]);
            }
        }

        $this->command->info('Created 5 orders with comments. Visit /inbox to see them.');
    }
}
