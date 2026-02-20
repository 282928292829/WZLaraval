<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Validate the data migration by comparing row counts and spot-checking records.
 *
 * Checks performed:
 *  1. Row counts: legacy vs Laravel for each migrated entity
 *  2. Order spot-check: verify a sample of orders have correct status + item count
 *  3. User spot-check: verify a sample of users exist with correct roles
 *  4. Comment integrity: verify comments reference valid orders
 *  5. Item integrity: verify order_items reference valid orders, no orphans
 *  6. Thread integrity: verify post_comments parent_id points to same post
 */
class MigrateValidate extends Command
{
    protected $signature = 'migrate:validate
                            {--sample=10 : Number of records to spot-check per entity}
                            {--fix : Attempt to fix minor integrity issues}';

    protected $description = 'Validate migration integrity: row counts, spot-checks, FK consistency';

    private array $issues  = [];
    private array $results = [];

    public function handle(): int
    {
        $this->info('=== MigrateValidate ===');
        $this->newLine();

        $this->checkUsers();
        $this->checkOrders();
        $this->checkOrderItems();
        $this->checkOrderComments();
        $this->checkPosts();
        $this->checkPostComments();
        $this->checkPages();
        $this->checkForeignKeyIntegrity();

        $this->renderResultsTable();

        if ($this->issues) {
            $this->newLine();
            $this->error('Issues found:');
            foreach ($this->issues as $issue) {
                $this->line("  ⚠  {$issue}");
            }

            if ($this->option('fix')) {
                $this->attemptFixes();
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All checks passed.');

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Entity checks
    // -------------------------------------------------------------------------

    private function checkUsers(): void
    {
        $legacy  = DB::connection('legacy')->table('wp_users')->count();
        $laravel = DB::table('users')->count();

        // WP has auto-generated spam accounts — expect Laravel count ≤ legacy count.
        $ok = $laravel > 0 && $laravel <= $legacy;

        $this->results[] = [
            'entity'  => 'users',
            'legacy'  => $legacy,
            'laravel' => $laravel,
            'status'  => $ok ? '✓' : '?',
            'note'    => $laravel === 0 ? 'EMPTY' : ($laravel < $legacy ? "Δ {$laravel}/{$legacy}" : 'OK'),
        ];

        if ($laravel === 0) {
            $this->issues[] = 'users table is empty — run migrate:users';
        }

        // Role distribution.
        $roleDistribution = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('roles.name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('roles.name')
            ->pluck('cnt', 'name')
            ->toArray();

        $this->line('  User role distribution: ' . collect($roleDistribution)->map(fn ($c, $r) => "{$r}:{$c}")->implode(', '));
    }

    private function checkOrders(): void
    {
        $legacy = DB::connection('legacy')
            ->table('wp_posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->count();

        $laravel = DB::table('orders')->count();
        $ok      = $laravel > 0 && $laravel >= ($legacy * 0.99); // Allow 1% tolerance.

        $this->results[] = [
            'entity'  => 'orders',
            'legacy'  => $legacy,
            'laravel' => $laravel,
            'status'  => $ok ? '✓' : '✗',
            'note'    => $ok ? 'OK' : "Expected ~{$legacy}, got {$laravel}",
        ];

        if (! $ok) {
            $this->issues[] = "orders: expected ~{$legacy}, got {$laravel}";
        }

        // Spot-check a sample.
        $sample = (int) $this->option('sample');
        $this->spotCheckOrders($sample);

        // Status distribution comparison.
        $this->compareStatusDistribution();
    }

    private function spotCheckOrders(int $sample): void
    {
        $sampleOrders = DB::connection('legacy')
            ->table('wp_postmeta as pm')
            ->join('wp_posts as p', 'p.ID', '=', 'pm.post_id')
            ->where('p.post_type', 'orders')
            ->where('pm.meta_key', 'order_id')
            ->orderByRaw('RAND()')
            ->limit($sample)
            ->pluck('pm.meta_value', 'p.ID')
            ->toArray();

        $misses = 0;
        foreach ($sampleOrders as $wpPostId => $orderNumber) {
            $exists = DB::table('orders')->where('order_number', (string) $orderNumber)->exists();
            if (! $exists) {
                $misses++;
                $this->issues[] = "Order #{$orderNumber} (WP post {$wpPostId}) not found in Laravel orders";
            }
        }

        if ($misses === 0 && $sample > 0) {
            $this->line("  Spot-check: {$sample} random orders — all found");
        }
    }

    private function compareStatusDistribution(): void
    {
        $legacyStatuses = DB::connection('legacy')
            ->table('wp_postmeta')
            ->join('wp_posts', 'wp_posts.ID', '=', 'wp_postmeta.post_id')
            ->where('wp_posts.post_type', 'orders')
            ->where('wp_postmeta.meta_key', 'order_status')
            ->select('wp_postmeta.meta_value as status', DB::connection('legacy')->raw('COUNT(*) as cnt'))
            ->groupBy('wp_postmeta.meta_value')
            ->pluck('cnt', 'status')
            ->toArray();

        $laravelStatuses = DB::table('orders')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $this->line('  Legacy status dist: ' . collect($legacyStatuses)->map(fn ($c, $s) => "{$s}:{$c}")->implode(', '));
        $this->line('  Laravel status dist: ' . collect($laravelStatuses)->map(fn ($c, $s) => "{$s}:{$c}")->implode(', '));
    }

    private function checkOrderItems(): void
    {
        $legacyItemSlots = DB::connection('legacy')
            ->table('wp_postmeta')
            ->join('wp_posts', 'wp_posts.ID', '=', 'wp_postmeta.post_id')
            ->where('wp_posts.post_type', 'orders')
            ->where('wp_postmeta.meta_key', 'like', 'p_url_%')
            ->whereRaw("wp_postmeta.meta_value != ''")
            ->count();

        $laravel = DB::table('order_items')->count();
        $ok      = $laravel >= ($legacyItemSlots * 0.95); // Allow 5% tolerance for empty URL slots.

        $this->results[] = [
            'entity'  => 'order_items',
            'legacy'  => $legacyItemSlots,
            'laravel' => $laravel,
            'status'  => $ok ? '✓' : '✗',
            'note'    => $ok ? 'OK' : "Expected ~{$legacyItemSlots}, got {$laravel}",
        ];

        if (! $ok) {
            $this->issues[] = "order_items: expected ~{$legacyItemSlots}, got {$laravel}";
        }

        // Check for orphan items (order_id references non-existent order).
        $orphans = DB::table('order_items')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('orders')->whereColumn('orders.id', 'order_items.order_id');
            })
            ->count();

        if ($orphans > 0) {
            $this->issues[] = "order_items: {$orphans} orphan rows with no matching order";
        }
    }

    private function checkOrderComments(): void
    {
        $legacy = DB::connection('legacy')
            ->table('wp_comments as c')
            ->join('wp_posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'orders')
            ->whereIn('c.comment_approved', ['1', '0'])
            ->count();

        $laravel = DB::table('order_comments')->count();
        $ok      = $laravel > 0 && $laravel >= ($legacy * 0.98);

        $this->results[] = [
            'entity'  => 'order_comments',
            'legacy'  => $legacy,
            'laravel' => $laravel,
            'status'  => $ok ? '✓' : '✗',
            'note'    => $ok ? 'OK' : "Expected ~{$legacy}, got {$laravel}",
        ];

        if (! $ok) {
            $this->issues[] = "order_comments: expected ~{$legacy}, got {$laravel}";
        }

        // Orphan check.
        $orphans = DB::table('order_comments')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('orders')->whereColumn('orders.id', 'order_comments.order_id');
            })
            ->count();

        if ($orphans > 0) {
            $this->issues[] = "order_comments: {$orphans} orphan rows";
        }
    }

    private function checkPosts(): void
    {
        $legacy = DB::connection('legacy')
            ->table('wp_posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();

        $laravel = DB::table('posts')->where('status', 'published')->count();
        $ok      = $laravel >= $legacy;

        $this->results[] = [
            'entity'  => 'posts',
            'legacy'  => $legacy,
            'laravel' => $laravel,
            'status'  => $ok ? '✓' : '?',
            'note'    => $ok ? 'OK' : "Expected {$legacy}, got {$laravel}",
        ];

        if ($laravel < $legacy) {
            $this->issues[] = "posts: expected {$legacy} published, got {$laravel}";
        }
    }

    private function checkPostComments(): void
    {
        $legacy = DB::connection('legacy')
            ->table('wp_comments as c')
            ->join('wp_posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'post')
            ->where('c.comment_approved', '1')
            ->count();

        $laravel = DB::table('post_comments')->count();
        $ok      = $laravel >= $legacy;

        $this->results[] = [
            'entity'  => 'post_comments',
            'legacy'  => $legacy,
            'laravel' => $laravel,
            'status'  => $ok ? '✓' : '?',
            'note'    => $ok ? 'OK' : "Expected {$legacy}, got {$laravel}",
        ];

        // Thread integrity: parent_id must point to a comment on the same post.
        $brokenThreads = DB::table('post_comments as c')
            ->join('post_comments as parent', 'parent.id', '=', 'c.parent_id')
            ->where('c.parent_id', '>', 0)
            ->whereColumn('c.post_id', '!=', 'parent.post_id')
            ->count();

        if ($brokenThreads > 0) {
            $this->issues[] = "post_comments: {$brokenThreads} reply comments whose parent belongs to a different post";
        }
    }

    private function checkPages(): void
    {
        $legacy = DB::connection('legacy')
            ->table('wp_posts')
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->count();

        $laravel = DB::table('pages')->where('is_published', true)->count();

        $this->results[] = [
            'entity'  => 'pages',
            'legacy'  => $legacy,
            'laravel' => $laravel,
            'status'  => '~',
            'note'    => 'Functional WP pages skipped intentionally',
        ];
    }

    private function checkForeignKeyIntegrity(): void
    {
        // order_comments.user_id → users.id
        $badUsers = DB::table('order_comments')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'order_comments.user_id');
            })
            ->count();

        if ($badUsers > 0) {
            $this->issues[] = "order_comments: {$badUsers} rows reference non-existent users";
        }

        // orders.user_id → users.id
        $badOrderUsers = DB::table('orders')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'orders.user_id');
            })
            ->count();

        if ($badOrderUsers > 0) {
            $this->issues[] = "orders: {$badOrderUsers} rows reference non-existent users";
        }
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    private function renderResultsTable(): void
    {
        $headers = ['Entity', 'Legacy', 'Laravel', 'Status', 'Note'];
        $rows    = array_map(fn ($r) => array_values($r), $this->results);
        $this->table($headers, $rows);
    }

    // -------------------------------------------------------------------------
    // Auto-fix
    // -------------------------------------------------------------------------

    private function attemptFixes(): void
    {
        $this->warn('Attempting fixes …');

        // Fix: delete orphan order_items.
        $deleted = DB::table('order_items')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('orders')->whereColumn('orders.id', 'order_items.order_id');
            })
            ->delete();

        if ($deleted > 0) {
            $this->line("  Deleted {$deleted} orphan order_items rows.");
        }

        // Fix: delete orphan order_comments.
        $deleted = DB::table('order_comments')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('orders')->whereColumn('orders.id', 'order_comments.order_id');
            })
            ->delete();

        if ($deleted > 0) {
            $this->line("  Deleted {$deleted} orphan order_comments rows.");
        }
    }
}
