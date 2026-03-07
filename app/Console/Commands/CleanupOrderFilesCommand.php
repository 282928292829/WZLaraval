<?php

namespace App\Console\Commands;

use App\Jobs\CleanupOrderFilesJob;
use App\Models\Setting;
use App\Services\ImageCleanupService;
use Illuminate\Console\Command;

class CleanupOrderFilesCommand extends Command
{
    protected $signature = 'orders:cleanup-files
                            {--dry-run : Preview without deleting or compressing}
                            {--sync : Run synchronously instead of dispatching to queue}
                            {--scheduled : Invoked by scheduler; checks settings before running}';

    protected $description = 'Clean up or compress order files based on configured triggers (status, retention, toggles)';

    public function handle(ImageCleanupService $service): int
    {
        $dryRun = $this->option('dry-run');
        $sync = $this->option('sync');
        $scheduled = $this->option('scheduled');

        if ($scheduled) {
            if (! Setting::get('image_cleanup_schedule_enabled', false)) {
                return self::SUCCESS;
            }
            $hour = (int) Setting::get('image_cleanup_schedule_hour', 2);
            $day = (int) Setting::get('image_cleanup_schedule_day', 0);
            $frequency = Setting::get('image_cleanup_schedule_frequency', 'daily');

            $now = now();
            if ((int) $now->format('G') !== $hour) {
                return self::SUCCESS;
            }
            if ($frequency === 'weekly' && (int) $now->format('w') !== $day) {
                return self::SUCCESS;
            }

            $result = $service->run(false, 'scheduled');
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

            return self::SUCCESS;
        }

        if ($sync) {
            $result = $service->run($dryRun, 'manual');
        } else {
            CleanupOrderFilesJob::dispatch($dryRun, 'manual');
            $this->info(__('image_cleanup.job_dispatched'));

            return self::SUCCESS;
        }

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
