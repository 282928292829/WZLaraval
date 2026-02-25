<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderTimeline;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateTestOrdersWithStatus extends Command
{
    protected $signature = 'orders:create-test-pending {--count=5 : Number of orders} {--email=customer@wasetzon.test : User email}';

    protected $description = 'Create orders with status pending (displays as تم حساب قيمة الشحن in Arabic) for testing';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $email = $this->option('email');

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User {$email} not found. Run: php artisan db:seed --class=RoleAndPermissionSeeder");

            return 1;
        }

        $maxNum = (int) Order::query()
            ->whereRaw(DB::getDriverName() === 'mysql'
                ? "order_number REGEXP '^[0-9]+$'"
                : "order_number GLOB '[0-9]*'")
            ->max(DB::raw(DB::getDriverName() === 'mysql'
                ? "CAST(order_number AS UNSIGNED)"
                : "CAST(order_number AS INTEGER)")) ?: 900000;

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $maxNum++;
            $orderNumber = (string) $maxNum;

            if (Order::where('order_number', $orderNumber)->exists()) {
                continue;
            }

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'status' => 'pending',
                'layout_option' => 2,
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

            $created++;
        }

        $this->info("Created {$created} orders with status pending (تم حساب قيمة الشحن) for {$email}.");

        return 0;
    }
}
