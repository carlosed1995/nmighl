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

    'ghl' => [
        'base_url' => env('GHL_API_BASE_URL', 'https://services.leadconnectorhq.com'),
        'api_version' => env('GHL_API_VERSION', '2021-07-28'),
        'invoice_api_version' => env('GHL_INVOICE_API_VERSION', '2023-02-21'),
        'oauth_base_url' => env('GHL_OAUTH_BASE_URL', 'https://marketplace.gohighlevel.com'),
        'agency_token' => env('GHL_AGENCY_TOKEN'),
        'use_private_integration' => env('GHL_USE_PRIVATE_INTEGRATION', false),
        'client_id' => env('GHL_CLIENT_ID'),
        'client_secret' => env('GHL_CLIENT_SECRET'),
        'redirect_uri' => env('GHL_REDIRECT_URI'),
        'scopes' => env('GHL_SCOPES', 'locations.readonly contacts.readonly'),
        'bridge_webhook_secret' => env('GHL_BRIDGE_WEBHOOK_SECRET'),
    ],

    'nmi' => [
        'api_url' => env('NMI_API_URL', 'https://secure.networkmerchants.com/api/transact.php'),
        'security_key' => env('NMI_SECURITY_KEY'),
        'webhook_signing_key' => env('NMI_WEBHOOK_SIGNING_KEY'),
        'sync_ghl_invoices' => env('NMI_SYNC_GHL_INVOICES_TO_NMI', true),
        'sync_approved_to_ghl' => env('NMI_SYNC_APPROVED_TO_GHL', true),
        'auto_create_from_webhook' => env('NMI_AUTO_CREATE_FROM_WEBHOOK', false),
        // add_invoice exige email. Si el contacto GHL no está en Laravel o no tiene email, se usa esto.
        // Sustituye en .env con tu bandeja real: NMI_INVOICE_FALLBACK_EMAIL (p. ej. operaciones@tudominio.com).
        'invoice_fallback_email' => env('NMI_INVOICE_FALLBACK_EMAIL') ?: 'noreply@example.com',
    ],

    'iprocess' => [
        'webhook_secret' => env('IPROCESS_WEBHOOK_SECRET'),
        'default_location_id' => env('IPROCESS_DEFAULT_LOCATION_ID'),
    ],

];
