<?php

namespace App\Services\InfoSoft;

use App\Model\Category;
use App\Model\InfoSoftPackingUnit;
use App\Model\InfoSoftProductMapping;
use App\Model\InfoSoftSetting;
use App\Model\Product;
use App\Model\Translation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InfoSoftCatalogService
{
    protected InfoSoftClient $client;
    protected InfoSoftSetting $settings;

    public function __construct(InfoSoftClient $client = null)
    {
        $this->client = $client ?? new InfoSoftClient();
        $this->settings = $this->client->getSettings();
    }

    /**
     * Ingest and update packaging units from /api/GetPacking
     */
    public function syncPackingUnits(): array
    {
        $response = $this->client->get('api/GetPacking');

        if (!is_array($response)) {
            // Try alternate path mentioned in docs: /api/GePacking
            $response = $this->client->get('api/GePacking');
        }

        if (!is_array($response)) {
            return ['status' => false, 'message' => 'Failed to fetch packing units from ERP.'];
        }

        $count = 0;
        foreach ($response as $unit) {
            if (!empty($unit['pack_id'])) {
                InfoSoftPackingUnit::updateOrCreate(
                    ['pack_id' => (string)$unit['pack_id']],
                    [
                        'name_ar'        => $unit['NameAR'] ?? $unit['name_ar'] ?? '',
                        'name_en'        => $unit['NameEN'] ?? $unit['name_en'] ?? null,
                        'default_pk_qty' => (float)($unit['default_pkQty'] ?? 1.0),
                    ]
                );
                $count++;
            }
        }

        return ['status' => true, 'count' => $count, 'message' => "Successfully synced {$count} packaging units."];
    }

    /**
     * Sync single page of items from /api/GetAllItems
     */
    public function fetchCatalogPage(int $page = 1, int $pageSize = 200): array
    {
        $params = [
            'warehouseNo' => $this->settings->default_warehouse ?? '01',
            'brno'        => $this->settings->default_branch ?? '01',
            'isBarcode'   => (bool)($this->settings->is_barcode ?? true),
            'page'        => $page,
            'pageSize'    => min($pageSize, 1000),
        ];

        return $this->client->get('api/GetAllItems', $params);
    }

    /**
     * Ingest full catalog and map products
     */
    public function syncFullCatalog(int $maxPages = 50): array
    {
        $page = 1;
        $totalSynced = 0;
        $totalItems = 0;

        do {
            $data = $this->fetchCatalogPage($page, 200);

            // Check if Paged Mode response or array
            $items = [];
            $totalPages = 1;

            if (isset($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
                $totalPages = $data['totalPages'] ?? 1;
                $totalItems = $data['totalItems'] ?? count($items);
            } elseif (is_array($data) && array_is_list($data)) {
                $items = $data;
                $totalPages = 1;
            }

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $this->upsertItem($item);
                $totalSynced++;
            }

            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        $this->settings->update(['last_sync_at' => now()]);

        return [
            'status'      => true,
            'synced'      => $totalSynced,
            'total_items' => $totalItems,
            'message'     => "Full catalog sync finished: {$totalSynced} items processed.",
        ];
    }

    /**
     * Incremental sync for recently updated items via /api/GetEditedItems
     */
    public function syncIncremental(int $days = 1): array
    {
        $days = max(1, $days ?: ($this->settings->sync_days_lookback ?? 1));

        $params = [
            'warehouseNo' => $this->settings->default_warehouse ?? '01',
            'brno'        => $this->settings->default_branch ?? '01',
            'isBarcode'   => (bool)($this->settings->is_barcode ?? true),
            'days'        => $days,
            'page'        => 1,
            'pageSize'    => 500,
        ];

        $editedData = $this->client->get('api/GetEditedItems', $params);
        $items = $editedData['items'] ?? (is_array($editedData) && array_is_list($editedData) ? $editedData : []);

        $synced = 0;
        foreach ($items as $item) {
            $this->upsertItem($item);
            $synced++;
        }

        // Also check newly created items
        $newData = $this->client->get('api/GetNewItems', $params);
        $newItems = $newData['items'] ?? (is_array($newData) && array_is_list($newData) ? $newData : []);

        foreach ($newItems as $item) {
            $this->upsertItem($item);
            $synced++;
        }

        $this->settings->update(['last_sync_at' => now()]);

        return [
            'status'  => true,
            'synced'  => $synced,
            'message' => "Incremental sync completed. {$synced} items updated from past {$days} day(s).",
        ];
    }

    /**
     * Upsert single InfoSoft item into store database
     */
    public function upsertItem(array $erpItem): ?Product
    {
        $itemId = trim($erpItem['itemID'] ?? '');
        if (empty($itemId)) {
            return null;
        }

        $nameAr = $erpItem['nameAR'] ?? "صنف {$itemId}";
        $nameEn = $erpItem['nameEN'] ?? $nameAr;
        $price = (float)($erpItem['price'] ?? 0);
        $cost = (float)($erpItem['cost'] ?? 0);
        $stock = (int)($erpItem['quantity'] ?? 0);
        $barcode = $erpItem['barcode'] ?? null;
        $specialPrice = !empty($erpItem['specialPrice']) ? (float)$erpItem['specialPrice'] : null;

        // Composite key cmbkey logic: itemID + package ID (default unit "01")
        $cmbkey = $erpItem['cmbkey'] ?? ($itemId . '01');

        // Check if product exists by erp_item_id or barcode
        $product = Product::where('erp_item_id', $itemId)
            ->orWhere(function ($q) use ($barcode) {
                if (!empty($barcode)) {
                    $q->where('erp_barcode', $barcode);
                }
            })
            ->first();

        // Calculate discount if special price is present
        $discount = 0;
        $discountType = 'percent';
        if ($specialPrice && $specialPrice < $price && $price > 0) {
            $discount = round((($price - $specialPrice) / $price) * 100, 2);
        }

        if (!$product) {
            $product = new Product();
            $product->name = $nameAr;
            $product->description = $nameAr;
            $product->image = json_encode(['def.png']);
            $product->category_ids = json_encode([]);
            $product->status = 1;
            $product->tax = 0.0;
            $product->tax_type = 'percent';
            $product->unit = 'piece';
            $product->capacity = 1.0;
            $product->attributes = json_encode([]);
            $product->choice_options = json_encode([]);
            $product->variations = json_encode([]);
        }

        $product->name = $nameAr;
        $product->erp_item_id = $itemId;
        $product->erp_cmbkey = $cmbkey;
        $product->erp_barcode = $barcode;
        $product->price = $price;
        $product->purchase_price = $cost;
        $product->total_stock = max(0, $stock);
        if ($discount > 0) {
            $product->discount = $discount;
            $product->discount_type = 'percent';
        }

        $product->save();

        // Update translations
        Translation::updateOrInsert(
            [
                'translationable_type' => 'App\Model\Product',
                'translationable_id'   => $product->id,
                'locale'               => 'ar',
                'key'                  => 'name',
            ],
            ['value' => $nameAr]
        );

        Translation::updateOrInsert(
            [
                'translationable_type' => 'App\Model\Product',
                'translationable_id'   => $product->id,
                'locale'               => 'en',
                'key'                  => 'name',
            ],
            ['value' => $nameEn]
        );

        // Update product mapping
        InfoSoftProductMapping::updateOrCreate(
            [
                'product_id'  => $product->id,
                'erp_item_id' => $itemId,
                'erp_cmbkey'  => $cmbkey,
            ],
            [
                'barcode'           => $barcode,
                'pack_name'         => 'حبة',
                'pack_qty'          => 1.0,
                'last_synced_price' => $price,
                'last_synced_stock' => $stock,
                'synced_at'         => now(),
            ]
        );

        // Also process package variants if barcodes array provided
        if (!empty($erpItem['barcodes']) && is_array($erpItem['barcodes'])) {
            foreach ($erpItem['barcodes'] as $b) {
                $pBarcode = $b['pBarcode'] ?? null;
                $packName = $b['pack'] ?? 'وحدة';
                $pkQty = (float)($b['pkQty'] ?? 1.0);
                $pPrice = (float)($b['lPrice1'] ?? $price);

                if ($pBarcode) {
                    InfoSoftProductMapping::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'barcode'    => $pBarcode,
                        ],
                        [
                            'erp_item_id'       => $itemId,
                            'erp_cmbkey'        => $itemId . str_pad((string)$pkQty, 2, '0', STR_PAD_LEFT),
                            'pack_name'         => $packName,
                            'pack_qty'          => $pkQty,
                            'last_synced_price' => $pPrice,
                            'last_synced_stock' => $stock,
                            'synced_at'         => now(),
                        ]
                    );
                }
            }
        }

        return $product;
    }
}
