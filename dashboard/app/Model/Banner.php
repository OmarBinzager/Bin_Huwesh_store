<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $casts = [
        'product_id'  => 'integer',
        'status'      => 'integer',
        'category_id' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'sections'     => 'array',
        'product_ids'  => 'array',
        'category_ids' => 'array',
    ];

    protected $fillable = [
        'image', 'product_id', 'category_id', 'status',
        'display_type', 'sort_order', 'click_action',
        'external_link', 'sections', 'product_ids',
        'category_ids', 'scroll_direction', 'title'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function getImageFullPathAttribute(): string
    {
        $image = $this->image ?? null;
        $path = asset('public/assets/admin/img/900x400/img1.jpg');

        if (!is_null($image) && Storage::disk('public')->exists('banner/' . $image)) {
            $path = asset('storage/app/public/banner/' . $image);
        }
        return $path;
    }

    // دالة isMultiItem - تأكد من وجودها مرة واحدة فقط
    public function isMultiItem(): bool
    {
        return in_array($this->display_type, ['carousel_horizontal', 'carousel_vertical', 'grid_2x2']);
    }

    // هل البانر قابل للضغط؟
    public function isClickable(): bool
    {
        return $this->click_action !== 'none';
    }

    // ما هو الرابط أو الهدف عند الضغط؟
    public function getClickUrlAttribute(): ?string
    {
        switch ($this->click_action) {
            case 'product':
                return $this->product ? route('product.details', $this->product->id) : null;
            case 'category':
                return $this->category ? route('category.products', $this->category->id) : null;
            case 'link':
                return $this->external_link;
            default:
                return null;
        }
    }

    // جلب جميع العناصر (منتجات أو فئات) لعرضها داخل البانر
    public function getItems()
    {
        if ($this->isMultiItem()) {
            if ($this->product_ids && count($this->product_ids) > 0) {
                return Product::whereIn('id', $this->product_ids)->active()->get();
            }
            if ($this->category_ids && count($this->category_ids) > 0) {
                return Category::whereIn('id', $this->category_ids)->active()->get();
            }
        }
        // حالة البانر العادي (single item)
        if ($this->product_id) {
            return Product::find($this->product_id);
        }
        if ($this->category_id) {
            return Category::find($this->category_id);
        }
        return null;
    }

    // نطاق لجلب البانرات حسب القسم
    public function scopeForSection($query, $sectionName)
    {
        return $query->where('status', 1)
                     ->whereJsonContains('sections', $sectionName)
                     ->orderBy('sort_order');
    }
}
