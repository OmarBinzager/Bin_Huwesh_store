<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SizeChart extends Model
{
    protected $table = 'size_charts';

    protected $guarded = ['id'];

    protected $casts = [
        'columns'     => 'array',
        'rows'        => 'array',
        'product_ids' => 'array',
        'is_active'   => 'boolean',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function direct_products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'size_chart_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get human-friendly linkage label matching Bin Huwesh design:
     * e.g., "12 منتجاً", "تصنيف القمصان", "تصنيف الأحذية"
     */
    public function getLinkageTextAttribute(): string
    {
        if (!empty($this->product_ids) && is_array($this->product_ids) && count($this->product_ids) > 0) {
            $count = count($this->product_ids);
            return $count === 1 ? 'منتج واحد مخصص' : "{$count} منتجات";
        }

        if ($this->category) {
            return 'تصنيف ' . $this->category->name;
        }

        $directCount = $this->direct_products()->count();
        if ($directCount > 0) {
            return "{$directCount} منتجاً";
        }

        return 'عام (غير مرتبط)';
    }
}
