<?php

namespace App\Jobs\InfoSoft;

use App\Model\InfoSoftSetting;
use App\Services\InfoSoft\InfoSoftCatalogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInfoSoftIncrementalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $days;

    public function __construct(int $days = 1)
    {
        $this->days = $days;
    }

    public function handle(InfoSoftCatalogService $catalogService): void
    {
        $settings = InfoSoftSetting::getActive();
        if (!$settings->is_enabled) {
            return;
        }

        Log::info("[InfoSoft] Starting incremental sync job for past {$this->days} day(s)...");
        $res = $catalogService->syncIncremental($this->days);
        Log::info('[InfoSoft] Incremental sync job completed', $res);
    }
}
