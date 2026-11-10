<?php

namespace App\Services\InfoSoft;

use App\Model\InfoSoftSetting;
use Illuminate\Support\Facades\Log;

class InfoSoftStockService
{
    protected InfoSoftClient $client;
    protected InfoSoftSetting $settings;

    public function __construct(InfoSoftClient $client = null)
    {
        $this->client = $client ?? new InfoSoftClient();
        $this->settings = $this->client->getSettings();
    }

    /**
     * Check live available stock for selling (Qty - RsvQty)
     * Calls POST /api/GetStock
     *
     * @param array $skus Array of SKU or Item IDs
     * @param string|null $storeCode Store/Warehouse ID (defaults to settings warehouse)
     * @return array [sku => float_available_qty]
     */
    public function getAvailableStock(array $skus, ?string $storeCode = null): array
    {
        if (empty($skus)) {
            return [];
        }

        $store = $storeCode ?? $this->settings->default_warehouse ?? '01';

        $payload = [
            'store' => (string)$store,
            'sku'   => array_values(array_unique($skus)),
        ];

        $response = $this->client->post('api/GetStock', $payload);

        // When successful, InfoSoft returns a key-value dictionary directly
        if (is_array($response) && !isset($response['isSuccess']) && !isset($response['status'])) {
            return $response;
        }

        // If wrapped in a dictionary response
        if (is_array($response)) {
            unset($response['isSuccess'], $response['status'], $response['message']);
            return $response;
        }

        Log::warning('[InfoSoftStockService] Unexpected stock response', ['response' => $response]);
        return [];
    }

    /**
     * Real-time price check for items or barcodes
     * Calls POST /api/GetPrice
     *
     * @param array $skus
     * @param bool|null $isBarcode
     * @return array [sku => float_price]
     */
    public function getRealTimePrices(array $skus, ?bool $isBarcode = null): array
    {
        if (empty($skus)) {
            return [];
        }

        $barcodeFlag = $isBarcode ?? $this->settings->is_barcode ?? true;

        $payload = [
            'sku'       => array_values(array_unique($skus)),
            'isBarcode' => (bool)$barcodeFlag,
        ];

        $response = $this->client->post('api/GetPrice', $payload);

        if (is_array($response) && !isset($response['isSuccess']) && !isset($response['status'])) {
            return $response;
        }

        if (is_array($response)) {
            unset($response['isSuccess'], $response['status'], $response['message']);
            return $response;
        }

        return [];
    }

    /**
     * Validate an entire cart of items before checkout
     * Returns ['valid' => bool, 'out_of_stock' => [...]]
     */
    public function validateCartStock(array $cartItems): array
    {
        if (!$this->settings->is_enabled || !$this->settings->live_stock_check) {
            return ['valid' => true, 'out_of_stock' => []];
        }

        $skusToCheck = [];
        $itemToSkuMap = [];

        foreach ($cartItems as $index => $item) {
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            $variant = $item['variant'] ?? $item['variation'] ?? null;

            $sku = null;
            if ($productId && !empty($variant)) {
                $mapping = \App\Model\InfoSoftProductMapping::where('product_id', $productId)
                    ->where('variation_type', $variant)
                    ->first();
                if ($mapping) {
                    $sku = $mapping->erp_item_id;
                }
            }

            if (!$sku && $productId) {
                $mapping = \App\Model\InfoSoftProductMapping::where('product_id', $productId)->first();
                if ($mapping) {
                    $sku = $mapping->erp_item_id;
                }
            }

            if (!$sku) {
                $sku = $item['erp_item_id'] ?? $item['sku'] ?? $productId;
            }

            if ($sku) {
                $skuStr = (string)$sku;
                $skusToCheck[] = $skuStr;
                $itemToSkuMap[$index] = $skuStr;
            }
        }

        if (empty($skusToCheck)) {
            return ['valid' => true, 'out_of_stock' => []];
        }

        $stockMap = $this->getAvailableStock($skusToCheck);
        $outOfStock = [];

        foreach ($cartItems as $index => $item) {
            $sku = $itemToSkuMap[$index] ?? (string)($item['erp_item_id'] ?? $item['sku'] ?? $item['product_id'] ?? '');
            $requestedQty = (float)($item['quantity'] ?? 1);

            if (isset($stockMap[$sku])) {
                $available = (float)$stockMap[$sku];
                if ($available < $requestedQty) {
                    $variantLabel = !empty($item['variant']) ? " ({$item['variant']})" : '';
                    $outOfStock[] = [
                        'sku'            => $sku,
                        'name'           => ($item['name'] ?? 'Product') . $variantLabel,
                        'requested_qty'  => $requestedQty,
                        'available_stock'=> $available,
                    ];
                }
            }
        }

        return [
            'valid'        => empty($outOfStock),
            'out_of_stock' => $outOfStock,
        ];
    }
}
