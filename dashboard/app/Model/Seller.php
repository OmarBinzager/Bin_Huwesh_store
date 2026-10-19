<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Seller extends Model
{
    protected $table = 'sellers';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'logo',
        'description',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'website',
        'status',
        'is_featured',
        'commission_rate'
    ];

    protected $casts = [
        'status' => 'integer',
        'is_featured' => 'integer',
        'commission_rate' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // علاقة المنتجات
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    // المنتجات النشطة فقط
    public function active_products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'seller_id')->where('status', 1);
    }

    // سكوب للبائعين النشطين
    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    // الحصول على مسار الصورة كاملاً
    public function getLogoFullPathAttribute()
    {
        $logo = $this->logo;
        if ($logo && Storage::disk('public')->exists('seller/' . $logo)) {
            return asset('storage/app/public/seller/' . $logo);
        }
        return asset('public/assets/admin/img/160x160/2.png');
    }

    // الحصول على اسم البائع المنسق
    public function getFormattedNameAttribute()
    {
        return $this->name . ($this->is_featured ? ' ⭐' : '');
    }
}
