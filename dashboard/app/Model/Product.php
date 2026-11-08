<?php

namespace App\Model;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $casts = [
        'tax'         => 'float',
        'price'       => 'float',
        'capacity'    => 'float',
        'status'      => 'integer',
        'discount'    => 'float',
        'total_stock' => 'integer',
        'set_menu'    => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'is_featured' => 'integer',
        'weight'      => 'float',
        'brand_id'    => 'integer',
        'seller_id'   => 'integer',
        'warranty_years' => 'integer',
        'purchase_price' => 'float',
        'size_chart_id'  => 'integer',
        'color_images'   => 'array',
    ];

    public function translations(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany('App\Model\Translation', 'translationable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function active_reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class)->where(['is_active' => 1])->latest();
    }

    public function wishlist(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Wishlist::class)->latest();
    }

    public function rating(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class)
            ->where('is_active', 1)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->groupBy('product_id');
    }

    public function all_rating(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class)
            ->select(DB::raw('avg(rating) average, product_id'))
            ->groupBy('product_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function($query){
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }

    public function order_details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function getIdentityImageFullPathAttribute()
    {
        $value = $this->image ?? [];
        $imageUrlArray = is_array($value) ? $value : json_decode($value, true);
        if (is_array($imageUrlArray)) {
            foreach ($imageUrlArray as $key => $item) {
                if (Storage::disk('public')->exists('product/' . $item)) {
                    $imageUrlArray[$key] = asset('storage/app/public/product/'. $item) ;
                } else {
                    $imageUrlArray[$key] = asset('public/assets/admin/img/160x160/2.png');
                }
            }
        }
        return $imageUrlArray;
    }

    public function brand(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function seller(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function size_chart(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SizeChart::class, 'size_chart_id');
    }

    public function getActiveSizeChartAttribute(): ?SizeChart
    {
        if ($this->size_chart_id) {
            $chart = SizeChart::where('id', $this->size_chart_id)->where('is_active', true)->first();
            if ($chart) return $chart;
        }

        // Direct product override in size chart
        $directChart = SizeChart::where('is_active', true)->whereJsonContains('product_ids', (int)$this->id)->first();
        if ($directChart) return $directChart;

        // Fallback to category size chart
        $categoryIds = is_array($this->category_ids) ? $this->category_ids : json_decode($this->category_ids, true);
        if (!empty($categoryIds)) {
            $catIds = array_column($categoryIds, 'id');
            $catChart = SizeChart::where('is_active', true)->whereIn('category_id', $catIds)->first();
            if ($catChart) return $catChart;
        }

        return null;
    }

    public function getColorImagesListAttribute(): array
    {
        $data = $this->color_images;
        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }
        if (!is_array($data)) return [];

        foreach ($data as &$item) {
            if (!empty($item['image'])) {
                $img = $item['image'];
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists('product/' . $img)) {
                    $item['image_url'] = asset('storage/app/public/product/' . $img);
                } else {
                    $item['image_url'] = asset('public/assets/admin/img/160x160/2.png');
                }
            } else {
                $item['image_url'] = asset('public/assets/admin/img/160x160/2.png');
            }
        }
        return $data;
    }

    public function erp_mappings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InfoSoftProductMapping::class, 'product_id');
    }

    public function erp_mapping(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InfoSoftProductMapping::class, 'product_id');
    }
}
