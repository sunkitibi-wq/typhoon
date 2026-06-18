<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'baas' => [
        'driver' => env('BAAS_PROVIDER', 'mock'),
        'api_key' => env('BAAS_API_KEY'),
        'secret' => env('BAAS_API_SECRET'),
        'base_url' => env('BAAS_BASE_URL', 'https://api.sandbox.baas-provider.com'),
    ],

    'exchange_rates' => [
        'source' => env('EXCHANGE_RATE_SOURCE', 'mock'),
        'coinmarketcap_key' => env('COINMARKETCAP_API_KEY'),
        'coinmarketcap_url' => env('COINMARKETCAP_BASE_URL', 'https://pro-api.coinmarketcap.com'),
        'coinapi_key' => env('COINAPI_API_KEY'),
    ],

    'cybersource' => [
        'merchant_id' => env('CYBERSOURCE_MERCHANT_ID'),
        'api_key_id' => env('CYBERSOURCE_API_KEY_ID'),
        'shared_secret' => env('CYBERSOURCE_SHARED_SECRET'),
        'environment' => env('CYBERSOURCE_ENVIRONMENT', 'sandbox'),
        'base_url' => env('CYBERSOURCE_ENVIRONMENT') === 'production'
            ? 'https://api.cybersource.com'
            : 'https://apitest.cybersource.com',
    ],

];
