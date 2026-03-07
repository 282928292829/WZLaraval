<?php

namespace App\Jobs;

use App\Services\ImageCleanupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupOrderFilesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public bool $dryRun = false,
        public string $triggeredBy = 'manual'
    ) {}

    public function handle(ImageCleanupService $service): void
    {
        $service->run($this->dryRun, $this->triggeredBy);
    }
}
