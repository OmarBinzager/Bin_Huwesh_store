<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Brand;
use App\Model\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(
        private Brand $brand,
        private Product $product
    ){}

    /**
     * الحصول على قائمة جميع العلامات التجارية النشطة
     * @return JsonResponse
     */
    public function getBrands(): JsonResponse
    {
        $brands = $this->brand->active()->withCount('products')->latest()->get();

        $brands->transform(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'description' => $brand->description,
                'image' => $brand->image,
                'is_featured' => $brand->is_featured,
                'products_count' => $brand->products_count,
            ];
        });

        return response()->json($brands, 200);
    }

    /**
     * الحصول على قائمة العلامات التجارية المميزة
     * @return JsonResponse
     */
    public function getFeaturedBrands(): JsonResponse
    {
        $brands = $this->brand->active()->where('is_featured', 1)->withCount('products')->latest()->get();

        $brands->transform(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'description' => $brand->description,
                'image' => $brand->image,
                'is_featured' => $brand->is_featured,
                'products_count' => $brand->products_count,
            ];
        });

        return response()->json($brands, 200);
    }

    /**
     * الحصول على العلامات التجارية مع التخزين المؤقت
     * @return JsonResponse
     */
    public function getBrandsWithCache(): JsonResponse
    {
        return $this->getBrands();
    }

    /**
     * الحصول على تفاصيل علامة تجارية محددة
     * @param int $id
     * @return JsonResponse
     */
    public function getBrandDetails($id): JsonResponse
    {
        $brand = $this->brand->active()->withCount('products')->find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => translate('Brand not found'),
                'data' => null
            ], 404);
        }

        $brandData = [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'description' => $brand->description,
            'image' => $brand->image,
            'is_featured' => $brand->is_featured,
            'status' => $brand->status,
            'products_count' => $brand->products_count,
            'created_at' => $brand->created_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $brandData
        ], 200);
    }

    /**
     * الحصول على منتجات علامة تجارية محددة
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getBrandProducts(Request $request, $id): JsonResponse
    {
        $perPage = $request->query('per_page', $request->query('limit', 15));
        $brand = $this->brand->active()->find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => translate('Brand not found'),
                'data' => null
            ], 404);
        }

        $products = $brand->products()
            ->active()
            ->withCount(['wishlist', 'active_reviews'])
            ->with(['rating', 'brand', 'seller'])
            ->latest()
            ->paginate($perPage);

        $formattedProducts = Helpers::product_data_formatting($products->items(), true);

        // Ensure brand info is attached to each formatted product
        foreach ($formattedProducts as &$product) {
            $product['brand'] = [
                'id' => $brand->id,
                'name' => $brand->name,
                'image' => $brand->image,
            ];
        }
        unset($product);

        return response()->json([
            'success' => true,
            'data' => [
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'image' => $brand->image,
                ],
                'products' => $formattedProducts,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            ]
        ], 200);
    }
}
