<?php

namespace App\Model;

use App\Traits\StorefrontMediaTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontVirtualFitItem extends Model
{
    use StorefrontMediaTrait;

    protected $table = 'storefront_virtual_fit_items';

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
