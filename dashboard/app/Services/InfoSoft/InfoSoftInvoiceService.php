<?php

namespace App\Services\InfoSoft;

use App\Model\InfoSoftInvoiceLog;
use App\Model\InfoSoftProductMapping;
use App\Model\InfoSoftSetting;
use App\Model\Order;
use App\Model\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InfoSoftInvoiceService
{
    protected InfoSoftClient $client;
    protected InfoSoftSetting $settings;

    public function __construct(InfoSoftClient $client = null)
    {
        $this->client = $client ?? new InfoSoftClient();
        $this->settings = $this->client->getSettings();
    }

    /**
     * Build payload and post sales invoice to InfoSoft (ZATCA Phase 2 compliant)
     * Calls POST /api/PostInvoice
     */
    public function postOrderInvoice(Order $order): array
    {
        $log = InfoSoftInvoiceLog::create([
            'order_id' => $order->id,
            'type'     => 'invoice',
            'status'   => 'pending',
            'attempts' => 1,
        ]);

        try {
            $order->loadMissing(['details.product', 'customer']);

            // Determine invoice type: 04 for B2B if tax number exists, else 06 B2C Simplified
            $taxNo = $order->customer->tax_number ?? $order->customer->vat_number ?? null;
            $ltrtype = !empty($taxNo) ? '04' : ($this->settings->default_invoice_type ?? '06');

            // Line items
            $lineItems = [];
            $totalVat = (float)($order->total_tax_amount ?? 0);
            $totalDiscount = (float)($order->coupon_discount_amount ?? 0) + (float)($order->extra_discount ?? 0);

            foreach ($order->details as $detail) {
                $product = $detail->product;
                $itemId = $product->erp_item_id ?? (string)$detail->product_id;
                $cmbkey = $product->erp_cmbkey ?? ($itemId . '01');
                $barcode = $product->erp_barcode ?? null;

                // Lookup packaging mapping if available
                $mapping = InfoSoftProductMapping::where('product_id', $detail->product_id)->first();
                if ($mapping) {
                    $cmbkey = $mapping->erp_cmbkey ?: $cmbkey;
                    $barcode = $mapping->barcode ?: $barcode;
                }

                $qty = (float)($detail->quantity ?? 1);
                $unitPrice = (float)($detail->price ?? 0);
                $discountOnProduct = (float)($detail->discount_on_product ?? 0);
                $discpc = ($unitPrice > 0 && $discountOnProduct > 0)
                    ? round(($discountOnProduct / ($unitPrice * $qty)) * 100, 2)
                    : 0.0;

                $lineItems[] = [
                    'cmbkey'  => (string)$cmbkey,
                    'barcode' => (string)($barcode ?? ''),
                    'qty'     => $qty,
                    'fqty'    => 0.0,
                    'price'   => $unitPrice,
                    'discpc'  => $discpc,
                ];
            }

            // Delivery charge and onus
            $deliveryCharge = (float)($order->delivery_charge ?? 0);
            $isFreeDelivery = ($order->free_delivery_amount > 0 && $deliveryCharge == 0);
            $transportOnus = $isFreeDelivery ? 1 : 0; // 0: on customer, 1: on merchant

            $paymentMethod = $order->payment_method ?? 'COD';
            $customerCode = $this->settings->default_customer_code ?? '01001';

            $payload = [
                'ltrtype'        => (string)$ltrtype,
                'SLCNTR'         => (string)($this->settings->sales_center ?? '01'),
                'lcustcode'      => (string)$customerCode,
                'hinvdsvl'       => (float)$totalDiscount,
                'hinvdspc'       => 0.0,
                'hvat_amt_rcvd'  => (float)$totalVat,
                'sales_htaxfree' => 0.0,
                'BRANCH'         => (string)($this->settings->default_branch ?? '01'),
                'USRID'          => (string)($this->settings->api_user ?? 'API_USER'),
                'Description'    => "Online Order #{$order->id}",
                'remarks2'       => "Method: {$paymentMethod} | Status: {$order->order_status}",
                'datetime_stamp' => Carbon::parse($order->created_at)->format('Y-m-d\TH:i:s'),
                'transport_amt'  => $deliveryCharge,
                'transport_onus' => $transportOnus,
                'SLPRSN'         => (string)($this->settings->sales_person ?? 'WEBSITE'),
                'API_Inv'        => $lineItems,
            ];

            if (!empty($taxNo)) {
                $payload['Taxno'] = (string)$taxNo;
            }

            if (!empty($this->settings->shipping_account)) {
                $payload['other_acct'] = (string)$this->settings->shipping_account;
                $payload['other_amt'] = $deliveryCharge;
            }

            $log->update(['request_payload' => $payload]);

            // Dispatch HTTP request
            $response = $this->client->post('api/PostInvoice', $payload);

            $log->update(['response_payload' => $response]);

            $isSuccess = (!empty($response['status']) && $response['status'] === true)
                || (isset($response['invoiceId']) && (int)$response['invoiceId'] > 0);

            if ($isSuccess) {
                $erpInvoiceId = (string)$response['invoiceId'];

                $order->update([
                    'erp_invoice_id'  => $erpInvoiceId,
                    'erp_sync_status' => 'synced',
                    'erp_sync_error'  => null,
                ]);

                $log->update([
                    'status'         => 'success',
                    'erp_invoice_id' => $erpInvoiceId,
                    'error_code'     => null,
                    'error_message'  => $response['message'] ?? 'Invoice posted successfully.',
                ]);

                return [
                    'status'     => true,
                    'invoiceId'  => $erpInvoiceId,
                    'message'    => "Invoice successfully posted to InfoSoft ERP (#{$erpInvoiceId}).",
                ];
            }

            // Failure handling
            $errorCode = (string)($response['invoiceId'] ?? '-1');
            $errorMessage = $response['message'] ?? 'Unknown ERP Invoice Error';

            $order->update([
                'erp_sync_status' => 'failed',
                'erp_sync_error'  => "Error {$errorCode}: {$errorMessage}",
            ]);

            $log->update([
                'status'        => 'failed',
                'error_code'    => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return [
                'status'     => false,
                'error_code' => $errorCode,
                'message'    => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error("[InfoSoftInvoiceService] Exception creating invoice for order #{$order->id}: " . $e->getMessage());

            $order->update([
                'erp_sync_status' => 'failed',
                'erp_sync_error'  => $e->getMessage(),
            ]);

            $log->update([
                'status'        => 'failed',
                'error_code'    => 'EXCEPTION',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'status'     => false,
                'error_code' => 'EXCEPTION',
                'message'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Post a return/credit note for cancelled or returned order
     * Calls POST /api/PostInvoice with ltrtype 26 (cash) or 24 (credit) and hrtnref (original invoice)
     */
    public function postOrderReturn(Order $order, array $items = []): array
    {
        if (empty($order->erp_invoice_id)) {
            return [
                'status'  => false,
                'message' => 'Cannot create credit return: Original ERP Invoice ID not found on this order.',
            ];
        }

        $log = InfoSoftInvoiceLog::create([
            'order_id'       => $order->id,
            'erp_invoice_id' => $order->erp_invoice_id,
            'type'           => 'return',
            'status'         => 'pending',
            'attempts'       => 1,
        ]);

        try {
            $order->loadMissing(['details.product']);

            // Return type: 26 for cash return, 24 for credit return
            $ltrtype = '26';

            $lineItems = [];
            foreach ($order->details as $detail) {
                $product = $detail->product;
                $itemId = $product->erp_item_id ?? (string)$detail->product_id;
                $cmbkey = $product->erp_cmbkey ?? ($itemId . '01');

                $qty = (float)($detail->quantity ?? 1);
                $price = (float)($detail->price ?? 0);

                $lineItems[] = [
                    'cmbkey'  => (string)$cmbkey,
                    'barcode' => (string)($product->erp_barcode ?? ''),
                    'qty'     => $qty,
                    'fqty'    => 0.0,
                    'price'   => $price,
                    'discpc'  => 0.0,
                ];
            }

            $payload = [
                'ltrtype'        => (string)$ltrtype,
                'SLCNTR'         => (string)($this->settings->sales_center ?? '01'),
                'lcustcode'      => (string)($this->settings->default_customer_code ?? '01001'),
                'hinvdsvl'       => 0.0,
                'hinvdspc'       => 0.0,
                'hvat_amt_rcvd'  => (float)($order->total_tax_amount ?? 0),
                'sales_htaxfree' => 0.0,
                'hrtnref'        => (int)$order->erp_invoice_id, // Mandatory reference to original invoice
                'BRANCH'         => (string)($this->settings->default_branch ?? '01'),
                'USRID'          => (string)($this->settings->api_user ?? 'API_USER'),
                'Description'    => "Return / Credit Note for Order #{$order->id} (Ref #{$order->erp_invoice_id})",
                'datetime_stamp' => Carbon::now()->format('Y-m-d\TH:i:s'),
                'API_Inv'        => $lineItems,
            ];

            $log->update(['request_payload' => $payload]);

            $response = $this->client->post('api/PostInvoice', $payload);

            $log->update(['response_payload' => $response]);

            $isSuccess = (!empty($response['status']) && $response['status'] === true)
                || (isset($response['invoiceId']) && (int)$response['invoiceId'] > 0);

            if ($isSuccess) {
                $creditNoteId = (string)$response['invoiceId'];

                $log->update([
                    'status'        => 'success',
                    'error_code'    => null,
                    'error_message' => "Credit note created (#{$creditNoteId})",
                ]);

                return [
                    'status'    => true,
                    'invoiceId' => $creditNoteId,
                    'message'   => "Return credit note posted successfully to InfoSoft ERP (#{$creditNoteId}).",
                ];
            }

            $errorCode = (string)($response['invoiceId'] ?? '-1');
            $errorMessage = $response['message'] ?? 'Failed to post return credit note';

            $log->update([
                'status'        => 'failed',
                'error_code'    => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return [
                'status'     => false,
                'error_code' => $errorCode,
                'message'    => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error("[InfoSoftInvoiceService] Exception creating return credit note for order #{$order->id}: " . $e->getMessage());

            $log->update([
                'status'        => 'failed',
                'error_code'    => 'EXCEPTION',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
