<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CategoryLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private Category $category
    ){}


    /**
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        $categories = $this->category->where(['position'=>0,'status'=>1])->orderBY('priority', 'ASC')->get();
        return response()->json($categories, 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function getChildes($id): JsonResponse
    {
        $categories = $this->category->where(['parent_id' => $id,'status'=>1])->get();
        return response()->json($categories, 200);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
  public function getProducts($id, Request $request): JsonResponse
{
    $products = CategoryLogic::products($id, $request['limit'], $request['offset']);
    
    // ✅ تعديل هنا: جلب بيانات البراند والبائع لكل منتج قبل الـ formatting
    foreach ($products['products'] as $product) {
        $product->load(['brand', 'seller']);
    }
    
    $products['products'] = Helpers::product_data_formatting($products['products'], true);
    
    // ✅ تعديل هنا: إضافة بيانات البراند والبائع كاملة بعد الـ formatting
    foreach ($products['products'] as &$product) {
        // جلب البراند من العلاقة
        $originalProduct = \App\Model\Product::find($product['id']);
        if ($originalProduct && $originalProduct->brand) {
            $product['brand'] = [
                'id' => $originalProduct->brand->id,
                'name' => $originalProduct->brand->name,
                'image' => $originalProduct->brand->image_full_path ?? '',
            ];
        } else {
            $product['brand'] = null;
        }
        
        // جلب البائع من العلاقة
        if ($originalProduct && $originalProduct->seller) {
            $product['seller'] = [
                'id' => $originalProduct->seller->id,
                'name' => $originalProduct->seller->name,
                'image' => $originalProduct->seller->logo_full_path ?? '',
            ];
        } else {
            $product['seller'] = null;
        }
        
        // إذا كان المنتج مضاف بواسطة admin
        if ($product['added_by'] == 'admin') {
            $product['seller'] = [
                'id' => 0,
                'name' => 'In House Shop',
                'image' => '',
            ];
        }
    }
    
    return response()->json($products, 200);
}

    /**
     * @param $id
     * @return JsonResponse
     */
    public function getAllProducts($id): JsonResponse
    {
        return response()->json(Helpers::product_data_formatting(CategoryLogic::all_products($id), true), 200);

    }
}
