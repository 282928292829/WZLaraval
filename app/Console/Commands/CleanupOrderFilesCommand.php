<?php

namespace App\Console\Commands;

use App\Jobs\CleanupOrderFilesJob;
use App\Models\ImageCleanupRule;
use App\Models\Setting;
use App\Services\ImageCleanupService;
use Illuminate\Console\Command;

class CleanupOrderFilesCommand extends Command
{
    protected $signature = 'orders:cleanup-files
                            {--type= : Rule type: delete or compress (required for manual runs)}
                            {--dry-run : Preview without deleting or compressing}
                            {--sync : Run synchronously instead of dispatching to queue}
                            {--scheduled : Invoked by scheduler; checks per-type schedule settings}';

    protected $description = 'Clean up or compress order files based on image cleanup rules';

    public function handle(ImageCleanupService $service): int
    {
        $dryRun = $this->option('dry-run');
        $sync = $this->option('sync');
        $scheduled = $this->option('scheduled');

        if ($scheduled) {
            return $this->runScheduled($service);
        }

        $type = $this->option('type');
        if (! $type || ! in_array($type, [ImageCleanupRule::TYPE_DELETE, ImageCleanupRule::TYPE_COMPRESS], true)) {
            $this->error(__('image_cleanup.cli_type_required'));

            return self::FAILURE;
        }

        if ($sync) {
            $result = $service->run($type, $dryRun, 'manual');
        } else {
            CleanupOrderFilesJob::dispatch($type, $dryRun, 'manual');
            $this->info(__('image_cleanup.job_dispatched'));

            return self::SUCCESS;
        }

        return $this->handleResult($result);
    }

    private function runScheduled(ImageCleanupService $service): int
    {
        $now = now();
        $hour = (int) $now->format('G');
        $dayOfWeek = (int) $now->format('w');
        $ran = false;

        foreach ([ImageCleanupRule::TYPE_DELETE, ImageCleanupRule::TYPE_COMPRESS] as $ruleType) {
            $prefix = "image_cleanup_{$ruleType}_schedule_";
            if (! Setting::get($prefix.'enabled', false)) {
                continue;
            }
            if ((int) Setting::get($prefix.'hour', 2) !== $hour) {
                continue;
            }
            $frequency = Setting::get($prefix.'frequency', 'daily');
            if ($frequency === 'weekly' && (int) Setting::get($prefix.'day', 0) !== $dayOfWeek) {
                continue;
            }

            $result = $service->run($ruleType, false, 'scheduled');
            if (isset($result['details'][0]['error'])) {
                $this->error($result['details'][0]['error']);

                return self::FAILURE;
            }
            $this->info(__('image_cleanup.result_summary', [
                'orders' => $result['orders_processed'],
                'deleted' => $result['files_deleted'],
                'compressed' => $result['files_compressed'],
                'bytes' => $this->formatBytes($result['bytes_freed']),
            ]));
            $ran = true;
        }

        return $ran ? self::SUCCESS : self::SUCCESS;
    }

    private function handleResult(array $result): int
    {
        if (isset($result['details'][0]['error'])) {
            $this->error($result['details'][0]['error']);

            return self::FAILURE;
        }

        if (isset($result['details'][0]['info'])) {
            $this->info($result['details'][0]['info']);

            return self::SUCCESS;
        }

        $this->info(__('image_cleanup.result_summary', [
            'orders' => $result['orders_processed'],
            'deleted' => $result['files_deleted'],
            'compressed' => $result['files_compressed'],
            'bytes' => $this->formatBytes($result['bytes_freed']),
        ]));

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
