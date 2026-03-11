<?php

namespace App\Jobs;

use App\Models\ImageCleanupRule;
use App\Services\ImageCleanupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupOrderFilesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $ruleType,
        public bool $dryRun = false,
        public string $triggeredBy = 'manual',
        public ?int $ruleId = null
    ) {
        if (! in_array($ruleType, [ImageCleanupRule::TYPE_DELETE, ImageCleanupRule::TYPE_COMPRESS], true)) {
            throw new \InvalidArgumentException("Invalid rule type: {$ruleType}");
        }
    }

    public function handle(ImageCleanupService $service): void
    {
        $service->run($this->ruleType, $this->dryRun, $this->triggeredBy, $this->ruleId);
    }
}
