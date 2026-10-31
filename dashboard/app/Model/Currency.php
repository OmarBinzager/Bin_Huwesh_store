<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'country',
        'currency_code',
        'currency_symbol',
        'symbol_position',
        'decimal_digits',
        'exchange_rate',
        'status',
        'is_base'
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'status' => 'boolean',
        'is_base' => 'boolean',
        'decimal_digits' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeBase($query)
    {
        return $query->where('is_base', 1);
    }

    /**
     * Get system base currency
     */
    public static function getBaseCurrency()
    {
        return self::where('is_base', 1)->first() 
            ?? self::where('currency_code', BusinessSetting::where(['key' => 'currency'])->value('value') ?? 'YER')->first();
    }
}
