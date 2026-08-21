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

    'payway' => [
        'base_url' => env('PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
        'merchant_id' => env('PAYWAY_MERCHANT_ID'),
        'api_key' => env('PAYWAY_API_KEY'),
        'currency' => env('PAYWAY_CURRENCY', 'USD'),
        'payment_option' => env('PAYWAY_PAYMENT_OPTION', 'abapay_khqr'),
        'qr_lifetime_minutes' => (int) env('PAYWAY_QR_LIFETIME_MINUTES', 10),
        'callback_url' => env('PAYWAY_CALLBACK_URL', rtrim((string) env('APP_URL'), '/').'/api/payments/payway/callback'),
        'allow_sandbox_simulation' => (bool) env('PAYWAY_ALLOW_SANDBOX_SIMULATION', false),
    ],

    'keycloak' => [
        'enabled' => (bool) env('KEYCLOAK_ENABLED', false),
        'base_url' => rtrim((string) env('KEYCLOAK_BASE_URL', 'http://localhost:8080'), '/'),
        'public_url' => rtrim((string) env('KEYCLOAK_PUBLIC_URL', env('KEYCLOAK_BASE_URL', 'http://localhost:8080')), '/'),
        'realm' => env('KEYCLOAK_REALM', 'minipos'),
        'client_id' => env('KEYCLOAK_CLIENT_ID', 'minipos-web'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect_uri' => env('KEYCLOAK_REDIRECT_URI', rtrim((string) env('APP_URL'), '/').'/api/auth/keycloak/callback'),
        'frontend_callback' => env('KEYCLOAK_FRONTEND_CALLBACK', 'http://localhost:5173/auth/sso/callback'),
    ],

];
