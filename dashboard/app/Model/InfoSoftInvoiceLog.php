<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfoSoftInvoiceLog extends Model
{
    protected $table = 'infosoft_invoice_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'order_id'         => 'integer',
        'attempts'         => 'integer',
        'request_payload'  => 'array',
        'response_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope for failed invoices needing retry (Dead-Letter Queue)
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }
}
