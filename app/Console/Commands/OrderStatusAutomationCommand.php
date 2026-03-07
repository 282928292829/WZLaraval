<?php

namespace App\Console\Commands;

use App\Mail\CommentNotification;
use App\Mail\StatusChangeNotification;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\OrderCommentNotificationLog;
use App\Models\OrderStatusAutomationLog;
use App\Models\OrderStatusAutomationRule;
use App\Models\OrderTimeline;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class OrderStatusAutomationCommand extends Command
{
    protected $signature = 'orders:status-automation';

    protected $description = 'Run order automation rules — status duration and comment no-reply';

    public function handle(): int
    {
        $rules = OrderStatusAutomationRule::where('is_active', true)->get();

        if ($rules->isEmpty()) {
            $this->info(__('automation.no_rules'));

            return self::SUCCESS;
        }

        $totalTriggered = 0;

        foreach ($rules as $rule) {
            if ($rule->isStatusTrigger()) {
                $totalTriggered += $this->processStatusRule($rule);
            } else {
                $totalTriggered += $this->processCommentRule($rule);
            }
        }

        $this->info(__('automation.triggered', ['count' => $totalTriggered]));

        return self::SUCCESS;
    }

    protected function processStatusRule(OrderStatusAutomationRule $rule): int
    {
        $cutoff = Carbon::now()->subHours($rule->getThresholdHours());

        $orders = Order::where('status', $rule->status)
            ->where('status_changed_at', '<=', $cutoff)
            ->whereDoesntHave('automationLogs', fn ($q) => $q
                ->where('order_status_automation_rule_id', $rule->id)
                ->whereNull('order_comment_id'))
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            if ($rule->hasPauseIfNoReply()) {
                $latestComment = $order->comments()->orderByDesc('created_at')->first();
                if ($latestComment !== null) {
                    $hoursSinceLastComment = (int) $latestComment->created_at->diffInHours(now());
                    if ($hoursSinceLastComment >= $rule->getPauseIfNoReplyThresholdHours()) {
                        continue;
                    }
                }
            }

            $totalHoursInStatus = (int) $order->status_changed_at->diffInHours(now());
            $duration = [
                'days' => (int) floor($totalHoursInStatus / 24),
                'hours' => $totalHoursInStatus % 24,
            ];

            $body = null;
            $createdComment = null;
            if ($rule->shouldPostComment()) {
                $body = $rule->renderComment($duration);
                $createdComment = OrderComment::create([
                    'order_id' => $order->id,
                    'user_id' => null,
                    'body' => $body,
                    'is_internal' => $rule->comment_is_internal,
                    'is_system' => true,
                ]);
            }

            if ($rule->shouldChangeStatus() && filled($rule->action_status)) {
                $order->update([
                    'status' => $rule->action_status,
                    'status_changed_at' => now(),
                ]);
                if ($body === null) {
                    $body = __('automation.status_changed_to', ['status' => Order::getStatuses()[$rule->action_status] ?? $rule->action_status]);
                }
            }

            OrderStatusAutomationLog::create([
                'order_id' => $order->id,
                'order_status_automation_rule_id' => $rule->id,
                'order_comment_id' => null,
            ]);

            $this->sendAutomationEmailIfRequested($rule, $order, $createdComment);

            $count++;
            $output = $body;
            if ($output === null && $rule->shouldChangeStatus() && filled($rule->action_status)) {
                $output = __('automation.status_changed_to', ['status' => Order::getStatuses()[$rule->action_status] ?? $rule->action_status]);
            }
            $this->line("  {$order->order_number}: ".($output ?? '-'));
        }

        return $count;
    }

    protected function processCommentRule(OrderStatusAutomationRule $rule): int
    {
        $thresholdHours = $rule->getThresholdHours();
        $cutoff = Carbon::now()->subHours($thresholdHours);

        $orderIds = $this->getOrderIdsWithUnrepliedComment($rule->last_comment_from, $cutoff);

        if ($orderIds->isEmpty()) {
            return 0;
        }

        $orderQuery = Order::whereIn('id', $orderIds);

        if ($rule->status !== null && $rule->status !== '') {
            $orderQuery->where('status', $rule->status);
        }

        $orders = $orderQuery->get();
        $count = 0;

        foreach ($orders as $order) {
            $lastHumanComment = $order->comments()
                ->where('is_system', false)
                ->orderByDesc('created_at')
                ->first();

            if ($lastHumanComment === null) {
                continue;
            }

            $hoursSince = (int) $lastHumanComment->created_at->diffInHours(now());
            if ($hoursSince < $thresholdHours) {
                continue;
            }

            $alreadyLogged = OrderStatusAutomationLog::where('order_id', $order->id)
                ->where('order_status_automation_rule_id', $rule->id)
                ->where('order_comment_id', $lastHumanComment->id)
                ->exists();

            if ($alreadyLogged) {
                continue;
            }

            $totalHoursUnreplied = $hoursSince;
            $duration = [
                'days' => (int) floor($totalHoursUnreplied / 24),
                'hours' => $totalHoursUnreplied % 24,
            ];
            $body = $rule->renderComment($duration, $order->status);

            $createdComment = OrderComment::create([
                'order_id' => $order->id,
                'user_id' => null,
                'body' => $body,
                'is_internal' => $rule->comment_is_internal,
                'is_system' => true,
            ]);

            OrderStatusAutomationLog::create([
                'order_id' => $order->id,
                'order_status_automation_rule_id' => $rule->id,
                'order_comment_id' => $lastHumanComment->id,
            ]);

            $this->sendAutomationEmailIfRequested($rule, $order, $createdComment);

            $count++;
            $this->line("  {$order->order_number}: {$body}");
        }

        return $count;
    }

    /**
     * Get order IDs where last human comment matches filter and is older than cutoff.
     *
     * @param  string  $lastCommentFrom  customer|staff|any
     */
    protected function getOrderIdsWithUnrepliedComment(string $lastCommentFrom, Carbon $cutoff): \Illuminate\Support\Collection
    {
        $sub = OrderComment::query()
            ->selectRaw('order_id, MAX(created_at) as max_created')
            ->where('is_system', false)
            ->whereNull('deleted_at')
            ->groupBy('order_id');

        $query = OrderComment::query()
            ->select('order_comments.order_id')
            ->joinSub($sub, 'last', function ($join) {
                $join->on('order_comments.order_id', '=', 'last.order_id')
                    ->on('order_comments.created_at', '=', 'last.max_created');
            })
            ->whereNull('order_comments.deleted_at')
            ->where('order_comments.is_system', false)
            ->where('order_comments.created_at', '<=', $cutoff);

        if ($lastCommentFrom === 'customer') {
            $query->whereHas('user', fn ($q) => $q->nonStaff());
        } elseif ($lastCommentFrom === 'staff') {
            $query->whereHas('user', fn ($q) => $q->staff());
        }

        return $query->pluck('order_comments.order_id')->unique()->values();
    }

    /**
     * Optionally send email to customer when automation triggers.
     * - Public comment created → CommentNotification
     * - Status changed without public comment → StatusChangeNotification
     */
    protected function sendAutomationEmailIfRequested(
        OrderStatusAutomationRule $rule,
        Order $order,
        ?OrderComment $createdComment
    ): void {
        if (! $rule->notify_customer_email) {
            return;
        }

        if (! Setting::get('email_enabled', false)) {
            return;
        }

        $order->loadMissing('user');
        if (! $order->user || ! $order->user->email) {
            return;
        }

        try {
            if ($createdComment !== null && ! $createdComment->is_internal) {
                if (! Setting::get('email_comment_notification', false)) {
                    return;
                }
                Mail::to($order->user->email, $order->user->name)
                    ->locale($order->user->locale ?? 'ar')
                    ->queue(new CommentNotification($createdComment));

                OrderCommentNotificationLog::create([
                    'order_id' => $order->id,
                    'comment_id' => $createdComment->id,
                    'user_id' => null,
                    'channel' => 'email',
                    'sent_at' => now(),
                ]);
            } elseif ($createdComment === null || $createdComment->is_internal) {
                if (! Setting::get('email_status_change', true)) {
                    return;
                }
                Mail::to($order->user->email, $order->user->name)
                    ->locale($order->user->locale ?? 'ar')
                    ->queue(new StatusChangeNotification($order));
            }

            OrderTimeline::create([
                'order_id' => $order->id,
                'user_id' => null,
                'type' => 'note',
                'body' => __('orders.timeline_notification_sent', ['email' => $order->user->email]),
            ]);
        } catch (\Throwable $e) {
            $this->warn("  Automation email failed for {$order->order_number}: {$e->getMessage()}");
        }
    }
}
