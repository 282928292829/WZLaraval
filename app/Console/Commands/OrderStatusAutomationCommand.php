<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderStatusAutomationLog;
use App\Models\OrderStatusAutomationRule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OrderStatusAutomationCommand extends Command
{
    protected $signature = 'orders:status-automation';

    protected $description = 'Run order status automation rules — post system comments after X days in status';

    public function handle(): int
    {
        $rules = OrderStatusAutomationRule::where('is_active', true)->get();

        if ($rules->isEmpty()) {
            $this->info(__('automation.no_rules'));

            return self::SUCCESS;
        }

        $totalTriggered = 0;

        foreach ($rules as $rule) {
            $cutoff = Carbon::now()->subDays($rule->days);

            $orders = Order::where('status', $rule->status)
                ->where('status_changed_at', '<=', $cutoff)
                ->whereDoesntHave('automationLogs', fn ($q) => $q->where('order_status_automation_rule_id', $rule->id))
                ->get();

            foreach ($orders as $order) {
                $daysInStatus = (int) $order->status_changed_at->diffInDays(now());
                $body = $rule->renderComment($daysInStatus);

                OrderComment::create([
                    'order_id' => $order->id,
                    'user_id' => null,
                    'body' => $body,
                    'is_internal' => false,
                    'is_system' => true,
                ]);

                OrderStatusAutomationLog::create([
                    'order_id' => $order->id,
                    'order_status_automation_rule_id' => $rule->id,
                ]);

                $totalTriggered++;
                $this->line("  {$order->order_number}: {$body}");
            }
        }

        $this->info(__('automation.triggered', ['count' => $totalTriggered]));

        return self::SUCCESS;
    }
}
