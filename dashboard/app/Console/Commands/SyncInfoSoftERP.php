<?php

namespace App\Console\Commands;

use App\Model\InfoSoftSetting;
use App\Services\InfoSoft\InfoSoftCatalogService;
use Illuminate\Console\Command;

class SyncInfoSoftERP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'infosoft:sync {--type=incremental : "incremental", "full", or "packing"} {--days=1 : Days lookback for incremental sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize catalog, stock and packaging units with InfoSoft ERP';

    /**
     * Execute the console command.
     */
    public function handle(InfoSoftCatalogService $catalogService): int
    {
        $settings = InfoSoftSetting::getActive();

        if (!$settings->is_enabled) {
            $this->warn('InfoSoft ERP integration is currently disabled in settings.');
            return 0;
        }

        $type = $this->option('type') ?? 'incremental';
        $days = (int)($this->option('days') ?? $settings->sync_days_lookback ?? 1);

        $this->info("Starting InfoSoft ERP sync [Type: {$type}]...");

        try {
            if ($type === 'full') {
                $catalogService->syncPackingUnits();
                $res = $catalogService->syncFullCatalog(50);
                $this->info($res['message']);
            } elseif ($type === 'packing') {
                $res = $catalogService->syncPackingUnits();
                $this->info($res['message']);
            } else {
                $res = $catalogService->syncIncremental($days);
                $this->info($res['message']);
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }
}
