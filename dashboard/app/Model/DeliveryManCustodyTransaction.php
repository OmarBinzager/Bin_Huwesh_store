<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DeliveryManCustodyTransaction extends Model
{
    protected $table = 'delivery_man_custody_transactions';

    protected $fillable = [
        'transaction_id',
        'delivery_man_id',
        'transaction_type',
        'order_id',
        'amount',
        'balance_before',
        'balance_after',
        'customer_id',
        'customer_name',
        'customer_phone',
        'settled_by_admin_id',
        'settlement_type',
        'payment_method',
        'reference',
        'notes',
    ];

    protected $casts = [
        'delivery_man_id' => 'integer',
        'order_id' => 'integer',
        'amount' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
        'customer_id' => 'integer',
        'settled_by_admin_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function deliveryMan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function admin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'settled_by_admin_id');
    }
}
