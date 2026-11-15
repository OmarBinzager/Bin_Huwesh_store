<?php

namespace App\Model;

use App\Traits\StorefrontMediaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontRailItem extends Model
{
    use StorefrontMediaTrait;

    protected $table = 'storefront_rail_items';

    protected $guarded = ['id'];

    protected $casts = [
        'product_id'     => 'integer',
        'details'        => 'array',
        'fallback_price' => 'integer',
        'visible'        => 'boolean',
        'sort_order'     => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
