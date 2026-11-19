<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflinePaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'method_name',
        'image',
        'method_fields',
        'payment_note',
        'method_informations',
    ];

    protected $appends = ['image_full_url'];

    protected $casts = [
        'id' => 'integer',
        'method_fields' => 'array',
        'method_informations' => 'array',
        'status' => 'integer',
    ];

    public function getImageFullUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }
        return asset('storage/app/public/offline_payment/' . $this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }
}
