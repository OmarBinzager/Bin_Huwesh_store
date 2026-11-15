<?php

namespace App\Model;

use App\Traits\StorefrontMediaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontShoeShelfItem extends Model
{
    use StorefrontMediaTrait;

    protected $table = 'storefront_shoe_shelf_items';

    protected $guarded = ['id'];

    protected $casts = [
        'product_id'     => 'integer',
        'fallback_price' => 'integer',
        'sizes'          => 'array',
        'colors'         => 'array',
        'visible'        => 'boolean',
        'sort_order'     => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
