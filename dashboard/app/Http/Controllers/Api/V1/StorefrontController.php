<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CategoryLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Brand;
use App\Model\BusinessSetting;
use App\Model\Category;
use App\Model\Product;
use App\Model\Review;
use App\Model\SocialMedia;
use App\Model\StorefrontCategoryCard;
use App\Model\StorefrontLook;
use App\Model\StorefrontLookItem;
use App\Model\StorefrontRailItem;
use App\Model\StorefrontShoeShelfItem;
use App\Model\StorefrontVirtualFitItem;
use App\Services\DecartTryOnService;
use App\Traits\StorefrontMediaTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StorefrontController extends Controller
{
    public const CACHE_KEY = 'storefront_bootstrap_v2';

    /**
     * Unified, cached bootstrap payload for the Next.js 3D Storefront home page.
     * Maps relational DB records & live product prices/sizes into the exact
     * TypeScript contract shapes expected by the 3D/motion components.
     */
    public function bootstrap(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'ar';
        $payload = Cache::remember(self::CACHE_KEY . '_' . $locale, 300, function () {
            $settingsMap = BusinessSetting::whereIn('key', [
                'ecommerce_name',
                'phone',
                'address',
                'footer_text',
                'storefront_settings',
            ])->pluck('value', 'key');

            $sfSettings = json_decode($settingsMap['storefront_settings'] ?? '{}', true) ?: [];

            return [
                'looks'      => $this->buildLooks(),
                'virtualFit' => $this->buildVirtualFit($sfSettings['virtual_fit'] ?? []),
                'shoes'      => $this->buildShoes($sfSettings['shoes'] ?? []),
                'rail'       => $this->buildRail(),
                'categories' => $this->buildCategories($sfSettings['categories'] ?? []),
                'brands'     => $this->buildBrands($sfSettings['brands'] ?? []),
                'closing'    => $this->buildClosing($settingsMap, $sfSettings['closing'] ?? []),
                'try_on'     => app(DecartTryOnService::class)->getPublicConfig(app()->getLocale() ?: 'ar'),
            ];
        });

        return response()->json($payload, 200);
    }

    /**
     * Paginated & filterable category catalog for /categories/[slug].
     */
    public function getProductsCatalog(Request $request): JsonResponse
    {
        $baseQuery = Product::active()
            ->with(['brand.translations' => fn($q) => $q->where('locale', app()->getLocale()), 'rating']);

        $categoryParam = trim((string)$request->input('category', ''));
        if ($categoryParam !== '') {
            $category = $this->resolveCategory($categoryParam);
            if ($category) {
                $allCatIds = array_merge([$category->id], $category->childes()->pluck('id')->toArray());
                $baseQuery->where(function ($q) use ($allCatIds) {
                    foreach ($allCatIds as $cid) {
                        $q->orWhereJsonContains('category_ids', ['id' => (string)$cid])
                          ->orWhereJsonContains('category_ids', ['id' => (int)$cid]);
                    }
                });
            }
        }

        $result = $this->executeCatalogQuery($baseQuery, $request, includeCategoryFacets: true);

        return response()->json($result, 200);
    }

    public function getCategoryCatalog(string $slugOrId, Request $request): JsonResponse
    {
        $category = $this->resolveCategory($slugOrId);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $allCatIds = array_merge([$category->id], $category->childes()->pluck('id')->toArray());

        $baseQuery = Product::active()
            ->with(['brand.translations' => fn($q) => $q->where('locale', app()->getLocale()), 'rating'])
            ->where(function ($q) use ($allCatIds) {
                foreach ($allCatIds as $cid) {
                    $q->orWhereJsonContains('category_ids', ['id' => (string)$cid])
                      ->orWhereJsonContains('category_ids', ['id' => (int)$cid]);
                }
            });

        $subCatId = (int)$request->input('sub_category_id', 0);
        if ($subCatId > 0) {
            $baseQuery->where(function ($q) use ($subCatId) {
                $q->orWhereJsonContains('category_ids', ['id' => (string)$subCatId])
                  ->orWhereJsonContains('category_ids', ['id' => $subCatId]);
            });
        }

        $result = $this->executeCatalogQuery($baseQuery, $request, includeCategoryFacets: false);

        $catName = $this->translatedValue($category, 'name', $category->name);
        $children = $category->childes()->active()->get()->map(function ($c) {
            $cName = $this->translatedValue($c, 'name', $c->name);
            return [
                'id'    => $c->id,
                'name'  => $this->isEnglish() ? $this->translateCategoryName($cName) : $cName,
                'image' => $c->image_full_path,
            ];
        });

        return response()->json(array_merge([
            'category' => [
                'id'       => $category->id,
                'name'     => $this->isEnglish() ? $this->translateCategoryName($catName) : $catName,
                'image'    => $category->image_full_path,
                'children' => $children,
            ],
        ], $result), 200);
    }

    private function executeCatalogQuery($baseQuery, Request $request, bool $includeCategoryFacets): array
    {
        $facets = $this->buildCatalogFacets(clone $baseQuery, $includeCategoryFacets);

        $limit = max(1, min(48, (int)$request->input('limit', 24)));
        $offset = max(1, (int)$request->input('offset', 1));
        $sortBy = $request->input('sort_by', 'newest');
        $brandParam = trim((string)$request->input('brand_id', ''));
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $search = trim((string)$request->input('q', ''));
        $colorParam = trim((string)$request->input('color', ''));
        $attrsParam = trim((string)$request->input('attrs', ''));
        $inStock = $request->input('in_stock');
        $onSale = $request->input('on_sale');

        $query = clone $baseQuery;

        if ($brandParam !== '') {
            $brandIds = array_values(array_filter(array_map('intval', explode(',', $brandParam))));
            if (!empty($brandIds)) {
                $query->whereIn('brand_id', $brandIds);
            }
        }
        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $query->where('price', '>=', (float)$minPrice);
        }
        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $query->where('price', '<=', (float)$maxPrice);
        }
        if ($inStock === '1' || $inStock === 'true' || $inStock === 1) {
            $query->where('total_stock', '>', 0);
        }
        if ($onSale === '1' || $onSale === 'true' || $onSale === 1) {
            $today = now()->toDateString();
            $flashProductIds = \App\Model\FlashDealProduct::query()
                ->join('flash_deals', 'flash_deals.id', '=', 'flash_deal_products.flash_deal_id')
                ->where('flash_deals.status', 1)
                ->whereDate('flash_deals.start_date', '<=', $today)
                ->whereDate('flash_deals.end_date', '>=', $today)
                ->pluck('flash_deal_products.product_id')
                ->toArray();

            $query->where(function ($q) use ($flashProductIds) {
                $q->where('discount', '>', 0);
                if (!empty($flashProductIds)) {
                    $q->orWhereIn('id', $flashProductIds);
                }
            });
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('translations', function ($tq) use ($search) {
                      $tq->where('key', 'name')->where('value', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by color or attribute values in choice_options / color_images
        $selectedColors = $colorParam !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $colorParam))))
            : [];

        $selectedAttrs = [];
        if ($attrsParam !== '') {
            // Format: Title1:ValA,ValB|Title2:ValC
            foreach (explode('|', $attrsParam) as $group) {
                $parts = explode(':', $group, 2);
                if (count($parts) === 2) {
                    $attrTitle = trim($parts[0]);
                    $vals = array_values(array_filter(array_map('trim', explode(',', $parts[1]))));
                    if ($attrTitle !== '' && !empty($vals)) {
                        $selectedAttrs[$attrTitle] = $vals;
                    }
                }
            }
        }

        if (!empty($selectedColors) || !empty($selectedAttrs)) {
            $matchingIds = (clone $query)
                ->select('id', 'choice_options', 'color_images')
                ->get()
                ->filter(function (Product $p) use ($selectedColors, $selectedAttrs) {
                    $choices = is_string($p->choice_options) ? json_decode($p->choice_options, true) : $p->choice_options;
                    $choices = is_array($choices) ? $choices : [];

                    if (!empty($selectedColors)) {
                        $productColors = [];
                        foreach ($choices as $c) {
                            if (($c['name'] ?? '') === 'choice_color' && is_array($c['options'] ?? null)) {
                                foreach ($c['options'] as $opt) {
                                    $productColors[] = trim((string)$opt);
                                }
                            }
                        }
                        foreach ($p->color_images_list as $ci) {
                            if (!empty($ci['color_name'])) {
                                $productColors[] = trim((string)$ci['color_name']);
                            }
                        }
                        if (empty(array_intersect($selectedColors, $productColors))) {
                            return false;
                        }
                    }

                    foreach ($selectedAttrs as $attrTitle => $requiredVals) {
                        $matchedGroup = false;
                        foreach ($choices as $c) {
                            if (($c['name'] ?? '') === 'choice_color') {
                                continue;
                            }
                            $title = trim((string)($c['title'] ?? ''));
                            if ($title === $attrTitle && is_array($c['options'] ?? null)) {
                                $opts = array_map(fn($o) => trim((string)$o), $c['options']);
                                if (!empty(array_intersect($requiredVals, $opts))) {
                                    $matchedGroup = true;
                                    break;
                                }
                            }
                        }
                        if (!$matchedGroup) {
                            return false;
                        }
                    }

                    return true;
                })
                ->pluck('id')
                ->toArray();

            $query->whereIn('id', !empty($matchingIds) ? $matchingIds : [0]);
        }

        match ($sortBy) {
            'low_to_high', 'price_asc'  => $query->orderBy('price', 'ASC'),
            'high_to_low', 'price_desc' => $query->orderBy('price', 'DESC'),
            'popular'                   => $query->orderBy('popularity_count', 'DESC'),
            default                     => $query->latest(),
        };

        $paginator = $query->paginate($limit, ['*'], 'page', $offset);
        $products = collect($paginator->items())->map(fn(Product $p) => $this->formatCatalogProduct($p))->values();

        return [
            'total_size' => $paginator->total(),
            'limit'      => $limit,
            'offset'     => $offset,
            'products'   => $products,
            'facets'     => $facets,
        ];
    }

    private function buildCatalogFacets($baseQuery, bool $includeCategoryFacets): array
    {
        $rows = $baseQuery
            ->with(['brand.translations' => fn($q) => $q->where('locale', app()->getLocale())])
            ->get(['id', 'brand_id', 'category_ids', 'choice_options', 'color_images', 'price', 'discount', 'total_stock']);

        $today = now()->toDateString();
        $flashProductIds = \App\Model\FlashDealProduct::query()
            ->join('flash_deals', 'flash_deals.id', '=', 'flash_deal_products.flash_deal_id')
            ->where('flash_deals.status', 1)
            ->whereDate('flash_deals.start_date', '<=', $today)
            ->whereDate('flash_deals.end_date', '>=', $today)
            ->pluck('flash_deal_products.product_id')
            ->flip()
            ->toArray();

        $brandsMap = [];
        $colorsMap = [];
        $attrsMap = [];
        $catCounts = [];
        $minPrice = null;
        $maxPrice = null;
        $inStockCount = 0;
        $onSaleCount = 0;

        foreach ($rows as $p) {
            $price = (float)$p->price;
            $minPrice = $minPrice === null ? $price : min($minPrice, $price);
            $maxPrice = $maxPrice === null ? $price : max($maxPrice, $price);

            if ((int)$p->total_stock > 0) {
                $inStockCount++;
            }
            if ((float)$p->discount > 0 || isset($flashProductIds[$p->id])) {
                $onSaleCount++;
            }

            if ($p->brand) {
                $bid = (int)$p->brand->id;
                if (!isset($brandsMap[$bid])) {
                    $brandsMap[$bid] = [
                        'id'    => $bid,
                        'name'  => $this->translatedValue($p->brand, 'name', $p->brand->name),
                        'count' => 0,
                    ];
                }
                $brandsMap[$bid]['count']++;
            }

            if ($includeCategoryFacets) {
                $catIds = is_string($p->category_ids) ? json_decode($p->category_ids, true) : $p->category_ids;
                if (is_array($catIds)) {
                    $seenForProduct = [];
                    foreach ($catIds as $ci) {
                        $cid = (int)($ci['id'] ?? 0);
                        if ($cid > 0 && !isset($seenForProduct[$cid])) {
                            $seenForProduct[$cid] = true;
                            $catCounts[$cid] = ($catCounts[$cid] ?? 0) + 1;
                        }
                    }
                }
            }

            // Color codes lookup from color_images
            $colorCodes = [];
            foreach ($p->color_images_list as $ci) {
                $cName = trim((string)($ci['color_name'] ?? ''));
                if ($cName !== '' && !empty($ci['color_code'])) {
                    $colorCodes[$cName] = $ci['color_code'];
                }
            }

            $choices = is_string($p->choice_options) ? json_decode($p->choice_options, true) : $p->choice_options;
            if (is_array($choices)) {
                foreach ($choices as $c) {
                    $name = (string)($c['name'] ?? '');
                    $title = trim((string)($c['title'] ?? ''));
                    $options = is_array($c['options'] ?? null) ? $c['options'] : [];

                    if ($name === 'choice_color') {
                        $seenColors = [];
                        foreach ($options as $opt) {
                            $cVal = trim((string)$opt);
                            if ($cVal === '' || isset($seenColors[$cVal])) continue;
                            $seenColors[$cVal] = true;
                            if (!isset($colorsMap[$cVal])) {
                                $colorsMap[$cVal] = [
                                    'name'  => $cVal,
                                    'code'  => $colorCodes[$cVal] ?? null,
                                    'count' => 0,
                                ];
                            } elseif (!$colorsMap[$cVal]['code'] && isset($colorCodes[$cVal])) {
                                $colorsMap[$cVal]['code'] = $colorCodes[$cVal];
                            }
                            $colorsMap[$cVal]['count']++;
                        }
                    } elseif ($title !== '') {
                        if (!isset($attrsMap[$title])) {
                            $attrsMap[$title] = [];
                        }
                        $seenOpts = [];
                        foreach ($options as $opt) {
                            $val = trim((string)$opt);
                            if ($val === '' || isset($seenOpts[$val])) continue;
                            $seenOpts[$val] = true;
                            $attrsMap[$title][$val] = ($attrsMap[$title][$val] ?? 0) + 1;
                        }
                    }
                }
            }

            // Also include color_images colors that weren't in choice_options
            foreach ($colorCodes as $cName => $cCode) {
                if (!isset($colorsMap[$cName])) {
                    $colorsMap[$cName] = ['name' => $cName, 'code' => $cCode, 'count' => 1];
                }
            }
        }

        $categoriesFacet = [];
        if ($includeCategoryFacets && !empty($catCounts)) {
            $cats = \App\Model\Category::active()
                ->whereIn('id', array_keys($catCounts))
                ->where('position', 0)
                ->get();
            foreach ($cats as $cat) {
                $catName = $this->translatedValue($cat, 'name', $cat->name);
                $categoriesFacet[] = [
                    'id'    => (int)$cat->id,
                    'name'  => $this->isEnglish() ? $this->translateCategoryName($catName) : $catName,
                    'slug'  => (string)$cat->id,
                    'count' => $catCounts[$cat->id] ?? 0,
                ];
            }
        }

        $attributesFacet = [];
        foreach ($attrsMap as $title => $opts) {
            $optList = [];
            foreach ($opts as $val => $cnt) {
                $optList[] = ['value' => (string)$val, 'count' => $cnt];
            }
            if (!empty($optList)) {
                $attributesFacet[] = [
                    'title'   => $title,
                    'options' => $optList,
                ];
            }
        }

        return [
            'categories'     => $categoriesFacet,
            'brands'         => array_values($brandsMap),
            'colors'         => array_values($colorsMap),
            'attributes'     => $attributesFacet,
            'price_range'    => [
                'min' => $minPrice ?? 0,
                'max' => $maxPrice ?? 0,
            ],
            'in_stock_count' => $inStockCount,
            'on_sale_count'  => $onSaleCount,
        ];
    }

    /**
     * Full product details + SizeChart + color_images + related products.
     */
    public function getProductDetail(int $id): JsonResponse
    {
        $product = Product::active()
            ->with([
                'brand.translations' => fn($q) => $q->where('locale', app()->getLocale()),
                'rating',
                'active_reviews.customer',
                'size_chart',
            ])
            ->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $formatted = $this->formatCatalogProduct($product);
        $formatted['description'] = $this->translatedValue($product, 'description', $product->description);
        $formatted['variant_mode'] = $product->variant_mode ?? 'standard';
        $formatted['images'] = $product->identity_image_full_path;
        $formatted['color_images'] = $product->color_images_list;
        $formatted['patterns'] = $product->isUniquePatterns() ? $product->patterns_list : [];
        $formatted['variations'] = $this->parseVariations($product);
        $rawChoices = is_string($product->choice_options)
            ? (json_decode($product->choice_options, true) ?? [])
            : ($product->choice_options ?? []);
        $formatted['choice_options'] = is_array($rawChoices)
            ? array_values(array_filter($rawChoices, fn($ch) => ($ch['name'] ?? '') !== 'choice_color'))
            : [];
        $rawSpecs = is_string($product->specifications)
            ? (json_decode($product->specifications, true) ?? [])
            : ($product->specifications ?? []);
        $formatted['specifications'] = is_array($rawSpecs)
            ? array_values(array_filter($rawSpecs, fn($s) => !empty($s['label']) && !empty($s['value'])))
            : [];

        $sizeChart = $product->active_size_chart;
        if ($sizeChart) {
            $columns = is_array($sizeChart->columns) ? array_values($sizeChart->columns) : [];
            $rawRows = is_array($sizeChart->rows) ? $sizeChart->rows : [];
            $normalizedRows = array_map(function ($row) use ($columns) {
                if (!is_array($row)) {
                    return [];
                }
                if (array_is_list($row)) {
                    return array_values(array_map('strval', $row));
                }
                return array_map(fn($col) => (string)($row[$col] ?? ''), $columns);
            }, $rawRows);

            $formatted['size_chart'] = [
                'id'      => $sizeChart->id,
                'name'    => $sizeChart->name,
                'columns' => $columns,
                'rows'    => array_values($normalizedRows),
                'note'    => $sizeChart->note ?? $sizeChart->instructions ?? null,
            ];
        } else {
            $formatted['size_chart'] = null;
        }

        $defaultCustomer = app()->getLocale() === 'en' ? 'Bin Huwesh Customer' : 'عميل بن هويش';
        $formatted['reviews'] = $product->active_reviews->take(10)->map(fn(Review $r) => [
            'id'         => $r->id,
            'rating'     => (int)$r->rating,
            'comment'    => $r->comment,
            'customer'   => $r->customer ? trim($r->customer->f_name . ' ' . $r->customer->l_name) : $defaultCustomer,
            'created_at' => $r->created_at?->toIso8601String(),
        ])->values();

        $completeTheLook = $this->buildCompleteTheLook($product);
        $excludeForRelated = $completeTheLook->pluck('id')->push($product->id)->all();
        $related = $this->buildSimilarProducts($product, $excludeForRelated);

        return response()->json([
            'product'           => $formatted,
            'complete_the_look' => $completeTheLook->values(),
            'related_products'  => $related->values(),
        ], 200);
    }

    /**
     * Cross-category "Complete the Look" recommendation algorithm:
     * 1) Curated Hero Look pairings (StorefrontLookItem)
     * 2) Co-purchased products from real orders (OrderDetails)
     * 3) Complementary body-zone outfit pairing (never same body zone or full-outfit duplicates)
     */
    private function buildCompleteTheLook(Product $product, int $limit = 3)
    {
        $categoryNameMap = Category::pluck('name', 'id')->all();
        $productSlot = $this->classifyProductBodyZone($product, $categoryNameMap);
        $productAudience = $this->classifyProductAudience($product, $categoryNameMap);

        // Complementary body zones per slot (strictly excludes the product's own zone)
        $complementaryMatrix = [
            'top'         => ['bottom', 'footwear', 'accessories', 'outerwear'],
            'bottom'      => ['top', 'footwear', 'accessories', 'outerwear'],
            'outerwear'   => ['top', 'bottom', 'footwear', 'accessories'],
            'full_outfit' => ['footwear', 'accessories', 'outerwear'],
            'footwear'    => ['bottom', 'top', 'accessories', 'full_outfit'],
            'accessories' => ['top', 'bottom', 'footwear', 'outerwear'],
            'unknown'     => ['bottom', 'footwear', 'accessories', 'outerwear', 'top'],
        ];
        $allowedSlots = $complementaryMatrix[$productSlot] ?? $complementaryMatrix['unknown'];

        $curatedIds = [];

        // 1. Curated look pairings (explicitly styled together by admin)
        if (Schema::hasTable('storefront_look_items')) {
            $lookIds = StorefrontLookItem::where('product_id', $product->id)->pluck('look_id')->unique()->all();
            if (!empty($lookIds)) {
                $pairedIds = StorefrontLookItem::whereIn('look_id', $lookIds)
                    ->whereNotNull('product_id')
                    ->where('product_id', '!=', $product->id)
                    ->pluck('product_id')
                    ->unique()
                    ->all();
                $curatedIds = array_merge($curatedIds, $pairedIds);
            }
        }

        // 2. Co-purchased products from orders (filtered to complementary body zones)
        $coPurchasedIds = [];
        if (Schema::hasTable('order_details')) {
            $orderIds = DB::table('order_details')
                ->where('product_id', $product->id)
                ->orderByDesc('id')
                ->limit(40)
                ->pluck('order_id')
                ->unique()
                ->all();
            if (!empty($orderIds)) {
                $coPurchasedIds = DB::table('order_details')
                    ->whereIn('order_id', $orderIds)
                    ->where('product_id', '!=', $product->id)
                    ->select('product_id', DB::raw('COUNT(*) as freq'))
                    ->groupBy('product_id')
                    ->orderByDesc('freq')
                    ->limit(12)
                    ->pluck('product_id')
                    ->all();
            }
        }

        $selected = collect();
        $usedIds = [$product->id];

        if (!empty($curatedIds)) {
            $curated = Product::active()
                ->with(['brand.translations' => fn($q) => $q->where('locale', app()->getLocale()), 'rating'])
                ->whereIn('id', $curatedIds)
                ->where('total_stock', '>', 0)
                ->get()
                ->filter(fn(Product $cand) => $this->classifyProductBodyZone($cand, $categoryNameMap) !== $productSlot);
            foreach ($curated as $c) {
                if ($selected->count() >= $limit) break;
                $selected->push($c);
                $usedIds[] = $c->id;
            }
        }

        if ($selected->count() < $limit && !empty($coPurchasedIds)) {
            $coProducts = Product::active()
                ->with(['brand.translations' => fn($q) => $q->where('locale', app()->getLocale()), 'rating'])
                ->whereIn('id', array_diff($coPurchasedIds, $usedIds))
                ->where('total_stock', '>', 0)
                ->get()
                ->filter(fn(Product $cand) => in_array($this->classifyProductBodyZone($cand, $categoryNameMap), $allowedSlots, true));
            foreach ($coProducts as $c) {
                if ($selected->count() >= $limit) break;
                $selected->push($c);
                $usedIds[] = $c->id;
            }
        }

        // 3. Complementary body-zone pool: pick 1 best item per complementary slot first, then fill
        if ($selected->count() < $limit) {
            $pool = Product::active()
                ->with(['brand.translations' => fn($q) => $q->where('locale', app()->getLocale()), 'rating'])
                ->whereNotIn('id', $usedIds)
                ->where('total_stock', '>', 0)
                ->latest()
                ->take(80)
                ->get()
                ->map(function (Product $cand) use ($categoryNameMap, $product, $productAudience) {
                    $slot = $this->classifyProductBodyZone($cand, $categoryNameMap);
                    $aud = $this->classifyProductAudience($cand, $categoryNameMap);
                    $score = 0;
                    if ($product->brand_id && $cand->brand_id === $product->brand_id) {
                        $score += 20;
                    }
                    if ($productAudience === 'kids' && $aud === 'kids') {
                        $score += 30;
                    } elseif ($productAudience !== 'kids' && $aud === 'kids') {
                        $score -= 50; // Avoid mixing kids items into adult outfits
                    }
                    return ['product' => $cand, 'slot' => $slot, 'score' => $score];
                })
                ->filter(fn($row) => in_array($row['slot'], $allowedSlots, true))
                ->sortByDesc('score')
                ->values();

            // Round 1: pick 1 item from each complementary slot for a balanced outfit
            foreach ($allowedSlots as $targetSlot) {
                if ($selected->count() >= $limit) break;
                $match = $pool->first(fn($row) => $row['slot'] === $targetSlot && !in_array($row['product']->id, $usedIds, true));
                if ($match) {
                    $selected->push($match['product']);
                    $usedIds[] = $match['product']->id;
                }
            }

            // Round 2: if still under $limit, add remaining complementary items (at most 2 per slot)
            foreach ($allowedSlots as $targetSlot) {
                if ($selected->count() >= $limit) break;
                $slotMatches = $pool->filter(fn($row) => $row['slot'] === $targetSlot && !in_array($row['product']->id, $usedIds, true));
                foreach ($slotMatches->take(1) as $row) {
                    if ($selected->count() >= $limit) break;
                    $selected->push($row['product']);
                    $usedIds[] = $row['product']->id;
                }
            }
        }

        return $selected->take($limit)->map(fn(Product $p) => $this->formatCatalogProduct($p))->values();
    }

    private function classifyProductBodyZone(Product $product, array $categoryNameMap): string
    {
        $catIds = $this->extractProductCategoryIds($product);
        $catText = implode(' ', array_map(fn($id) => $categoryNameMap[$id] ?? '', $catIds));
        $haystack = mb_strtolower(trim($product->name . ' ' . $catText));

        $rules = [
            'footwear'    => ['حذاء', 'أحذية', 'احذية', 'شوز', 'سنيكرز', 'لوفر', 'أكسفورد', 'اكسفورد', 'صندل', 'شبشب', 'جزمة', 'بوت', 'shoe', 'shoes', 'sneaker', 'loafer', 'oxford', 'boot', 'sandal'],
            'accessories' => ['حزام', 'أحزمة', 'احزمة', 'ساعة', 'ساعات', 'نظارة', 'نظارات', 'كاب', 'قبعة', 'شماغ', 'غترة', 'كوفية', 'محفظة', 'حقيبة', 'شنطة', 'ربطة عنق', 'كرافت', 'جوارب', 'شراب', 'عطر', 'سبحة', 'اكسسوار', 'إكسسوار', 'belt', 'watch', 'sunglasses', 'cap', 'hat', 'bag', 'wallet', 'tie', 'socks', 'accessor'],
            'outerwear'   => ['جاكيت', 'بليزر', 'معطف', 'كوت', 'فيست', 'صدرية', 'jacket', 'blazer', 'coat', 'vest'],
            'full_outfit' => ['بدلة', 'بدلات', 'طقم', 'أطقم', 'اطقم', 'ثوب', 'ثياب', 'عباية', 'فستان', 'suit', 'set', 'thobe', 'dress'],
            'top'         => ['قميص', 'قمصان', 'تيشيرت', 'تيشرت', 'بولو', 'بلوزة', 'كنزة', 'سويتر', 'هودي', 'شيرت', 'shirt', 't-shirt', 'polo', 'hoodie', 'sweater', 'blouse'],
            'bottom'      => ['بنطلون', 'بناطيل', 'بنطال', 'جينز', 'شورت', 'معوز', 'معاوز', 'إزار', 'ازار', 'فوطة', 'سروال', 'تنورة', 'trouser', 'pants', 'jeans', 'shorts', 'maawaz'],
        ];

        foreach ($rules as $zone => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($haystack, $kw) !== false) {
                    return $zone;
                }
            }
        }

        return 'unknown';
    }

    private function classifyProductAudience(Product $product, array $categoryNameMap): string
    {
        $catIds = $this->extractProductCategoryIds($product);
        $catText = implode(' ', array_map(fn($id) => $categoryNameMap[$id] ?? '', $catIds));
        $haystack = mb_strtolower(trim($product->name . ' ' . $catText));

        foreach (['ولادي', 'أطفال', 'اطفال', 'بناتي', 'kids', 'child', 'boy', 'girl'] as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                return 'kids';
            }
        }
        return 'adult';
    }

    /**
     * Similarity-scored "Similar Products" algorithm:
     * Filters to the same body zone & compatible audience, then scores by
     * shared title keywords, overlapping categories, matching brand, and price proximity.
     */
    private function buildSimilarProducts(Product $product, array $excludeIds = [], int $limit = 8)
    {
        $categoryNameMap = Category::pluck('name', 'id')->all();
        $productSlot = $this->classifyProductBodyZone($product, $categoryNameMap);
        $productAudience = $this->classifyProductAudience($product, $categoryNameMap);
        $targetCats = $this->extractProductCategoryIds($product);
        $targetPrice = (float)$product->price;

        $stopWords = ['من', 'في', 'على', 'مع', 'لـ', 'و', 'أو', 'مقاس', 'رجالي', 'شبابي', 'ولادي', 'فاخر', 'فاخرة', 'عصري', 'مريح', 'جديد'];
        $targetWords = array_values(array_filter(
            preg_split('/[\s\-\(\)\/]+/u', mb_strtolower((string)$product->name)),
            fn($w) => mb_strlen($w) >= 3 && !in_array($w, $stopWords, true)
        ));

        $pool = Product::active()
            ->with(['brand.translations' => fn($q) => $q->where('locale', app()->getLocale()), 'rating'])
            ->whereNotIn('id', $excludeIds)
            ->where('total_stock', '>', 0)
            ->latest()
            ->take(100)
            ->get();

        $scored = $pool->map(function (Product $cand) use ($categoryNameMap, $productSlot, $productAudience, $targetCats, $targetPrice, $targetWords, $product) {
            $candSlot = $this->classifyProductBodyZone($cand, $categoryNameMap);
            $candAudience = $this->classifyProductAudience($cand, $categoryNameMap);

            // Must match body zone when known (never show shoes/suits as similar to a polo shirt)
            if ($productSlot !== 'unknown' && $candSlot !== $productSlot) {
                return null;
            }
            // Avoid mixing kids items with adult items
            if ($productAudience !== $candAudience) {
                return null;
            }

            $score = 10;

            // Shared meaningful words in product name (e.g. بولو, تيشيرت, قميص, جامبو)
            $candName = mb_strtolower((string)$cand->name);
            foreach ($targetWords as $word) {
                if (mb_strpos($candName, $word) !== false) {
                    $score += 30;
                }
            }

            $candCats = $this->extractProductCategoryIds($cand);
            $overlap = count(array_intersect($targetCats, $candCats));
            $score += $overlap * 40;

            if ($product->brand_id && $cand->brand_id === $product->brand_id) {
                $score += 20;
            }
            if ($targetPrice > 0) {
                $diffRatio = abs((float)$cand->price - $targetPrice) / $targetPrice;
                if ($diffRatio <= 0.35) {
                    $score += 15;
                }
            }

            return ['product' => $cand, 'score' => $score];
        })->filter()->sortByDesc('score')->take($limit);

        return $scored->map(fn($row) => $this->formatCatalogProduct($row['product']))->values();
    }

    private function extractProductCategoryIds(Product $product): array
    {
        $raw = is_string($product->category_ids)
            ? (json_decode($product->category_ids, true) ?? [])
            : ($product->category_ids ?? []);
        if (!is_array($raw)) {
            return [];
        }
        return array_values(array_filter(array_map(fn($c) => isset($c['id']) ? (string)$c['id'] : null, $raw)));
    }

    /**
     * Storefront legal & informational pages managed from BusinessSettings.
     */
    public function getPolicies(): JsonResponse
    {
        $keys = [
            'terms_and_conditions',
            'privacy_policy',
            'return_policy',
            'refund_policy',
            'cancellation_policy',
            'about_us',
            'faq',
        ];

        $settings = BusinessSetting::whereIn('key', $keys)->pluck('value', 'key');

        return response()->json([
            'terms_and_conditions' => $this->extractPolicyHtml($settings['terms_and_conditions'] ?? ''),
            'privacy_policy'       => $this->extractPolicyHtml($settings['privacy_policy'] ?? ''),
            'return_policy'        => $this->extractPolicyHtml($settings['return_policy'] ?? ''),
            'refund_policy'        => $this->extractPolicyHtml($settings['refund_policy'] ?? ''),
            'cancellation_policy'  => $this->extractPolicyHtml($settings['cancellation_policy'] ?? ''),
            'about_us'             => $this->extractPolicyHtml($settings['about_us'] ?? ''),
            'faq'                  => $this->extractPolicyHtml($settings['faq'] ?? ''),
        ], 200);
    }

    /**
     * Public-safe configuration for Virtual Try-On ("جرّبها عليك" / Decart Anywear).
     */
    public function getTryOnConfig(DecartTryOnService $tryOnService): JsonResponse
    {
        $locale = app()->getLocale() ?: 'ar';
        return response()->json($tryOnService->getPublicConfig($locale), 200);
    }

    /**
     * Issue a short-lived ephemeral Decart client token for an eligible product.
     */
    public function createTryOnToken(Request $request, DecartTryOnService $tryOnService): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|min:1',
            'color'      => 'nullable|string|max:120',
        ]);

        $product = Product::active()->find((int) $validated['product_id']);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        try {
            $tokenData = $tryOnService->createClientToken(
                $product,
                $request->headers->get('origin')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Resolve garment image matching the selected color if available
        $garmentImage = ($product->identity_image_full_path[0] ?? null)
            ?: asset('public/assets/admin/img/160x160/2.png');

        $selectedColor = trim((string) ($validated['color'] ?? ''));
        if ($selectedColor !== '' && is_array($product->color_images_list)) {
            foreach ($product->color_images_list as $entry) {
                $entryColor = trim((string) ($entry['color'] ?? $entry['name'] ?? ''));
                if ($entryColor !== '' && mb_strtolower($entryColor) === mb_strtolower($selectedColor)) {
                    $candidate = $entry['image'] ?? $entry['url'] ?? null;
                    if (!empty($candidate)) {
                        $garmentImage = $candidate;
                        break;
                    }
                }
            }
        }

        $locale = app()->getLocale() ?: 'ar';
        $publicConfig = $tryOnService->getPublicConfig($locale);

        return response()->json([
            'api_key'             => $tokenData['api_key'],
            'expires_at'          => $tokenData['expires_at'],
            'model'               => $tokenData['model'],
            'speed'               => $tokenData['speed'],
            'max_session_seconds' => $tokenData['max_session_seconds'],
            'garment_image_url'   => $garmentImage,
            'product_name'        => $this->translatedValue($product, 'name', $product->name),
            'disclaimer'          => $publicConfig['disclaimer'],
        ], 200);
    }

    /**
     * Extracts pure clean HTML content from BusinessSetting value which may be stored
     * as a raw HTML string or a JSON payload {"background_image":..., "description":"..."}.
     */
    private function extractPolicyHtml(?string $raw): string
    {
        if (empty($raw)) {
            return '';
        }

        // Check if raw value is JSON payload
        $trimmed = trim($raw);
        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded) && isset($decoded['description'])) {
                $trimmed = (string)$decoded['description'];
            }
        }

        // Clean up empty HTML like "<p></p>" or "<p><br></p>"
        $stripped = trim(strip_tags($trimmed));
        if ($stripped === '' && !str_contains($trimmed, '<img')) {
            return '';
        }

        return $trimmed;
    }

    /* ------------------------------------------------------------------ */
    /* Private Builders                                                    */
    /* ------------------------------------------------------------------ */

    private function buildLooks(): ?array
    {
        $looks = StorefrontLook::where('published', true)
            ->with(['items.product'])
            ->orderBy('sort_order')
            ->get();

        if ($looks->isEmpty()) {
            return null;
        }

        return $looks->map(function (StorefrontLook $look) {
            return [
                'id'       => $look->uid,
                'order'    => $look->sort_order,
                'published'=> (bool)$look->published,
                'title'    => $look->title,
                'subtitle' => $look->subtitle ?? '',
                'image'    => [
                    'src'       => $look->image_url,
                    'width'     => $look->image_width ?: 900,
                    'height'    => $look->image_height ?: 1400,
                    'alt'       => $look->image_alt ?: $look->title,
                    'scale'     => (float)$look->scale,
                    'offsetX'   => (float)$look->offset_x,
                    'offsetY'   => (float)$look->offset_y,
                    'isPreview' => false,
                ],
                'items' => $look->items->map(function ($it) {
                    $p = $it->product;
                    $livePrice = $p ? $this->finalPrice($p) : (int)$it->fallback_price;
                    $sizes = $p ? $this->extractSizes($p, $it->sizes ?? []) : ($it->sizes ?? []);
                    $label = $p ? $this->translatedValue($p, 'name', $p->name) : $it->label;
                    $pImages = $p ? ($p->identity_image_full_path ?? []) : [];
                    $image = $pImages[0] ?? null;

                    return [
                        'id'        => $it->uid,
                        'label'     => $label,
                        'category'  => $it->category,
                        'hotspot'   => ['x' => (float)$it->hotspot_x, 'y' => (float)$it->hotspot_y],
                        'productId' => $it->product_id ? (string)$it->product_id : null,
                        'demoPrice' => $livePrice,
                        'livePrice' => $livePrice,
                        'isDemo'    => $it->product_id === null,
                        'sizes'     => $sizes,
                        'image'     => $image,
                    ];
                })->values()->all(),
                'wholeLookDiscount' => $look->whole_look_discount ?: null,
                'href'              => $look->href ?: null,
            ];
        })->values()->all();
    }

    private function buildVirtualFit(array $cfg): ?array
    {
        $items = StorefrontVirtualFitItem::where('visible', true)
            ->with('product')
            ->orderBy('sort_order')
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        $categories = $items->unique('category_key')->map(fn($it) => [
            'id'   => $it->category_key,
            'name' => $this->isEnglish()
                ? $this->translateCategoryName($it->category_name, $it->category_key)
                : $it->category_name,
        ])->values()->all();

        return [
            'version'  => 1,
            'settings' => [
                'title'     => $this->localizedSetting($cfg, 'title', 'وصل حديثًا', 'New Arrivals'),
                'subtitle'  => $this->localizedSetting($cfg, 'subtitle', 'جرّب القطعة على المودل واختر مقاسك ولونك المفضل', 'Try the piece on the model and pick your preferred size and color'),
                'watermark' => $cfg['watermark'] ?? 'BIN HUWESH',
                'room'      => $cfg['room'] ?? '/images/new-arrivals/room.webp',
            ],
            'categories' => $categories,
            'items'      => $items->map(function (StorefrontVirtualFitItem $it) {
                $p = $it->product;
                $colors = collect($it->colors ?? [])->map(fn($c) => [
                    'id'      => $c['id'] ?? 'c1',
                    'name'    => $c['name'] ?? '',
                    'swatch'  => $c['swatch'] ?? '#111111',
                    'garment' => StorefrontMediaTrait::resolveMediaUrl($c['garment_path'] ?? $c['garment'] ?? '', 'storefront/virtual-fit'),
                    'model'   => StorefrontMediaTrait::resolveMediaUrl($c['model_path'] ?? $c['model'] ?? '', 'storefront/virtual-fit'),
                ])->values()->all();

                return [
                    'id'          => $it->uid,
                    'productId'   => $it->product_id ? (string)$it->product_id : null,
                    'name'        => $it->name,
                    'description' => $it->description,
                    'categoryId'  => $it->category_key,
                    'price'       => $p ? $this->finalPrice($p) : (int)$it->fallback_price,
                    'isDemo'      => $it->product_id === null,
                    'sizes'       => $p ? $this->extractSizes($p, $it->sizes ?? ['S', 'M', 'L', 'XL']) : ($it->sizes ?? ['S', 'M', 'L', 'XL']),
                    'colors'      => $colors,
                    'visible'     => true,
                ];
            })->values()->all(),
        ];
    }

    private function buildShoes(array $cfg): ?array
    {
        $items = StorefrontShoeShelfItem::where('visible', true)
            ->with('product')
            ->orderBy('sort_order')
            ->take(10)
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'version'  => 1,
            'settings' => [
                'title'    => $this->localizedSetting($cfg, 'title', 'أحذية تكمل إطلالتك', 'Shoes to Complete Your Look'),
                'subtitle' => $this->localizedSetting($cfg, 'subtitle', 'تشكيلة مختارة من الأحذية الكلاسيكية والكاجوال', 'A curated selection of classic and casual footwear'),
            ],
            'shoes' => $items->map(function (StorefrontShoeShelfItem $it) {
                $p = $it->product;
                $colors = collect($it->colors ?? [])->map(fn($c) => [
                    'id'     => $c['id'] ?? 'c1',
                    'name'   => $c['name'] ?? '',
                    'swatch' => $c['swatch'] ?? '#222222',
                    'image'  => StorefrontMediaTrait::resolveMediaUrl($c['image_path'] ?? $c['image'] ?? '', 'storefront/shoes'),
                ])->values()->all();

                return [
                    'id'          => $it->uid,
                    'productId'   => $it->product_id ? (string)$it->product_id : null,
                    'name'        => $it->name,
                    'description' => $it->description ?? '',
                    'price'       => $p ? $this->finalPrice($p) : (int)$it->fallback_price,
                    'isDemo'      => $it->product_id === null,
                    'sizes'       => $p ? $this->extractSizes($p, $it->sizes ?? ['40', '41', '42', '43', '44']) : ($it->sizes ?? ['40', '41', '42', '43', '44']),
                    'colors'      => $colors,
                    'alt'         => $it->alt ?: $it->name,
                    'visible'     => true,
                ];
            })->values()->all(),
        ];
    }

    private function buildRail(): ?array
    {
        $items = StorefrontRailItem::where('visible', true)
            ->with('product')
            ->orderBy('sort_order')
            ->take(10)
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        return $items->map(function (StorefrontRailItem $it) {
            $p = $it->product;
            $price = $p ? $this->finalPrice($p) : (int)$it->fallback_price;

            return [
                'id'          => $it->uid,
                'productId'   => $it->product_id ? (string)$it->product_id : null,
                'name'        => $it->name,
                'kind'        => $it->kind,
                'color'       => $it->color_hex,
                'tag'         => number_format($price) . ($this->isEnglish() ? ' YER' : ' ر.ي'),
                'price'       => $price,
                'description' => $it->description ?? '',
                'details'     => $it->details ?? [],
                'image'       => StorefrontMediaTrait::resolveMediaUrl($it->front_image_path, 'storefront/rail'),
                'backImage'   => StorefrontMediaTrait::resolveMediaUrl($it->back_image_path, 'storefront/rail'),
                'sideImage'   => StorefrontMediaTrait::resolveMediaUrl($it->side_image_path, 'storefront/rail'),
            ];
        })->values()->all();
    }

    private function buildCategories(array $cfg): ?array
    {
        $cards = StorefrontCategoryCard::where('visible', true)
            ->with('category')
            ->orderBy('sort_order')
            ->get();

        $settings = [
            'title'    => $this->localizedSetting($cfg, 'title', 'تسوّق حسب القسم', 'Shop by Category'),
            'subtitle' => $this->localizedSetting($cfg, 'subtitle', 'استكشف أقسام بن هويش المتنوعة', 'Explore Bin Huwesh collections by category'),
            'cta'      => $this->localizedSetting($cfg, 'cta', 'تصفّح القسم', 'Browse Category'),
        ];

        if ($cards->isEmpty()) {
            $systemCategories = Category::active()
                ->where('parent_id', 0)
                ->orderBy('priority')
                ->orderBy('id')
                ->get();

            if ($systemCategories->isEmpty()) {
                return null;
            }

            return [
                'version'  => 1,
                'settings' => $settings,
                'cards'    => $systemCategories->map(function (Category $cat) {
                    $rawName = $this->translatedValue($cat, 'name', $cat->name);
                    $name = $this->isEnglish() ? $this->translateCategoryName($rawName) : $rawName;
                    return [
                        'id'         => 'cat-' . $cat->id,
                        'categoryId' => (string)$cat->id,
                        'name'       => $name,
                        'caption'    => '',
                        'href'       => '/categories/' . $cat->id,
                        'image'      => $cat->image_full_path,
                        'alt'        => $name,
                        'focus'      => ['x' => 50, 'y' => 50],
                        'size'       => 'regular',
                        'visible'    => true,
                    ];
                })->values()->all(),
            ];
        }

        return [
            'version'  => 1,
            'settings' => $settings,
            'cards'    => $cards->map(function (StorefrontCategoryCard $c) {
                $catTranslated = $c->category ? $this->translatedValue($c->category, 'name', '') : '';
                $rawName = $c->name ?: ($c->category->name ?? '');
                $name = $this->isEnglish()
                    ? ($catTranslated !== '' && !preg_match('/[\x{0600}-\x{06FF}]/u', $catTranslated)
                        ? $catTranslated
                        : $this->translateCategoryName($rawName, $c->slug))
                    : $rawName;

                return [
                    'id'         => $c->uid,
                    'categoryId' => $c->category_id ? (string)$c->category_id : $c->slug,
                    'name'       => $name,
                    'caption'    => $this->isEnglish() && preg_match('/[\x{0600}-\x{06FF}]/u', (string)$c->caption) ? '' : ($c->caption ?? ''),
                    'href'       => '/categories/' . ($c->category_id ?: $c->slug),
                    'image'      => $c->image_url,
                    'alt'        => ($this->isEnglish() && preg_match('/[\x{0600}-\x{06FF}]/u', (string)$c->alt)) ? $name : ($c->alt ?: $name),
                    'focus'      => ['x' => (int)$c->focus_x, 'y' => (int)$c->focus_y],
                    'size'       => 'regular',
                    'visible'    => true,
                ];
            })->values()->all(),
        ];
    }

    private function buildBrands(array $cfg): ?array
    {
        $brands = Brand::active()
            ->with(['translations' => fn($q) => $q->where('locale', app()->getLocale())])
            ->where('is_featured', 1)
            ->orderBy('id')
            ->get();
        if ($brands->isEmpty()) {
            return null;
        }

        return [
            'version'  => 1,
            'settings' => [
                'title'     => $this->localizedSetting($cfg, 'title', 'ماركات عالمية معتمدة', 'Trusted Global Brands'),
                'showTitle' => true,
                'speed'     => (int)($cfg['speed'] ?? 38),
                'direction' => $cfg['direction'] ?? 'right',
                'logoSize'  => (int)($cfg['logoSize'] ?? 34),
            ],
            'brands' => $brands->map(fn(Brand $b) => [
                'id'        => 'brand-' . $b->id,
                'name'      => $this->translatedValue($b, 'name', $b->name),
                'logo'      => $b->image_full_path,
                'colorLogo' => $b->image_full_path,
                'aspect'    => 2.4,
                'mode'      => 'color',
                'scale'     => 1,
                'visible'   => true,
                'source'    => 'dashboard',
            ])->values()->all(),
        ];
    }

    private function buildClosing($settingsMap, array $cfg): ?array
    {
        $reviews = Review::active()->with('customer')->latest()->take(6)->get();
        $socials = Schema::hasTable('social_media') ? SocialMedia::where('status', 1)->get() : collect();

        $phone = $settingsMap['phone'] ?? '780024406';
        $phoneDigits = preg_replace('/\D+/', '', $phone);
        $phoneIntl = str_starts_with($phoneDigits, '967') ? $phoneDigits : '967' . ltrim($phoneDigits, '0');

        $defaultReviewAuthor = $this->isEnglish() ? 'Bin Huwesh Customer' : 'عميل بن هويش';
        $defaultReviewSource = $this->isEnglish() ? 'Bin Huwesh Store Review' : 'تقييم متجر بن هويش';

        $customTestimonials = is_array($cfg['testimonials_items'] ?? null)
            ? array_values(array_filter($cfg['testimonials_items'], fn($it) => (bool)($it['visible'] ?? true)))
            : [];

        $testimonialItems = !empty($customTestimonials)
            ? collect($customTestimonials)->map(fn($it, $idx) => [
                'id'       => (string)($it['id'] ?? ('tm-' . $idx)),
                'quote'    => (string)($it['quote'] ?? ''),
                'name'     => (string)($it['name'] ?? $defaultReviewAuthor),
                'verified' => (bool)($it['verified'] ?? true),
                'source'   => (string)($it['source'] ?? $defaultReviewSource),
                'rating'   => isset($it['rating']) ? (int)$it['rating'] : 5,
                'visible'  => true,
            ])->values()->all()
            : $reviews->map(fn(Review $r) => [
                'id'       => 'rev-' . $r->id,
                'quote'    => $r->comment ?? '',
                'name'     => $r->customer ? trim($r->customer->f_name . ' ' . $r->customer->l_name) : $defaultReviewAuthor,
                'verified' => true,
                'source'   => $defaultReviewSource,
                'rating'   => (int)$r->rating,
                'visible'  => true,
            ])->values()->all();

        return [
            'version'      => 1,
            'testimonials' => [
                'title'    => $this->localizedSetting($cfg, 'testimonials_title', 'قالوا عن بن هويش', 'What They Say About Bin Huwesh'),
                'subtitle' => $this->localizedSetting($cfg, 'testimonials_subtitle', 'آراء عملائنا الموثقة', 'Verified customer reviews'),
                'items'    => $testimonialItems,
            ],
            'contact' => [
                'title'        => $this->localizedSetting($cfg, 'contact_title', 'ننتظرك في بن هويش', 'Visit Us at Bin Huwesh'),
                'subtitle'     => $this->localizedSetting($cfg, 'contact_subtitle', 'زورنا في المعرض أو تواصل معنا مباشرة', 'Visit our showroom or reach out directly'),
                'company'      => $this->localizedSetting(['company' => $settingsMap['ecommerce_name'] ?? ''], 'company', 'شركة بن هويش التجارية', 'Bin Huwesh Trading Co.'),
                'address'      => $this->localizedSetting(['address' => $settingsMap['address'] ?? ''], 'address', 'عدن، اليمن', 'Aden, Yemen'),
                'phoneDisplay' => $phone,
                'phoneIntl'    => $phoneIntl,
                'mapUrl'       => $cfg['mapUrl'] ?? '',
            ],
            'social' => $socials->map(fn($s) => [
                'platform' => strtolower($s->name ?? 'instagram'),
                'url'      => $s->link ?? '',
            ])->filter(fn($s) => !empty($s['url']))->values()->all(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function resolveCategory(string $slugOrId): ?Category
    {
        if (ctype_digit($slugOrId)) {
            return Category::active()->find((int)$slugOrId);
        }

        // Map known static slugs to category search if slug column is absent
        $slugMap = [
            'men'         => 'رجالي',
            'youth'       => 'شبابي',
            'kids'        => 'ولادي',
            'jumbo'       => 'جامبو',
            'suits'       => 'بدلات',
            'maawaz'      => 'معاوز',
            'shoes'       => 'أحذية',
            'accessories' => 'إكسسوارات',
            'sportswear'  => 'رياضي',
            'formal'      => 'رسمي',
        ];
        $term = $slugMap[$slugOrId] ?? $slugOrId;

        return Category::active()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%");
            })
            ->first();
    }

    private function effectiveDiscount(Product $product, float $basePrice): array
    {
        $bestDiscountAmount = 0.0;
        $bestDiscountValue = 0.0;
        $bestDiscountType = $product->discount_type ?: 'percent';

        // 1. Product-level discount
        $prodDisc = (float)($product->discount ?? 0);
        if ($prodDisc > 0) {
            $amt = $bestDiscountType === 'percent' ? ($basePrice * $prodDisc) / 100 : $prodDisc;
            if ($amt > $bestDiscountAmount) {
                $bestDiscountAmount = $amt;
                $bestDiscountValue = $prodDisc;
            }
        }

        // 2. Category-level discount
        $catIds = is_string($product->category_ids) ? json_decode($product->category_ids, true) : $product->category_ids;
        $mainCatId = is_array($catIds) ? ($catIds[0]['id'] ?? null) : null;
        if ($mainCatId) {
            $catAmt = (float)Helpers::category_discount_calculate($mainCatId, $basePrice);
            if ($catAmt > $bestDiscountAmount) {
                $bestDiscountAmount = $catAmt;
                $bestDiscountValue = $catAmt;
                $bestDiscountType = 'amount';
            }
        }

        // 3. Active Flash Deal discount
        $today = now()->toDateString();
        $flashItem = \App\Model\FlashDealProduct::query()
            ->join('flash_deals', 'flash_deals.id', '=', 'flash_deal_products.flash_deal_id')
            ->where('flash_deal_products.product_id', $product->id)
            ->where('flash_deals.status', 1)
            ->whereDate('flash_deals.start_date', '<=', $today)
            ->whereDate('flash_deals.end_date', '>=', $today)
            ->select('flash_deal_products.discount', 'flash_deal_products.discount_type')
            ->first();
        if ($flashItem && (float)$flashItem->discount > 0) {
            $fType = $flashItem->discount_type ?: 'percent';
            $fVal = (float)$flashItem->discount;
            $fAmt = $fType === 'percent' ? ($basePrice * $fVal) / 100 : $fVal;
            if ($fAmt > $bestDiscountAmount) {
                $bestDiscountAmount = $fAmt;
                $bestDiscountValue = $fVal;
                $bestDiscountType = $fType;
            }
        }

        $final = max(0, (int)round($basePrice - $bestDiscountAmount));
        return [
            'final_price'   => $final,
            'discount'      => $bestDiscountValue,
            'discount_type' => $bestDiscountType,
        ];
    }

    private function finalPrice(Product $product): int
    {
        return $this->effectiveDiscount($product, (float)$product->price)['final_price'];
    }

    private function parseVariations(Product $product): array
    {
        $raw = is_string($product->variations) ? json_decode($product->variations, true) : $product->variations;
        if (!is_array($raw)) {
            return [];
        }

        return collect($raw)->map(fn($v) => [
            'type'  => $v['type'] ?? '',
            'price' => (float)($v['price'] ?? $product->price),
            'stock' => (int)($v['stock'] ?? 0),
        ])->values()->all();
    }

    private function extractSizes(Product $product, array $fallback): array
    {
        $choices = is_string($product->choice_options) ? json_decode($product->choice_options, true) : $product->choice_options;
        $variations = $this->parseVariations($product);

        if (is_array($choices) && count($choices) > 0) {
            foreach ($choices as $ch) {
                if (($ch['name'] ?? '') === 'choice_color') {
                    continue;
                }
                $opts = $ch['options'] ?? [];
                if (!empty($opts)) {
                    // Filter out sizes that have 0 stock in variations if variations exist
                    if (!empty($variations)) {
                        $inStock = array_values(array_filter($opts, function ($opt) use ($variations) {
                            $optClean = trim((string)$opt);
                            foreach ($variations as $v) {
                                if (str_contains((string)$v['type'], $optClean) && $v['stock'] > 0) {
                                    return true;
                                }
                            }
                            return false;
                        }));
                        if (!empty($inStock)) {
                            return array_map('trim', $inStock);
                        }
                    }
                    return array_map('trim', $opts);
                }
            }
        }

        return $fallback;
    }

    private function isEnglish(): bool
    {
        return app()->getLocale() === 'en';
    }

    private function localizedSetting(array $cfg, string $key, string $arDefault, string $enDefault): string
    {
        if ($this->isEnglish()) {
            $enVal = trim((string)($cfg[$key . '_en'] ?? ''));
            if ($enVal !== '') {
                return $enVal;
            }
            $val = trim((string)($cfg[$key] ?? ''));
            if ($val !== '' && !preg_match('/[\x{0600}-\x{06FF}]/u', $val)) {
                return $val;
            }
            return $enDefault;
        }
        $val = trim((string)($cfg[$key] ?? ''));
        return $val !== '' ? $val : $arDefault;
    }

    private function translateCategoryName(string $name, ?string $slug = null): string
    {
        if ($slug) {
            $bySlug = [
                'men'         => 'Men',
                'youth'       => 'Youth',
                'kids'        => 'Kids',
                'jumbo'       => 'Jumbo Sizes',
                'suits'       => 'Suits',
                'maawaz'      => 'Maawaz',
                'shoes'       => 'Shoes & Sandals',
                'accessories' => 'Accessories',
                'sportswear'  => 'Sportswear',
                'formal'      => 'Formal',
                'casual'      => 'Casual',
                'shirts'      => 'Shirts',
                'pants'       => 'Pants',
                'jackets'     => 'Jackets',
            ];
            $key = strtolower(trim($slug));
            if (isset($bySlug[$key])) {
                return $bySlug[$key];
            }
        }

        $trimmed = trim($name);
        $map = [
            'رجالي'          => 'Men',
            'ملابس رجالية'   => 'Men Clothing',
            'شبابي'          => 'Youth',
            'ملابس شبابية'   => 'Youth Clothing',
            'ولادي'          => 'Kids',
            'ملابس ولادية'   => 'Kids Clothing',
            'أطفال'          => 'Kids',
            'جامبو'          => 'Jumbo Sizes',
            'مقاسات جامبو'   => 'Jumbo Sizes',
            'بدلات'          => 'Suits',
            'بدلات رسمية'    => 'Formal Suits',
            'معاوز'          => 'Maawaz',
            'معاوز يمنية'    => 'Yemeni Maawaz',
            'أحذية'          => 'Shoes',
            'أحذية وصنادل'   => 'Shoes & Sandals',
            'إكسسوارات'      => 'Accessories',
            'اكسسوارات'      => 'Accessories',
            'رياضي'          => 'Sportswear',
            'رسمي'           => 'Formal',
            'كاجوال'         => 'Casual',
            'قمصان'          => 'Shirts',
            'بناطيل'         => 'Pants',
            'جاكيتات'        => 'Jackets',
            'ثوب'            => 'Thobes',
            'أثواب'          => 'Thobes',
            'الكل'           => 'All',
        ];

        return $map[$trimmed] ?? $trimmed;
    }

    private function translatedValue($model, string $field, $default = null)
    {
        if (!$model || !$model->relationLoaded('translations')) {
            return $default;
        }
        foreach ($model->translations as $tr) {
            if (($tr->key ?? null) === $field && $tr->value !== null && $tr->value !== '') {
                return $tr->value;
            }
        }
        return $default;
    }

    private function formatCatalogProduct(Product $product): array
    {
        $images = $product->identity_image_full_path;
        $discInfo = $this->effectiveDiscount($product, (float)$product->price);
        $ratingAvg = $product->rating->first()->average ?? 0;

        return [
            'id'            => $product->id,
            'name'          => $this->translatedValue($product, 'name', $product->name),
            'price'         => (float)$product->price,
            'final_price'   => $discInfo['final_price'],
            'discount'      => (float)$discInfo['discount'],
            'discount_type' => $discInfo['discount_type'],
            'total_stock'    => (int)$product->total_stock,
            'try_on_enabled' => (bool)$product->try_on_enabled,
            'image'          => $images[0] ?? asset('public/assets/admin/img/160x160/2.png'),
            'images'        => $images,
            'color_images'  => $product->color_images_list,
            'sizes'         => $this->extractSizes($product, []),
            'rating'        => round((float)$ratingAvg, 1),
            'brand'         => $product->brand ? [
                'id'    => $product->brand->id,
                'name'  => $this->translatedValue($product->brand, 'name', $product->brand->name),
                'image' => $product->brand->image_full_path,
            ] : null,
        ];
    }
}
