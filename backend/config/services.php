<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'embedding_dimensions' => (int) env('OPENAI_EMBEDDING_DIMENSIONS', 1536),
        'rerank_model' => env('OPENAI_RERANK_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 10),
        'rerank_timeout' => (int) env('OPENAI_RERANK_TIMEOUT', 15),
    ],

    'weather' => [
        'provider' => env('WEATHER_API_PROVIDER', 'openweather'),
        'key' => env('WEATHER_API_KEY'),
        'timeout' => (int) env('WEATHER_TIMEOUT', 3),
        'cache_ttl' => (int) env('WEATHER_CACHE_TTL', 1800),
    ],

    'recommendation' => [
        'timezone' => env('RECOMMENDATION_TIMEZONE', 'Asia/Ho_Chi_Minh'),
        'rate_limit_per_minute' => (int) env('RECOMMENDATION_RATE_LIMIT_PER_MINUTE', 5),
    ],

];
