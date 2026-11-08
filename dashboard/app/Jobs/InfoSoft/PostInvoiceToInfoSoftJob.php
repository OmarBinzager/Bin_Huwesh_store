<?php

namespace App\Jobs\InfoSoft;

use App\Model\InfoSoftSetting;
use App\Model\Order;
use App\Services\InfoSoft\InfoSoftInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostInvoiceToInfoSoftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;
    public int $tries = 3;
    public int $backoff = 60; // 1 min backoff

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(InfoSoftInvoiceService $invoiceService): void
    {
        $settings = InfoSoftSetting::getActive();
        if (!$settings->is_enabled) {
            Log::info("[InfoSoft] ERP integration is disabled. Skipping invoice for order #{$this->orderId}");
            return;
        }

        $order = Order::find($this->orderId);
        if (!$order) {
            Log::error("[InfoSoft] Order #{$this->orderId} not found for ERP invoice dispatch.");
            return;
        }

        if (!empty($order->erp_invoice_id)) {
            Log::info("[InfoSoft] Order #{$this->orderId} already has ERP invoice #{$order->erp_invoice_id}. Skipping.");
            return;
        }

        $result = $invoiceService->postOrderInvoice($order);

        if (!$result['status']) {
            $errorCode = $result['error_code'] ?? '';
            // If connection/server issue (-1003, -501), retry via queue
            if (in_array($errorCode, ['-1003', '-501', '-502', 'EXCEPTION'])) {
                throw new \Exception("InfoSoft transient error ({$errorCode}): " . ($result['message'] ?? ''));
            }
        }
    }
}
