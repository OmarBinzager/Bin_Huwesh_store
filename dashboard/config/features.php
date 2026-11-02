<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Business Feature Toggles
    |--------------------------------------------------------------------------
    |
    | These flags control whether specific business modules are visible and
    | configurable within the admin dashboard.
    | Setting any flag to `true` re-enables the feature immediately.
    |
    */

    'customer_wallet' => env('FEATURE_CUSTOMER_WALLET', false),
    'loyalty_points'  => env('FEATURE_LOYALTY_POINTS', false),
    'delivery_man'    => env('FEATURE_DELIVERY_MAN', false),
    'ai_integration'  => env('FEATURE_AI_INTEGRATION', false),
    'branches'        => env('FEATURE_BRANCHES', false),
    'banners'         => env('FEATURE_BANNERS', false),
    'multi_vendor'    => env('FEATURE_MULTI_VENDOR', false),
    'flash_sales'     => env('FEATURE_FLASH_SALES', false),
    'system_addons'   => env('FEATURE_SYSTEM_ADDONS', false),
];
