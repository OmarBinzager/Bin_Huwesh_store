<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Model\Seller;
use App\Model\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function __construct(
        private Seller $seller,
        private Product $product
    ){}

    /**
     * الحصول على قائمة البائعين
     */
    public function getSellers(): JsonResponse
    {
        $sellers = $this->seller->active()->latest()->get();

        $sellers->transform(function ($seller) {
            return [
                'id' => $seller->id,
                'name' => $seller->name,
                'slug' => $seller->slug,
                'logo' => $seller->logo,
                'description' => $seller->description,
                'is_featured' => $seller->is_featured,
                'products_count' => $seller->products()->count(),
                'rating' => 0, // يمكن إضافة نظام تقييم للبائعين
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $sellers
        ], 200);
    }

    /**
     * الحصول على تفاصيل بائع معين
     */
    public function getSellerDetails($id): JsonResponse
    {
        $seller = $this->seller->active()->find($id);

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => translate('Seller not found'),
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'slug' => $seller->slug,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'logo' => $seller->logo,
                'description' => $seller->description,
                'address' => $seller->address,
                'city' => $seller->city,
                'state' => $seller->state,
                'country' => $seller->country,
                'website' => $seller->website,
                'is_featured' => $seller->is_featured,
                'products_count' => $seller->products()->count(),
            ]
        ], 200);
    }

    /**
     * الحصول على منتجات بائع معين
     */
    public function getSellerProducts(Request $request, $id): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $seller = $this->seller->active()->find($id);

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => translate('Seller not found'),
                'data' => null
            ], 404);
        }

        $products = $seller->products()
            ->active()
            ->with(['reviews' => function($query) {
                $query->where('is_active', 1);
            }])
            ->latest()
            ->paginate($perPage);

        $productsData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'discount' => $product->discount,
                'image' => $product->image,
                'rating' => round($product->reviews->avg('rating') ?? 0, 1),
                'stock' => $product->total_stock,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'seller' => [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'logo' => $seller->logo,
                ],
                'products' => $productsData,
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
