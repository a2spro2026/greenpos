<?php

return [
    'sms' => [
        'enabled' => (bool) env('SMS_ENABLED', false),
        'url' => env('SMS_GATEWAY_URL'),
        'api_key' => env('SMS_API_KEY'),
        'from' => env('SMS_FROM', 'GreenPOS'),
    ],

    'whatsapp' => [
        'enabled' => (bool) env('WHATSAPP_ENABLED', false),
        'url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v19.0'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'from' => env('WHATSAPP_FROM'),
    ],

    'email' => [
        'from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'GreenPOS')),
    ],
];
