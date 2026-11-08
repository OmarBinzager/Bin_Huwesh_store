<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfoSoftProductMapping extends Model
{
    protected $table = 'infosoft_product_mappings';

    protected $guarded = ['id'];

    protected $casts = [
        'product_id'        => 'integer',
        'pack_qty'          => 'float',
        'last_synced_price' => 'float',
        'last_synced_stock' => 'float',
        'synced_at'         => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
