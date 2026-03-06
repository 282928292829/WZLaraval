<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verify that every order's user (order.user_id) matches legacy post_author by email.
 *
 * Compares legacy wp_posts.post_author → wp_users.user_email
 * against Laravel orders.user_id → users.email
 */
class VerifyUserMapping extends Command
{
    protected $signature = 'migrate:verify-user-mapping
                            {--show : Show first 20 mismatches}';

    protected $description = 'Verify post_author email matches order.user_id email for every order';

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');

        $this->info('=== Verify User Mapping ===');
        $this->newLine();

        $legacyMap = DB::connection('legacy')
            ->table('posts as p')
            ->join('users as u', 'u.ID', '=', 'p.post_author')
            ->where('p.post_type', 'orders')
            ->where('p.post_status', 'publish')
            ->pluck('u.user_email', 'p.ID')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (string) $v])
            ->toArray();

        $laravelMap = DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->whereNotNull('o.wp_post_id')
            ->pluck('u.email', 'o.wp_post_id')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (string) $v])
            ->toArray();

        $mismatches = [];
        foreach ($legacyMap as $wpPostId => $legacyEmail) {
            $laravelEmail = $laravelMap[$wpPostId] ?? null;
            if ($laravelEmail === null || strcasecmp((string) $legacyEmail, (string) $laravelEmail) !== 0) {
                $order = DB::table('orders')->where('wp_post_id', $wpPostId)->first();
                $mismatches[] = [
                    'order_number' => $order?->order_number ?? '?',
                    'wp_post_id' => $wpPostId,
                    'legacy' => $legacyEmail,
                    'laravel' => $laravelEmail ?? '?',
                ];
            }
        }

        $total = count($legacyMap);
        $matchCount = $total - count($mismatches);

        $this->line("Checked {$total} orders. Mismatches: ".count($mismatches));

        if (count($mismatches) > 0) {
            $this->error('User mapping mismatches: '.count($mismatches));
            if ($this->option('show') || count($mismatches) <= 20) {
                foreach (array_slice($mismatches, 0, 20) as $m) {
                    $this->line("  #{$m['order_number']} (wp:{$m['wp_post_id']}): legacy «{$m['legacy']}» != Laravel «{$m['laravel']}»");
                }
                if (count($mismatches) > 20) {
                    $this->line('  ... and '.(count($mismatches) - 20).' more.');
                }
            }
            $this->newLine();

            return self::FAILURE;
        }

        $this->info("All {$total} orders have matching user mapping (post_author email = order.user_id email).");

        return self::SUCCESS;
    }
}
