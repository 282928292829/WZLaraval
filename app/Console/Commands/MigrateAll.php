<?php

namespace App\Console\Commands;

use Database\Seeders\DeletedUserSeeder;
use Database\Seeders\DevUsersSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrator: runs all legacy → Laravel migration commands in the correct order.
 *
 * Order:
 *   1. ad-campaigns      (no dependencies)
 *   2. comment-templates  (no dependencies)
 *   3. users             (no dependencies; seeds Deleted User first)
 *   4. addresses         (depends on users)
 *   5. orders            (depends on users)
 *   6. order-comments    (depends on users + orders)
 *   7. timeline          (depends on orders)
 *   8. fix-merges        (depends on orders)
 *   9. order-files       (depends on users + orders)
 *  10. posts             (no dependencies; creates post_categories)
 *  11. post-comments     (depends on posts + users)
 *  12. pages             (no dependencies)
 *  13. page-comments     (depends on pages + users)
 *  14. assign-superadmins (depends on users)
 *  15. validate          (integrity report)
 *
 * Usage:
 *   php artisan migrate:all               # incremental (safe to re-run)
 *   php artisan migrate:all --fresh      # truncate all target tables once, then run all (no --fresh to sub-commands)
 *   php artisan migrate:all --dry-run    # validate only
 *   php artisan migrate:all --seed-dev   # run DevUsersSeeder after migration
 */
class MigrateAll extends Command
{
    protected $signature = 'migrate:all
                            {--fresh : Truncate all target tables once before migrating, then run all steps without --fresh}
                            {--dry-run : Run validate only, skip data import}
                            {--seed-dev : Run DevUsersSeeder after migration}
                            {--skip= : Comma-separated steps to skip}
                            {--chunk=500 : Batch size passed to sub-commands}';

    protected $description = 'Run all legacy WordPress → Laravel data migration steps in sequence';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║        Legacy → Laravel Migration         ║');
        $this->info('║   Source: old-wordpress (sole source)     ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        if ($this->option('dry-run')) {
            return $this->runValidate();
        }

        $skip = array_map('trim', explode(',', $this->option('skip') ?? ''));
        $fresh = $this->option('fresh');
        $chunk = $this->option('chunk');
        $startTime = microtime(true);

        if ($fresh) {
            $this->truncateAllTargetTables();
        }

        // Seed Deleted User before migrate:users so it exists for orphan comments.
        $this->line('Seeding Deleted User placeholder …');
        app(DeletedUserSeeder::class)->run();

        $steps = [
            'ad-campaigns' => fn () => $this->runStep('migrate:ad-campaigns'),
            'comment-templates' => fn () => $this->runStep('migrate:comment-templates'),
            'users' => fn () => $this->runStep('migrate:users', ['--chunk' => $chunk]),
            'addresses' => fn () => $this->runStep('migrate:addresses'),
            'orders' => fn () => $this->runStep('migrate:orders', ['--chunk' => $chunk]),
            'order-comments' => fn () => $this->runStep('migrate:order-comments', ['--chunk' => $chunk]),
            'timeline' => fn () => $this->runStep('migrate:timeline'),
            'fix-merges' => fn () => $this->runStep('migrate:fix-merges'),
            'order-files' => fn () => $this->runStep('migrate:order-files', ['--chunk' => $chunk]),
            'posts' => fn () => $this->runStep('migrate:posts'),
            'post-comments' => fn () => $this->runStep('migrate:post-comments'),
            'pages' => fn () => $this->runStep('migrate:pages'),
            'page-comments' => fn () => $this->runStep('migrate:page-comments'),
            'assign-superadmins' => fn () => $this->runStep('migrate:assign-superadmins'),
        ];

        $failed = [];

        foreach ($steps as $name => $runner) {
            if (in_array($name, $skip, true)) {
                $this->line("  <fg=yellow>SKIP</> {$name}");

                continue;
            }

            $this->newLine();
            $code = $runner();

            if ($code !== self::SUCCESS) {
                $this->error("{$name} failed with code {$code}");
                $failed[] = $name;

                if (! $this->confirm("Continue despite {$name} failure?", false)) {
                    break;
                }
            }
        }

        // Always run validate at the end (unless explicitly skipped).
        if (! in_array('validate', $skip, true)) {
            $this->newLine();
            $this->runValidate();
        }

        if ($this->option('seed-dev')) {
            $this->newLine();
            $this->line('Running DevUsersSeeder …');
            app(DevUsersSeeder::class)->run();
        }

        $elapsed = round(microtime(true) - $startTime);
        $this->newLine();
        $this->info("Migration completed in {$elapsed}s.");

        if ($failed) {
            $this->error('Failed steps: '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Truncate all migration target tables once. Used when --fresh.
     * Order matters for foreign key constraints.
     */
    private function truncateAllTargetTables(): void
    {
        $this->warn('Truncating all migration target tables …');

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        $tables = [
            'order_comment_reads',
            'order_comment_edits',
            'order_comment_notification_log',
            'order_comments',
            'order_files',
            'order_items',
            'order_timeline',
            'orders',
            'model_has_permissions',
            'model_has_roles',
            'user_addresses',
            'users',
            'sessions',
            'ad_campaigns',
            'comment_templates',
            'post_comments',
            'posts',
            'post_categories',
            'page_comments',
            'pages',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->line('  All target tables truncated.');
    }

    private function runStep(string $command, array $options = []): int
    {
        $filtered = array_filter($options, fn ($v) => $v !== false && $v !== null);

        return $this->call($command, $filtered);
    }

    private function runValidate(): int
    {
        return $this->call('migrate:validate', [
            '--sample' => 20,
        ]);
    }
}
