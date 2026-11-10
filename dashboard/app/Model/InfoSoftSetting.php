<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class InfoSoftSetting extends Model
{
    protected $table = 'infosoft_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled'               => 'boolean',
        'live_stock_check'         => 'boolean',
        'is_barcode'               => 'boolean',
        'variant_grouping_enabled' => 'boolean',
        'sync_days_lookback'       => 'integer',
        'sync_frequency_minutes'   => 'integer',
        'last_sync_at'             => 'datetime',
        'last_connection_test_at'  => 'datetime',
    ];

    /**
     * Encrypt password when saving if provided
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt password when retrieving
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        if (empty($this->attributes['password'])) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['password']);
        } catch (\Exception $e) {
            return $this->attributes['password'];
        }
    }

    /**
     * Singleton helper to get or create active configuration
     */
    public static function getActive(): self
    {
        return self::firstOrCreate(['id' => 1], [
            'is_enabled'               => false,
            'api_base_url'             => 'http://127.0.0.1:8080',
            'username'                 => '',
            'default_warehouse'        => '01',
            'default_branch'           => '01',
            'sales_center'             => '01',
            'default_customer_code'    => '01001',
            'sales_person'             => 'WEBSITE',
            'api_user'                 => 'API_USER',
            'default_invoice_type'     => '06',
            'live_stock_check'         => true,
            'is_barcode'               => true,
            'variant_grouping_enabled' => true,
            'variant_separator'        => '-',
            'sync_days_lookback'       => 1,
            'sync_frequency_minutes'   => 30,
        ]);
    }
}
