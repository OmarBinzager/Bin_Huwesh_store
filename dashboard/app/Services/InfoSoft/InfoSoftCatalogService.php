<?php

namespace App\Services\InfoSoft;

use App\Model\Category;
use App\Model\InfoSoftPackingUnit;
use App\Model\InfoSoftProductMapping;
use App\Model\InfoSoftSetting;
use App\Model\InfoSoftVariantDictionary;
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
     * Ingest full catalog with intelligent variant aggregation
     */
    public function syncFullCatalog(int $maxPages = 50): array
    {
        $page = 1;
        $totalSynced = 0;
        $totalItems = 0;

        do {
            $data = $this->fetchCatalogPage($page, 200);

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

            $syncedInBatch = $this->processItemsBatch($items);
            $totalSynced += $syncedInBatch;

            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        $this->settings->update(['last_sync_at' => now()]);

        return [
            'status'      => true,
            'synced'      => $totalSynced,
            'total_items' => $totalItems,
            'message'     => "Full catalog sync finished: {$totalSynced} items/variants processed.",
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
        if (!empty($items)) {
            $synced += $this->processItemsBatch($items);
        }

        // Also check newly created items
        $newData = $this->client->get('api/GetNewItems', $params);
        $newItems = $newData['items'] ?? (is_array($newData) && array_is_list($newData) ? $newData : []);

        if (!empty($newItems)) {
            $synced += $this->processItemsBatch($newItems);
        }

        $this->settings->update(['last_sync_at' => now()]);

        return [
            'status'  => true,
            'synced'  => $synced,
            'message' => "Incremental sync completed. {$synced} items/variants updated from past {$days} day(s).",
        ];
    }

    /**
     * Group a batch of InfoSoft items by Parent ID and upsert either as standalone or variant matrix
     */
    public function processItemsBatch(array $items): int
    {
        $grouped = [];

        foreach ($items as $item) {
            $itemId = trim((string)($item['itemID'] ?? ''));
            if (empty($itemId)) {
                continue;
            }

            $parsed = $this->parseItemIdentifier($itemId);
            $parentId = $parsed['parent_id'];

            $item['__parsed'] = $parsed;
            $grouped[$parentId][] = $item;
        }

        $processedCount = 0;

        foreach ($grouped as $parentId => $groupItems) {
            $hasVariants = false;
            foreach ($groupItems as $g) {
                if (!empty($g['__parsed']['is_variant'])) {
                    $hasVariants = true;
                    break;
                }
            }

            // Check if product already exists in DB as a variant product
            if (!$hasVariants) {
                $existingMapping = InfoSoftProductMapping::where('erp_item_id', $parentId)
                    ->orWhere(function ($q) use ($parentId) {
                        $q->where('erp_item_id', 'LIKE', "{$parentId}-%");
                    })
                    ->first();
                if ($existingMapping && !empty($existingMapping->variation_type)) {
                    $hasVariants = true;
                }
            }

            if ($hasVariants) {
                $this->upsertVariantGroup($parentId, $groupItems);
            } else {
                foreach ($groupItems as $singleItem) {
                    $this->upsertSingleItem($singleItem);
                }
            }

            $processedCount += count($groupItems);
        }

        return $processedCount;
    }

    /**
     * Parse an InfoSoft itemID into parent ID, color, and size
     * Expected pattern: {ParentID}-{Color}-{Size} or {ParentID}-{Variant}
     */
    public function parseItemIdentifier(string $itemId): array
    {
        $separator = $this->settings->variant_separator ?: '-';
        $isGroupingEnabled = $this->settings->variant_grouping_enabled ?? true;

        if (!$isGroupingEnabled || !str_contains($itemId, $separator)) {
            return [
                'is_variant' => false,
                'parent_id'  => trim($itemId),
                'color'      => null,
                'size'       => null,
            ];
        }

        $parts = array_values(array_filter(array_map('trim', explode($separator, $itemId)), fn($p) => $p !== ''));

        if (count($parts) <= 1) {
            return [
                'is_variant' => false,
                'parent_id'  => $parts[0] ?? trim($itemId),
                'color'      => null,
                'size'       => null,
            ];
        }

        $parentId = $parts[0];

        if (count($parts) >= 3) {
            // e.g. 100-BLACK-S or SHIRT-RED-XL
            $colorRaw = $parts[1];
            $sizeRaw = $parts[2];
            return [
                'is_variant' => true,
                'parent_id'  => $parentId,
                'color'      => $colorRaw,
                'size'       => $sizeRaw,
            ];
        }

        // Exactly 2 parts: e.g. 100-BLACK or 100-XL
        $secondPart = $parts[1];
        if (InfoSoftVariantDictionary::isSize($secondPart)) {
            return [
                'is_variant' => true,
                'parent_id'  => $parentId,
                'color'      => null,
                'size'       => $secondPart,
            ];
        } else {
            return [
                'is_variant' => true,
                'parent_id'  => $parentId,
                'color'      => $secondPart,
                'size'       => null,
            ];
        }
    }

    /**
     * Clean raw ERP product title by stripping specific color and size suffixes
     */
    public function cleanParentTitle(string $title, ?string $color, ?string $size): string
    {
        $clean = $title;
        if (!empty($color)) {
            $clean = preg_replace('/[\s\-]+' . preg_quote($color, '/') . '(?=[\s\-]|$)/iu', '', $clean);
        }
        if (!empty($size)) {
            $clean = preg_replace('/[\s\-]+' . preg_quote($size, '/') . '(?=[\s\-]|$)/iu', '', $clean);
        }
        $clean = trim(preg_replace('/[\s\-]+$/u', '', (string)$clean));
        return !empty($clean) ? $clean : $title;
    }

    /**
     * Upsert a grouped set of InfoSoft items under one single parent Product with variations
     */
    public function upsertVariantGroup(string $parentId, array $variantItems): ?Product
    {
        if (empty($variantItems)) {
            return null;
        }

        $firstItem = $variantItems[0];
        $firstParsed = $firstItem['__parsed'] ?? $this->parseItemIdentifier($firstItem['itemID']);

        // Clean parent product name
        $nameAr = $this->cleanParentTitle(
            $firstItem['nameAR'] ?? "منتج {$parentId}",
            $firstParsed['color'],
            $firstParsed['size']
        );
        $nameEn = $this->cleanParentTitle(
            $firstItem['nameEN'] ?? $nameAr,
            $firstParsed['color'],
            $firstParsed['size']
        );

        // Find or create parent product
        $product = Product::where('erp_item_id', $parentId)->first();

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
        }

        // Aggregate stock, prices and build variations matrix
        $totalStock = 0;
        $minPrice = PHP_FLOAT_MAX;
        $baseCost = 0.0;

        $variations = [];
        $uniqueColors = [];
        $uniqueSizes = [];

        foreach ($variantItems as $item) {
            $parsed = $item['__parsed'] ?? $this->parseItemIdentifier($item['itemID']);
            $rawColor = $parsed['color'];
            $rawSize = $parsed['size'];

            $normColor = $rawColor ? InfoSoftVariantDictionary::normalize('color', $rawColor) : null;
            $normSize = $rawSize ? InfoSoftVariantDictionary::normalize('size', $rawSize) : null;

            $colorKey = $normColor ? $normColor['en'] : '';
            $sizeKey = $normSize ? $normSize['en'] : '';

            $varType = trim($colorKey . ($colorKey && $sizeKey ? '-' : '') . $sizeKey, '-');
            if (empty($varType)) {
                $varType = $item['itemID'];
            }

            $price = (float)($item['price'] ?? 0);
            $cost = (float)($item['cost'] ?? 0);
            $qty = max(0, (int)($item['quantity'] ?? 0));

            $totalStock += $qty;
            if ($price > 0 && $price < $minPrice) {
                $minPrice = $price;
                $baseCost = $cost;
            }

            if ($normColor && !empty($normColor['ar'])) {
                $uniqueColors[$normColor['ar']] = $normColor;
            }
            if ($normSize && !empty($normSize['en'])) {
                $uniqueSizes[$normSize['en']] = $normSize['en'];
            }

            $variations[] = [
                'type'           => $varType,
                'price'          => $price,
                'stock'          => $qty,
                'purchase_price' => $cost,
                'sku'            => (string)$item['itemID'],
                'barcode'        => (string)($item['barcode'] ?? ''),
            ];

            // Update InfoSoft Product Mapping for this exact variant
            InfoSoftProductMapping::updateOrCreate(
                [
                    'product_id'  => $product->id ?: 0,
                    'erp_item_id' => (string)$item['itemID'],
                ],
                [
                    'variation_type'    => $varType,
                    'color'             => $normColor['ar'] ?? null,
                    'size'              => $normSize['ar'] ?? null,
                    'erp_cmbkey'        => $item['cmbkey'] ?? ($item['itemID'] . '01'),
                    'barcode'           => $item['barcode'] ?? null,
                    'pack_name'         => 'حبة',
                    'pack_qty'          => 1.0,
                    'last_synced_price' => $price,
                    'last_synced_stock' => $qty,
                    'synced_at'         => now(),
                ]
            );
        }

        if ($minPrice === PHP_FLOAT_MAX) {
            $minPrice = (float)($firstItem['price'] ?? 0);
        }

        // Build choice_options and attributes
        $choiceOptions = [];
        $attributeIds = [];

        if (!empty($uniqueSizes)) {
            $attributeIds[] = 2; // Size attribute
            $choiceOptions[] = [
                'name'    => 'choice_2',
                'title'   => 'Size',
                'options' => array_values($uniqueSizes),
            ];
        }

        // Build color images/palette
        $colorImages = [];
        if (!empty($uniqueColors)) {
            $attributeIds[] = 3; // Color attribute
            foreach ($uniqueColors as $c) {
                $colorImages[] = [
                    'name'       => $c['ar'],
                    'color'      => $c['hex'] ?: '#000000',
                    'color_code' => $c['hex'] ?: '#000000',
                    'image'      => null,
                ];
            }
        }

        // Save Parent Product
        $product->name = $nameAr;
        $product->erp_item_id = $parentId;
        $product->erp_cmbkey = $parentId . '01';
        $product->price = $minPrice;
        $product->purchase_price = $baseCost;
        $product->total_stock = $totalStock;
        $product->attributes = json_encode($attributeIds);
        $product->choice_options = json_encode($choiceOptions);
        $product->variations = json_encode($variations);
        if (!empty($colorImages)) {
            $product->color_images = $colorImages;
        }
        $product->save();

        // Ensure newly created product_id is assigned to all mappings
        InfoSoftProductMapping::where('product_id', 0)
            ->whereIn('erp_item_id', array_column($variantItems, 'itemID'))
            ->update(['product_id' => $product->id]);

        // Translations
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

        return $product;
    }

    /**
     * Upsert single standalone InfoSoft item into store database
     */
    public function upsertSingleItem(array $erpItem): ?Product
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

        $cmbkey = $erpItem['cmbkey'] ?? ($itemId . '01');

        $product = Product::where('erp_item_id', $itemId)
            ->orWhere(function ($q) use ($barcode) {
                if (!empty($barcode)) {
                    $q->where('erp_barcode', $barcode);
                }
            })
            ->first();

        $discount = 0;
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

        InfoSoftProductMapping::updateOrCreate(
            [
                'product_id'  => $product->id,
                'erp_item_id' => $itemId,
            ],
            [
                'variation_type'    => null,
                'color'             => null,
                'size'              => null,
                'erp_cmbkey'        => $cmbkey,
                'barcode'           => $barcode,
                'pack_name'         => 'حبة',
                'pack_qty'          => 1.0,
                'last_synced_price' => $price,
                'last_synced_stock' => $stock,
                'synced_at'         => now(),
            ]
        );

        return $product;
    }
}
