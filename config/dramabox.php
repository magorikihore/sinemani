<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DramaBox Configuration
    |--------------------------------------------------------------------------
    */

    // Coins awarded to new users upon registration
    'signup_bonus_coins' => env('DRAMABOX_SIGNUP_BONUS', 50),

    // Coins awarded for watching an ad
    'ad_reward_coins' => env('DRAMABOX_AD_REWARD', 5),

    // Daily reward schedule (day => coins)
    'daily_reward_schedule' => [
        1 => 5,
        2 => 10,
        3 => 15,
        4 => 20,
        5 => 25,
        6 => 30,
        7 => 50,
    ],

    // Subscription pricing defaults (TZS)
    'subscriptions' => [
        'weekly_price' => env('DRAMABOX_SUB_WEEKLY', 7500),
        'monthly_price' => env('DRAMABOX_SUB_MONTHLY', 25000),
        'yearly_price' => env('DRAMABOX_SUB_YEARLY', 200000),
        'daily_coin_bonus' => env('DRAMABOX_VIP_DAILY_BONUS', 10),
    ],

    // Payment gateway (payin.co.tz)
    'payment' => [
        'gateway_url' => env('PAYMENT_GATEWAY_URL', 'https://api.payin.co.tz/api/v1'),
        'api_key' => env('PAYMENT_GATEWAY_API_KEY', ''),
        'api_secret' => env('PAYMENT_GATEWAY_API_SECRET', ''),
        'callback_url' => env('PAYMENT_CALLBACK_URL', ''),
        'timeout' => env('PAYMENT_GATEWAY_TIMEOUT', 30),
        'currency' => 'TZS',
        'operators' => ['mpesa', 'tigopesa', 'airtelmoney', 'halopesa'],
    ],

    // Video settings
    'video' => [
        'max_upload_size' => env('DRAMABOX_MAX_VIDEO_SIZE', 512000), // KB
        'allowed_formats' => ['mp4', 'mov', 'avi', 'mkv'],
        'thumbnail_max_size' => 2048, // KB
    ],

    // Pagination defaults
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],

    // Rate limiting
    'rate_limits' => [
        'api' => env('DRAMABOX_API_RATE_LIMIT', 60), // per minute
        'auth' => env('DRAMABOX_AUTH_RATE_LIMIT', 5), // per minute
        'upload' => env('DRAMABOX_UPLOAD_RATE_LIMIT', 10), // per minute
    ],
];
