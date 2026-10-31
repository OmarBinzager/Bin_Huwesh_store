<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeliveryManWallet extends Model
{
    protected $table = 'delivery_man_wallets';

    protected $fillable = [
        'delivery_man_id',
        'cash_in_hand',
        'total_collected_cash',
        'total_settled_amount',
        'collected_orders_count',
        'last_settlement_at',
    ];

    protected $casts = [
        'delivery_man_id' => 'integer',
        'cash_in_hand' => 'float',
        'total_collected_cash' => 'float',
        'total_settled_amount' => 'float',
        'collected_orders_count' => 'integer',
        'last_settlement_at' => 'datetime',
    ];

    public function deliveryMan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeliveryManCustodyTransaction::class, 'delivery_man_id', 'delivery_man_id');
    }

    /**
     * Get settlement status: 'settled', 'partially_settled', 'pending'
     */
    public function getSettlementStatusAttribute(): string
    {
        if ($this->cash_in_hand <= 0) {
            return 'settled';
        }
        if ($this->total_settled_amount > 0 && $this->cash_in_hand > 0) {
            return 'partially_settled';
        }
        return 'pending';
    }
}
