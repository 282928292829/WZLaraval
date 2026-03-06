<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verify that Laravel order numbers match the legacy WordPress site 100%.
 *
 * Legacy canonical order number: post_name ?: order_id meta ?: post.ID
 * Compares against Laravel orders.order_number by wp_post_id.
 */
class VerifyOrderNumbers extends Command
{
    protected $signature = 'migrate:verify-order-numbers
                            {--fix : Update Laravel orders to match legacy (use with caution)}';

    protected $description = 'Verify order numbers match legacy WordPress 100%';

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');

        $this->info('=== Verify Order Numbers ===');
        $this->newLine();

        // 1. Fetch all legacy orders with canonical order number (one row per post)
        $legacyOrders = DB::connection('legacy')
            ->table('posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get(['ID as wp_post_id', 'post_name']);

        $orderIdMeta = [];
        foreach ($legacyOrders->chunk(5000) as $chunk) {
            $ids = $chunk->pluck('wp_post_id')->toArray();
            $rows = DB::connection('legacy')
                ->table('postmeta')
                ->whereIn('post_id', $ids)
                ->where('meta_key', 'order_id')
                ->get();
            foreach ($rows->groupBy('post_id') as $postId => $group) {
                $orderIdMeta[(int) $postId] = $group->first()->meta_value;
            }
        }

        $legacyOrders = $legacyOrders->map(function ($row) use ($orderIdMeta) {
            $row->order_id_meta = $orderIdMeta[(int) $row->wp_post_id] ?? null;
            return $row;
        });

        $legacyMap = [];
        foreach ($legacyOrders as $row) {
            $base = $row->post_name ?: ($row->order_id_meta ?? (string) $row->wp_post_id);
            $base = trim($base) !== '' ? $base : (string) $row->wp_post_id;
            $legacyMap[(int) $row->wp_post_id] = (string) $base;
        }

        $this->line('Legacy orders: '.count($legacyMap));

        // 2. Check for duplicates in legacy (same base number for different post IDs)
        $baseToPostIds = [];
        foreach ($legacyMap as $wpPostId => $base) {
            $baseToPostIds[$base][] = $wpPostId;
        }
        $duplicates = array_filter($baseToPostIds, fn ($ids) => count($ids) > 1);
        $dupCount = count($duplicates);
        if ($dupCount > 0) {
            $this->line("Legacy duplicate base numbers: {$dupCount} (e.g. 53979 for 2+ posts → one gets -2)");
            $examples = array_slice($duplicates, 0, 3);
            foreach ($examples as $base => $ids) {
                $this->line("  — {$base}: post IDs ".implode(', ', $ids));
            }
        }

        // 3. Expected order number per wp_post_id (first occurrence = plain, rest = -2, -3)
        $expectedByWpPostId = [];
        $seenBase = [];
        foreach ($legacyOrders as $row) {
            $wpPostId = (int) $row->wp_post_id;
            $base = $legacyMap[$wpPostId];
            if (! isset($seenBase[$base])) {
                $expectedByWpPostId[$wpPostId] = $base;
                $seenBase[$base] = 1;
            } else {
                $seenBase[$base]++;
                $expectedByWpPostId[$wpPostId] = $base.'-'.$seenBase[$base];
            }
        }

        // 4. Fetch Laravel orders
        $laravelOrders = DB::table('orders')
            ->whereNotNull('wp_post_id')
            ->pluck('order_number', 'wp_post_id')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (string) $v])
            ->toArray();

        $this->line('Laravel orders (with wp_post_id): '.count($laravelOrders));
        $this->newLine();

        // 5. Compare
        $mismatches = [];
        $missingInLaravel = [];
        $missingInLegacy = [];

        foreach ($expectedByWpPostId as $wpPostId => $expected) {
            $actual = $laravelOrders[$wpPostId] ?? null;
            if ($actual === null) {
                $missingInLaravel[$wpPostId] = $expected;
            } elseif ((string) $actual !== (string) $expected) {
                $mismatches[$wpPostId] = ['expected' => $expected, 'actual' => $actual];
            }
        }

        foreach (array_keys($laravelOrders) as $wpPostId) {
            if (! isset($expectedByWpPostId[$wpPostId])) {
                $missingInLegacy[$wpPostId] = $laravelOrders[$wpPostId];
            }
        }

        // 6. Report
        $total = count($expectedByWpPostId);
        $matched = $total - count($mismatches) - count($missingInLaravel);

        if (count($mismatches) > 0) {
            $this->error('Mismatches ( Laravel order_number != legacy expected ): '.count($mismatches));
            $show = array_slice($mismatches, 0, 20, true);
            foreach ($show as $wpPostId => $m) {
                $this->line("  wp_post_id {$wpPostId}: expected «{$m['expected']}», actual «{$m['actual']}»");
            }
            if (count($mismatches) > 20) {
                $this->line('  ... and '.(count($mismatches) - 20).' more.');
            }
        }

        if (count($missingInLaravel) > 0) {
            $this->warn('Legacy orders not in Laravel: '.count($missingInLaravel));
        }

        if (count($missingInLegacy) > 0) {
            $this->warn('Laravel orders not in legacy: '.count($missingInLegacy));
        }

        $this->newLine();
        if (count($mismatches) === 0 && count($missingInLaravel) === 0) {
            $this->info("All {$total} orders match legacy 100%.");
            return self::SUCCESS;
        }

        $pct = $total > 0 ? round(100 * $matched / $total, 2) : 100;
        $this->warn("Match rate: {$matched}/{$total} ({$pct}%). Mismatches: ".count($mismatches));

        if ($this->option('fix') && count($mismatches) > 0) {
            $this->fixMismatches($mismatches);
        }

        return count($mismatches) > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fixMismatches(array $mismatches): void
    {
        $this->info('Fixing mismatches...');

        foreach ($mismatches as $wpPostId => $m) {
            $expected = $m['expected'];
            $existing = DB::table('orders')->where('order_number', $expected)->first();
            if ($existing && (int) $existing->wp_post_id !== $wpPostId) {
                $this->error("  Cannot fix wp_post_id {$wpPostId}: target «{$expected}» already used by order id {$existing->id} (wp_post_id {$existing->wp_post_id})");
                continue;
            }
            $updated = DB::table('orders')
                ->where('wp_post_id', $wpPostId)
                ->update(['order_number' => $expected]);
            if ($updated) {
                $this->line("  Updated wp_post_id {$wpPostId}: «{$m['actual']}» → «{$expected}»");
            }
        }
    }
}
