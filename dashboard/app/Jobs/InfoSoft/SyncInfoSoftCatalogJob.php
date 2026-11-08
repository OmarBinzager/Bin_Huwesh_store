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

class SyncInfoSoftCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes

    public function handle(InfoSoftCatalogService $catalogService): void
    {
        $settings = InfoSoftSetting::getActive();
        if (!$settings->is_enabled) {
            return;
        }

        Log::info('[InfoSoft] Starting full catalog sync job...');
        $catalogService->syncPackingUnits();
        $res = $catalogService->syncFullCatalog(50);
        Log::info('[InfoSoft] Full catalog sync job completed', $res);
    }
}
