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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | External Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external service integrations used in the application
    |
    */

    'notifications' => [
        'url' => env('NOTIFICATION_SERVICE_URL', 'https://api.notifications.example.com'),
        'key' => env('NOTIFICATION_SERVICE_KEY', ''),
        'timeout' => env('NOTIFICATION_SERVICE_TIMEOUT', 10),
        'retry_attempts' => env('NOTIFICATION_SERVICE_RETRY_ATTEMPTS', 3),
    ],

    'analytics' => [
        'timeout' => env('ANALYTICS_SERVICE_TIMEOUT', 15),
        'batch_size' => env('ANALYTICS_BATCH_SIZE', 100),
        'providers' => [
            'google_analytics' => [
                'endpoint' => env('GA_ENDPOINT', 'https://www.google-analytics.com/collect'),
                'api_key' => env('GA_API_KEY', ''),
                'auth_type' => 'Bearer',
                'enabled' => env('GA_ENABLED', false),
            ],
            'mixpanel' => [
                'endpoint' => env('MIXPANEL_ENDPOINT', 'https://api.mixpanel.com/track'),
                'api_key' => env('MIXPANEL_API_KEY', ''),
                'auth_type' => 'Basic',
                'enabled' => env('MIXPANEL_ENABLED', false),
            ],
            'amplitude' => [
                'endpoint' => env('AMPLITUDE_ENDPOINT', 'https://api2.amplitude.com/2/httpapi'),
                'api_key' => env('AMPLITUDE_API_KEY', ''),
                'auth_type' => 'Bearer',
                'enabled' => env('AMPLITUDE_ENABLED', false),
            ],
        ],
    ],

    'webhooks' => [
        'timeout' => env('WEBHOOK_TIMEOUT', 5),
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 2),
        'endpoints' => [
            'task_created' => env('WEBHOOK_TASK_CREATED_URL', ''),
            'task_updated' => env('WEBHOOK_TASK_UPDATED_URL', ''),
            'task_completed' => env('WEBHOOK_TASK_COMPLETED_URL', ''),
        ],
    ],

];