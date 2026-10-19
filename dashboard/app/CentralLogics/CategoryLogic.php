<?php

namespace App\CentralLogics;

use App\Model\Category;
use App\Model\Product;

class CategoryLogic
{
    public static function parents()
    {
        return Category::where('position', 0)->get();
    }

    public static function child($parent_id)
    {
        return Category::where(['parent_id' => $parent_id])->get();
    }

    public static function products($category_id,$limit,$offset)
    {
        $categoryIds = [(int)$category_id, (string)$category_id];
        foreach (CategoryLogic::child($category_id) as $child) {
            $categoryIds[] = (int)$child['id'];
            $categoryIds[] = (string)$child['id'];
        }

        $products = Product::active()->get();
        $productIds = [];
        foreach ($products as $product) {
            $categories = json_decode($product['category_ids'], true);
            if (is_array($categories)) {
                foreach ($categories as $category) {
                    if (isset($category['id']) && in_array($category['id'], $categoryIds)) {
                        $productIds[] = $product['id'];
                        break;
                    }
                }
            }
        }
        $paginator = Product::active()->withCount(['wishlist', 'active_reviews'])->with('rating')->whereIn('id', $productIds)->paginate($limit, ['*'], 'page', $offset);
        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items()
        ];
    }

    public static function all_products($id)
    {
        $categoryIds = [];
        $categoryIds[] = (int)$id;
        $categoryIds[] = (string)$id;
        foreach (CategoryLogic::child($id) as $child){
            $categoryIds[] = (int)$child['id'];
            $categoryIds[] = (string)$child['id'];
            foreach (CategoryLogic::child($child['id']) as $ch2){
                $categoryIds[] = (int)$ch2['id'];
                $categoryIds[] = (string)$ch2['id'];
            }
        }

        $products = Product::active()->with('rating', 'active_reviews')->get();
        $productIds = [];
        foreach ($products as $product) {
            $categories = json_decode($product['category_ids'], true);
            if (is_array($categories)) {
                foreach ($categories as $category) {
                    if (isset($category['id']) && in_array($category['id'], $categoryIds)) {
                        $productIds[] = $product['id'];
                        break;
                    }
                }
            }
        }

        return Product::active()->withCount(['wishlist'])->with('rating', 'active_reviews')->whereIn('id', $productIds)->get();
    }
}
