<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Orchestrator: runs all legacy → Laravel migration commands in the correct order.
 *
 * Order matters:
 *   1. ad-campaigns      (no dependencies)
 *   2. comment-templates (no dependencies)
 *   3. users             (no dependencies)
 *   4. addresses         (depends on users)
 *   5. orders            (depends on users)
 *   6. order-comments    (depends on users + orders)
 *   7. timeline          (depends on orders)
 *   8. fix-merges        (depends on orders)
 *   9. order-files       (depends on users + orders)
 *  10. posts             (no dependencies; creates post_categories)
 *  11. post-comments     (depends on posts + users)
 *  12. pages             (no dependencies)
 *  13. assign-superadmins (depends on users)
 *  14. validate          (integrity report)
 *
 * Usage:
 *   php artisan migrate:all               # incremental (safe to re-run)
 *   php artisan migrate:all --fresh        # truncate all target tables first
 *   php artisan migrate:all --dry-run      # validate only
 *   php artisan migrate:all --skip=order-files  # skip file migration (faster dry runs)
 */
class MigrateAll extends Command
{
    protected $signature = 'migrate:all
                            {--fresh : Truncate all target tables before migrating}
                            {--dry-run : Run validate only, skip data import}
                            {--skip= : Comma-separated list of steps to skip: ad-campaigns,comment-templates,users,addresses,orders,order-comments,timeline,fix-merges,order-files,posts,post-comments,pages,assign-superadmins,validate}
                            {--chunk=500 : Batch size passed to each sub-command}';

    protected $description = 'Run all legacy WordPress → Laravel data migration steps in sequence';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║        Legacy → Laravel Migration         ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        if ($this->option('dry-run')) {
            return $this->runValidate();
        }

        $skip = array_map('trim', explode(',', $this->option('skip') ?? ''));
        $fresh = $this->option('fresh');
        $chunk = $this->option('chunk');
        $startTime = microtime(true);

        $steps = [
            'ad-campaigns' => fn () => $this->runStep('migrate:ad-campaigns', ['--fresh' => $fresh]),
            'comment-templates' => fn () => $this->runStep('migrate:comment-templates'),
            'users' => fn () => $this->runStep('migrate:users', ['--chunk' => $chunk, '--fresh' => $fresh]),
            'addresses' => fn () => $this->runStep('migrate:addresses', ['--fresh' => $fresh]),
            'orders' => fn () => $this->runStep('migrate:orders', ['--chunk' => $chunk, '--fresh' => $fresh]),
            'order-comments' => fn () => $this->runStep('migrate:order-comments', ['--chunk' => $chunk, '--fresh' => $fresh]),
            'timeline' => fn () => $this->runStep('migrate:timeline', ['--fresh' => $fresh]),
            'fix-merges' => fn () => $this->runStep('migrate:fix-merges'),
            'order-files' => fn () => $this->runStep('migrate:order-files', ['--chunk' => $chunk, '--fresh' => $fresh]),
            'posts' => fn () => $this->runStep('migrate:posts', ['--fresh' => $fresh]),
            'post-comments' => fn () => $this->runStep('migrate:post-comments', ['--fresh' => $fresh]),
            'pages' => fn () => $this->runStep('migrate:pages', ['--fresh' => $fresh]),
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

        $elapsed = round(microtime(true) - $startTime);
        $this->newLine();
        $this->info("Migration completed in {$elapsed}s.");

        if ($failed) {
            $this->error('Failed steps: '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function runStep(string $command, array $options = []): int
    {
        // Remove null/false options so they don't get passed as flags.
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
