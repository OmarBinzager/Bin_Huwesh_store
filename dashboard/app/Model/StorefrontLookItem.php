<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontLookItem extends Model
{
    protected $table = 'storefront_look_items';

    protected $guarded = ['id'];

    protected $casts = [
        'look_id'        => 'integer',
        'product_id'     => 'integer',
        'hotspot_x'      => 'float',
        'hotspot_y'      => 'float',
        'fallback_price' => 'integer',
        'sizes'          => 'array',
        'sort_order'     => 'integer',
    ];

    public function look(): BelongsTo
    {
        return $this->belongsTo(StorefrontLook::class, 'look_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
