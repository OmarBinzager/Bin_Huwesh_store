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
use App\Model\StorefrontRailItem;
use App\Model\StorefrontShoeShelfItem;
use App\Model\StorefrontVirtualFitItem;
use App\Traits\StorefrontMediaTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class StorefrontController extends Controller
{
    public const CACHE_KEY = 'storefront_bootstrap_v1';

    /**
     * Unified, cached bootstrap payload for the Next.js 3D Storefront home page.
     * Maps relational DB records & live product prices/sizes into the exact
     * TypeScript contract shapes expected by the 3D/motion components.
     */
    public function bootstrap(): JsonResponse
    {
        $payload = Cache::remember(self::CACHE_KEY, 300, function () {
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
            ];
        });

        return response()->json($payload, 200);
    }

    /**
     * Paginated & filterable category catalog for /categories/[slug].
     */
    public function getCategoryCatalog(string $slugOrId, Request $request): JsonResponse
    {
        $category = $this->resolveCategory($slugOrId);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $limit = max(1, min(48, (int)$request->input('limit', 20)));
        $offset = max(1, (int)$request->input('offset', 1));
        $sortBy = $request->input('sort_by', 'latest');
        $brandId = $request->input('brand_id');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $search = trim((string)$request->input('q', ''));

        $allCatIds = array_merge([$category->id], $category->childes()->pluck('id')->toArray());

        $query = Product::active()
            ->with(['brand', 'rating'])
            ->where(function ($q) use ($allCatIds) {
                foreach ($allCatIds as $cid) {
                    $q->orWhereJsonContains('category_ids', ['id' => (string)$cid])
                      ->orWhereJsonContains('category_ids', ['id' => (int)$cid]);
                }
            });

        if ($brandId) {
            $query->where('brand_id', (int)$brandId);
        }
        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->where('price', '>=', (float)$minPrice);
        }
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->where('price', '<=', (float)$maxPrice);
        }
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        match ($sortBy) {
            'low_to_high' => $query->orderBy('price', 'ASC'),
            'high_to_low' => $query->orderBy('price', 'DESC'),
            'popular'     => $query->orderBy('popularity_count', 'DESC'),
            default       => $query->latest(),
        };

        $paginator = $query->paginate($limit, ['*'], 'page', $offset);

        $products = collect($paginator->items())->map(fn(Product $p) => $this->formatCatalogProduct($p))->values();

        return response()->json([
            'category' => [
                'id'        => $category->id,
                'name'      => $category->name,
                'image'     => $category->image_full_path,
                'children'  => $category->childes()->active()->get()->map(fn($c) => [
                    'id'    => $c->id,
                    'name'  => $c->name,
                    'image' => $c->image_full_path,
                ]),
            ],
            'total_size' => $paginator->total(),
            'limit'      => $limit,
            'offset'     => $offset,
            'products'   => $products,
        ], 200);
    }

    /**
     * Full product details + SizeChart + color_images + related products.
     */
    public function getProductDetail(int $id): JsonResponse
    {
        $product = Product::active()
            ->with(['brand', 'rating', 'active_reviews.customer', 'size_chart'])
            ->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $formatted = $this->formatCatalogProduct($product);
        $formatted['description'] = $product->description;
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

        $formatted['reviews'] = $product->active_reviews->take(10)->map(fn(Review $r) => [
            'id'         => $r->id,
            'rating'     => (int)$r->rating,
            'comment'    => $r->comment,
            'customer'   => $r->customer ? trim($r->customer->f_name . ' ' . $r->customer->l_name) : 'عميل بن هويش',
            'created_at' => $r->created_at?->toIso8601String(),
        ])->values();

        $related = Product::active()
            ->where('id', '!=', $product->id)
            ->when($product->brand_id, fn($q) => $q->where('brand_id', $product->brand_id))
            ->latest()
            ->take(8)
            ->get()
            ->map(fn(Product $p) => $this->formatCatalogProduct($p))
            ->values();

        return response()->json([
            'product'          => $formatted,
            'related_products' => $related,
        ], 200);
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
            'terms_and_conditions' => $settings['terms_and_conditions'] ?? '',
            'privacy_policy'       => $settings['privacy_policy'] ?? '',
            'return_policy'        => $settings['return_policy'] ?? '',
            'refund_policy'        => $settings['refund_policy'] ?? '',
            'cancellation_policy'  => $settings['cancellation_policy'] ?? '',
            'about_us'             => $settings['about_us'] ?? '',
            'faq'                  => $settings['faq'] ?? '',
        ], 200);
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

                    return [
                        'id'        => $it->uid,
                        'label'     => $it->label,
                        'category'  => $it->category,
                        'hotspot'   => ['x' => (float)$it->hotspot_x, 'y' => (float)$it->hotspot_y],
                        'productId' => $it->product_id ? (string)$it->product_id : null,
                        'demoPrice' => $livePrice,
                        'livePrice' => $livePrice,
                        'isDemo'    => $it->product_id === null,
                        'sizes'     => $sizes,
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
            'name' => $it->category_name,
        ])->values()->all();

        return [
            'version'  => 1,
            'settings' => [
                'title'     => $cfg['title'] ?? 'وصل حديثًا',
                'subtitle'  => $cfg['subtitle'] ?? 'جرّب القطعة على المودل واختر مقاسك ولونك المفضل',
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
                'title'    => $cfg['title'] ?? 'أحذية تكمل إطلالتك',
                'subtitle' => $cfg['subtitle'] ?? 'تشكيلة مختارة من الأحذية الكلاسيكية والكاجوال',
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
                'tag'         => number_format($price) . ' ر.ي',
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
            'title'    => $cfg['title'] ?? 'تسوّق حسب القسم',
            'subtitle' => $cfg['subtitle'] ?? 'استكشف أقسام بن هويش المتنوعة',
            'cta'      => $cfg['cta'] ?? 'تصفّح القسم',
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
                'cards'    => $systemCategories->map(fn(Category $cat) => [
                    'id'         => 'cat-' . $cat->id,
                    'categoryId' => (string)$cat->id,
                    'name'       => $cat->name,
                    'caption'    => '',
                    'href'       => '/categories/' . $cat->id,
                    'image'      => $cat->image_full_path,
                    'alt'        => $cat->name,
                    'focus'      => ['x' => 50, 'y' => 50],
                    'size'       => 'regular',
                    'visible'    => true,
                ])->values()->all(),
            ];
        }

        return [
            'version'  => 1,
            'settings' => $settings,
            'cards'    => $cards->map(fn(StorefrontCategoryCard $c) => [
                'id'         => $c->uid,
                'categoryId' => $c->category_id ? (string)$c->category_id : $c->slug,
                'name'       => $c->name ?: ($c->category->name ?? ''),
                'caption'    => $c->caption ?? '',
                'href'       => '/categories/' . ($c->category_id ?: $c->slug),
                'image'      => $c->image_url,
                'alt'        => $c->alt ?: $c->name,
                'focus'      => ['x' => (int)$c->focus_x, 'y' => (int)$c->focus_y],
                'size'       => 'regular',
                'visible'    => true,
            ])->values()->all(),
        ];
    }

    private function buildBrands(array $cfg): ?array
    {
        $brands = Brand::active()->where('is_featured', 1)->orderBy('id')->get();
        if ($brands->isEmpty()) {
            return null;
        }

        return [
            'version'  => 1,
            'settings' => [
                'title'     => $cfg['title'] ?? 'ماركات عالمية معتمدة',
                'showTitle' => true,
                'speed'     => (int)($cfg['speed'] ?? 38),
                'direction' => $cfg['direction'] ?? 'right',
                'logoSize'  => (int)($cfg['logoSize'] ?? 34),
            ],
            'brands' => $brands->map(fn(Brand $b) => [
                'id'        => 'brand-' . $b->id,
                'name'      => $b->name,
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

        return [
            'version'      => 1,
            'testimonials' => [
                'title'    => $cfg['testimonials_title'] ?? 'قالوا عن بن هويش',
                'subtitle' => $cfg['testimonials_subtitle'] ?? 'آراء عملائنا الموثقة',
                'items'    => $reviews->map(fn(Review $r) => [
                    'id'       => 'rev-' . $r->id,
                    'quote'    => $r->comment ?? '',
                    'name'     => $r->customer ? trim($r->customer->f_name . ' ' . $r->customer->l_name) : 'عميل بن هويش',
                    'verified' => true,
                    'source'   => 'تقييم متجر بن هويش',
                    'rating'   => (int)$r->rating,
                    'visible'  => true,
                ])->values()->all(),
            ],
            'contact' => [
                'title'        => $cfg['contact_title'] ?? 'ننتظرك في بن هويش',
                'subtitle'     => $cfg['contact_subtitle'] ?? 'زورنا في المعرض أو تواصل معنا مباشرة',
                'company'      => $settingsMap['ecommerce_name'] ?? 'شركة بن هويش التجارية',
                'address'      => $settingsMap['address'] ?? 'عدن، اليمن',
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

    private function formatCatalogProduct(Product $product): array
    {
        $images = $product->identity_image_full_path;
        $discInfo = $this->effectiveDiscount($product, (float)$product->price);
        $ratingAvg = $product->rating->first()->average ?? 0;

        return [
            'id'            => $product->id,
            'name'          => $product->name,
            'price'         => (float)$product->price,
            'final_price'   => $discInfo['final_price'],
            'discount'      => (float)$discInfo['discount'],
            'discount_type' => $discInfo['discount_type'],
            'total_stock'   => (int)$product->total_stock,
            'image'         => $images[0] ?? asset('public/assets/admin/img/160x160/2.png'),
            'images'        => $images,
            'color_images'  => $product->color_images_list,
            'sizes'         => $this->extractSizes($product, []),
            'rating'        => round((float)$ratingAvg, 1),
            'brand'         => $product->brand ? [
                'id'    => $product->brand->id,
                'name'  => $product->brand->name,
                'image' => $product->brand->image_full_path,
            ] : null,
        ];
    }
}
