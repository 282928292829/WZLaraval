<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Validate the data migration by comparing row counts and spot-checking records.
 *
 * Uses legacy connection with table prefix (posts, postmeta, etc.).
 */
class MigrateValidate extends Command
{
    protected $signature = 'migrate:validate
                            {--sample=10 : Number of records to spot-check per entity}
                            {--fix : Attempt to fix minor integrity issues}';

    protected $description = 'Validate migration integrity: row counts, spot-checks, FK consistency';

    private array $issues = [];

    private array $results = [];

    public function handle(): int
    {
        $this->info('=== MigrateValidate ===');
        $this->newLine();

        $this->checkUsers();
        $this->checkOrders();
        $this->checkOrderItems();
        $this->checkOrderComments();
        $this->checkUserMapping();
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

    private function checkUsers(): void
    {
        $legacy = DB::connection('legacy')->table('users')->count();
        $laravel = DB::table('users')->count();

        $ok = $laravel > 0 && $laravel <= $legacy + 1;

        $this->results[] = [
            'entity' => 'users',
            'legacy' => $legacy,
            'laravel' => $laravel,
            'status' => $ok ? '✓' : '?',
            'note' => $laravel === 0 ? 'EMPTY' : ($laravel < $legacy + 1 ? "Δ {$laravel}/{$legacy}" : 'OK'),
        ];

        if ($laravel === 0) {
            $this->issues[] = 'users table is empty — run migrate:users';
        }

        $roleDistribution = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('roles.name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('roles.name')
            ->pluck('cnt', 'name')
            ->toArray();

        $this->line('  User role distribution: '.collect($roleDistribution)->map(fn ($c, $r) => "{$r}:{$c}")->implode(', '));
    }

    private function checkOrders(): void
    {
        $legacy = DB::connection('legacy')
            ->table('posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->count();

        $laravel = DB::table('orders')->count();
        $ok = $laravel > 0 && $laravel >= ($legacy * 0.99);

        $this->results[] = [
            'entity' => 'orders',
            'legacy' => $legacy,
            'laravel' => $laravel,
            'status' => $ok ? '✓' : '✗',
            'note' => $ok ? 'OK' : "Expected ~{$legacy}, got {$laravel}",
        ];

        if (! $ok) {
            $this->issues[] = "orders: expected ~{$legacy}, got {$laravel}";
        }

        $sample = (int) $this->option('sample');
        $this->spotCheckOrders($sample);

        $legacyStatuses = DB::connection('legacy')
            ->table('postmeta')
            ->join('posts', 'posts.ID', '=', 'postmeta.post_id')
            ->where('posts.post_type', 'orders')
            ->where('postmeta.meta_key', 'order_status')
            ->select('postmeta.meta_value as status', DB::connection('legacy')->raw('COUNT(*) as cnt'))
            ->groupBy('postmeta.meta_value')
            ->pluck('cnt', 'status')
            ->toArray();

        $laravelStatuses = DB::table('orders')
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $this->line('  Legacy status dist: '.collect($legacyStatuses)->map(fn ($c, $s) => "{$s}:{$c}")->implode(', '));
        $this->line('  Laravel status dist: '.collect($laravelStatuses)->map(fn ($c, $s) => "{$s}:{$c}")->implode(', '));
    }

    private function spotCheckOrders(int $sample): void
    {
        $sampleOrders = DB::connection('legacy')
            ->table('postmeta as pm')
            ->join('posts as p', 'p.ID', '=', 'pm.post_id')
            ->where('p.post_type', 'orders')
            ->where('pm.meta_key', 'order_id')
            ->orderByRaw('RAND()')
            ->limit($sample)
            ->pluck('pm.meta_value', 'p.ID')
            ->toArray();

        $misses = 0;
        foreach ($sampleOrders as $wpPostId => $orderNumber) {
            $exists = DB::table('orders')->where('order_number', (string) $orderNumber)->exists()
                || DB::table('orders')->where('order_number', 'like', (string) $orderNumber.'-%')->exists();
            if (! $exists) {
                $misses++;
                $this->issues[] = "Order #{$orderNumber} (WP post {$wpPostId}) not found in Laravel orders";
            }
        }

        if ($misses === 0 && $sample > 0) {
            $this->line("  Spot-check: {$sample} random orders — all found");
        }
    }

    private function checkOrderItems(): void
    {
        $prefix = DB::connection('legacy')->getTablePrefix();
        $legacyItemSlots = DB::connection('legacy')
            ->table('postmeta')
            ->join('posts', 'posts.ID', '=', 'postmeta.post_id')
            ->where('posts.post_type', 'orders')
            ->where('postmeta.meta_key', 'like', 'p_url_%')
            ->whereRaw("`{$prefix}postmeta`.`meta_value` != ''")
            ->count();

        $laravel = DB::table('order_items')->count();
        $ok = $laravel >= ($legacyItemSlots * 0.95);

        $this->results[] = [
            'entity' => 'order_items',
            'legacy' => $legacyItemSlots,
            'laravel' => $laravel,
            'status' => $ok ? '✓' : '✗',
            'note' => $ok ? 'OK' : "Expected ~{$legacyItemSlots}, got {$laravel}",
        ];

        if (! $ok) {
            $this->issues[] = "order_items: expected ~{$legacyItemSlots}, got {$laravel}";
        }

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
            ->table('comments as c')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'orders')
            ->whereIn('c.comment_approved', ['1', '0'])
            ->count();

        $laravel = DB::table('order_comments')->count();
        $ok = $laravel > 0 && $laravel >= ($legacy * 0.98);

        $this->results[] = [
            'entity' => 'order_comments',
            'legacy' => $legacy,
            'laravel' => $laravel,
            'status' => $ok ? '✓' : '✗',
            'note' => $ok ? 'OK' : "Expected ~{$legacy}, got {$laravel}",
        ];

        if (! $ok) {
            $this->issues[] = "order_comments: expected ~{$legacy}, got {$laravel}";
        }

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
            ->table('posts')
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->count();

        $laravel = DB::table('posts')->where('status', 'published')->count();
        $ok = $laravel >= $legacy;

        $this->results[] = [
            'entity' => 'posts',
            'legacy' => $legacy,
            'laravel' => $laravel,
            'status' => $ok ? '✓' : '?',
            'note' => $ok ? 'OK' : "Expected {$legacy}, got {$laravel}",
        ];

        if ($laravel < $legacy) {
            $this->issues[] = "posts: expected {$legacy} published, got {$laravel}";
        }
    }

    private function checkPostComments(): void
    {
        $legacy = DB::connection('legacy')
            ->table('comments as c')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'post')
            ->where('c.comment_approved', '1')
            ->count();

        $laravel = DB::table('post_comments')->count();
        $ok = $laravel >= $legacy;

        $this->results[] = [
            'entity' => 'post_comments',
            'legacy' => $legacy,
            'laravel' => $laravel,
            'status' => $ok ? '✓' : '?',
            'note' => $ok ? 'OK' : "Expected {$legacy}, got {$laravel}",
        ];

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
            ->table('posts')
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->count();

        $laravel = DB::table('pages')->where('is_published', true)->count();

        $this->results[] = [
            'entity' => 'pages',
            'legacy' => $legacy,
            'laravel' => $laravel,
            'status' => '~',
            'note' => 'Functional WP pages skipped intentionally',
        ];
    }

    private function checkUserMapping(): void
    {
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
                $mismatches[] = [
                    'wp_post_id' => $wpPostId,
                    'legacy' => $legacyEmail,
                    'laravel' => $laravelEmail ?? '?',
                ];
            }
        }

        $count = count($mismatches);
        $ok = $count === 0;

        $this->results[] = [
            'entity' => 'user_mapping',
            'legacy' => '-',
            'laravel' => '-',
            'status' => $ok ? '✓' : '✗',
            'note' => $ok ? '0 mismatches' : "{$count} orders: post_author email != order.user_id email",
        ];

        if (! $ok) {
            foreach (array_slice($mismatches, 0, 10) as $m) {
                $this->issues[] = "Order wp_post_id {$m['wp_post_id']}: legacy {$m['legacy']} != Laravel {$m['laravel']}";
            }
            if ($count > 10) {
                $this->issues[] = '... and '.($count - 10).' more user mapping mismatches';
            }
        }
    }

    private function checkForeignKeyIntegrity(): void
    {
        $badUsers = DB::table('order_comments')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'order_comments.user_id');
            })
            ->count();

        if ($badUsers > 0) {
            $this->issues[] = "order_comments: {$badUsers} rows reference non-existent users";
        }

        $badOrderUsers = DB::table('orders')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'orders.user_id');
            })
            ->count();

        if ($badOrderUsers > 0) {
            $this->issues[] = "orders: {$badOrderUsers} rows reference non-existent users";
        }
    }

    private function renderResultsTable(): void
    {
        $headers = ['Entity', 'Legacy', 'Laravel', 'Status', 'Note'];
        $rows = array_map(fn ($r) => array_values($r), $this->results);
        $this->table($headers, $rows);
    }

    private function attemptFixes(): void
    {
        $this->warn('Attempting fixes …');

        $deleted = DB::table('order_items')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('orders')->whereColumn('orders.id', 'order_items.order_id');
            })
            ->delete();

        if ($deleted > 0) {
            $this->line("  Deleted {$deleted} orphan order_items rows.");
        }

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
