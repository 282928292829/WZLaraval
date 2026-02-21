<?php

namespace App\Jobs;

use App\Models\UserActivityLog;
use App\Services\GeoIPService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchActivityGeoLocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 10;

    public function __construct(
        private readonly int    $logId,
        private readonly string $ip,
    ) {}

    public function handle(GeoIPService $geoIP): void
    {
        $log = UserActivityLog::find($this->logId);

        if (! $log) {
            return;
        }

        $geo = $geoIP->lookup($this->ip);

        $log->update([
            'country' => $geo['country'],
            'city'    => $geo['city'],
        ]);
    }
}
