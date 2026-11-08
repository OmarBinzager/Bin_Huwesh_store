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

class PostReturnToInfoSoftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(InfoSoftInvoiceService $invoiceService): void
    {
        $settings = InfoSoftSetting::getActive();
        if (!$settings->is_enabled) {
            return;
        }

        $order = Order::find($this->orderId);
        if (!$order || empty($order->erp_invoice_id)) {
            Log::warning("[InfoSoft] Cannot dispatch return credit note. Order #{$this->orderId} missing or has no ERP invoice ID.");
            return;
        }

        $result = $invoiceService->postOrderReturn($order);

        if (!$result['status']) {
            $errorCode = $result['error_code'] ?? '';
            if (in_array($errorCode, ['-1003', '-501', '-502', 'EXCEPTION'])) {
                throw new \Exception("InfoSoft return transient error: " . ($result['message'] ?? ''));
            }
        }
    }
}
