<?php

namespace App\Model;

use App\Traits\StorefrontMediaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontCategoryCard extends Model
{
    use StorefrontMediaTrait;

    protected $table = 'storefront_category_cards';

    protected $guarded = ['id'];

    protected $casts = [
        'category_id' => 'integer',
        'focus_x'     => 'integer',
        'focus_y'     => 'integer',
        'visible'     => 'boolean',
        'sort_order'  => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getImageUrlAttribute(): string
    {
        return self::resolveMediaUrl($this->image_path, 'storefront/categories');
    }
}
