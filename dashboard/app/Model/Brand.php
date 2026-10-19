<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    protected $table = 'brands';

    protected $fillable = [
        'name',
        'image',
        'slug',
        'description',
        'status',
        'is_featured'
    ];

    protected $casts = [
        'status' => 'integer',
        'is_featured' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // علاقة المنتجات
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    // المنتجات النشطة فقط
    public function active_products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'brand_id')->where('status', 1);
    }

    // سكوب للعلامات التجارية النشطة
    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    // الحصول على مسار الصورة كاملاً
    public function getImageFullPathAttribute()
    {
        $image = $this->image;
        if ($image && Storage::disk('public')->exists('brand/' . $image)) {
            return asset('storage/app/public/brand/' . $image);
        }
        return asset('public/assets/admin/img/160x160/2.png');
    }

    // الترجمة إذا كنت بحاجة لها
    public function translations(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany('App\Model\Translation', 'translationable');
    }
}
