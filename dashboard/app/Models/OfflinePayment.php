<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflinePayment extends Model
{
    use HasFactory;

    protected $appends = ['receipt_image_full_url'];

    protected $casts = [
        'order_id' => 'integer'
    ];

    public function getReceiptImageFullUrlAttribute(): ?string
    {
        if (empty($this->receipt_image)) {
            return null;
        }
        return asset('storage/app/public/offline_payment_receipt/' . $this->receipt_image);
    }
}
