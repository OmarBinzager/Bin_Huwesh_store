<?php

namespace App\Model;

use App\Traits\StorefrontMediaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorefrontLook extends Model
{
    use StorefrontMediaTrait;

    protected $table = 'storefront_looks';

    protected $guarded = ['id'];

    protected $casts = [
        'sort_order'          => 'integer',
        'published'           => 'boolean',
        'image_width'         => 'integer',
        'image_height'        => 'integer',
        'scale'               => 'float',
        'offset_x'            => 'float',
        'offset_y'            => 'float',
        'whole_look_discount' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StorefrontLookItem::class, 'look_id')->orderBy('sort_order');
    }

    public function getImageUrlAttribute(): string
    {
        return self::resolveMediaUrl($this->image_path, 'storefront/looks');
    }
}
