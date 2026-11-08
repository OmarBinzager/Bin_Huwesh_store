<?php

namespace App\Observers;

use App\Jobs\InfoSoft\PostInvoiceToInfoSoftJob;
use App\Jobs\InfoSoft\PostReturnToInfoSoftJob;
use App\Model\InfoSoftSetting;
use App\Model\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $settings = InfoSoftSetting::getActive();
        if (!$settings->is_enabled) {
            return;
        }

        // Auto dispatch invoice for COD or already paid orders
        if (in_array($order->payment_status, ['paid', 'collected']) || $order->payment_method === 'cash_on_delivery') {
            PostInvoiceToInfoSoftJob::dispatch($order->id)->delay(now()->addSeconds(5));
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $settings = InfoSoftSetting::getActive();
        if (!$settings->is_enabled) {
            return;
        }

        // 1. If order was confirmed or delivered, and doesn't have an ERP invoice yet
        if ($order->isDirty('order_status')) {
            $newStatus = $order->order_status;

            if (in_array($newStatus, ['confirmed', 'processing', 'out_for_delivery', 'delivered']) && empty($order->erp_invoice_id)) {
                PostInvoiceToInfoSoftJob::dispatch($order->id);
            }

            // 2. If order was canceled or refunded, and already has an ERP invoice, dispatch return credit note
            if (in_array($newStatus, ['canceled', 'returned', 'refunded']) && !empty($order->erp_invoice_id)) {
                PostReturnToInfoSoftJob::dispatch($order->id);
            }
        }

        // 3. If payment status became paid and no ERP invoice yet
        if ($order->isDirty('payment_status') && $order->payment_status === 'paid' && empty($order->erp_invoice_id)) {
            PostInvoiceToInfoSoftJob::dispatch($order->id);
        }
    }
}
