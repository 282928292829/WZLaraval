<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verify that order comment counts match legacy WordPress per order.
 *
 * For each order (by wp_post_id), compares legacy wp_comments count
 * against Laravel order_comments count.
 */
class VerifyOrderComments extends Command
{
    protected $signature = 'migrate:verify-order-comments
                            {--limit= : Only check this many orders (for testing)}
                            {--show : Show first 20 mismatches}';

    protected $description = 'Verify order comment counts match legacy per order';

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');

        $this->info('=== Verify Order Comments ===');
        $this->newLine();

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $p = DB::connection('legacy')->getTablePrefix();
        $legacyCounts = DB::connection('legacy')
            ->table('comments')
            ->join('posts', 'posts.ID', '=', 'comments.comment_post_ID')
            ->where('posts.post_type', 'orders')
            ->where('posts.post_status', 'publish')
            ->whereIn('comments.comment_approved', ['1', '0'])
            ->whereRaw("TRIM({$p}comments.comment_content) != ''")
            ->groupBy('comments.comment_post_ID')
            ->selectRaw("{$p}comments.comment_post_ID as wp_post_id, COUNT(*) as cnt")
            ->pluck('cnt', 'wp_post_id')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (int) $v])
            ->toArray();

        $laravelCounts = [];
        $orders = DB::table('orders')->whereNotNull('wp_post_id')->get(['id', 'wp_post_id']);
        $counts = DB::table('order_comments')
            ->selectRaw('order_id, COUNT(*) as cnt')
            ->groupBy('order_id')
            ->pluck('cnt', 'order_id')
            ->toArray();
        foreach ($orders as $o) {
            $laravelCounts[(int) $o->wp_post_id] = (int) ($counts[$o->id] ?? 0);
        }

        $mismatches = [];
        $checked = 0;

        foreach ($laravelCounts as $wpPostId => $laravelCnt) {
            if ($limit && $checked >= $limit) {
                break;
            }
            $legacyCnt = $legacyCounts[$wpPostId] ?? 0;
            if ((int) $laravelCnt !== (int) $legacyCnt) {
                $order = DB::table('orders')->where('wp_post_id', $wpPostId)->first();
                $mismatches[] = [
                    'order_number' => $order?->order_number ?? '?',
                    'wp_post_id' => $wpPostId,
                    'legacy' => $legacyCnt,
                    'laravel' => $laravelCnt,
                ];
            }
            $checked++;
        }

        $totalOrders = count($laravelCounts);
        $mismatchCount = count($mismatches);

        $this->line("Checked {$checked} orders. Mismatches: {$mismatchCount}");

        if ($mismatchCount > 0) {
            $this->error("Orders with comment count mismatch: {$mismatchCount}");
            if ($this->option('show') || $mismatchCount <= 20) {
                foreach (array_slice($mismatches, 0, 20) as $m) {
                    $this->line("  #{$m['order_number']} (wp:{$m['wp_post_id']}): legacy={$m['legacy']}, laravel={$m['laravel']}");
                }
                if ($mismatchCount > 20) {
                    $this->line('  ... and '.($mismatchCount - 20).' more.');
                }
            }
            $this->newLine();
            return self::FAILURE;
        }

        $this->info("All {$checked} orders have matching comment counts.");
        return self::SUCCESS;
    }
}
