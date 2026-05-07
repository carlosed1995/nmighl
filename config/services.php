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
        // Orígenes que pueden embeber la app (Custom Page / iframe). Vacío o GHL_EMBED_FRAME_ANCESTORS=false desactiva.
        'embed_frame_ancestors' => (function () {
            $raw = env('GHL_EMBED_FRAME_ANCESTORS');
            if ($raw === false || $raw === '0' || $raw === 'false' || $raw === '') {
                return [];
            }
            if ($raw === null) {
                return [
                    'https://app.gohighlevel.com',
                    'https://*.gohighlevel.com',
                    'https://*.msgsndr.com',
                    'https://*.leadconnectorhq.com',
                ];
            }

            return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
        })(),
    ],

    'nmi' => [
        'api_url' => env('NMI_API_URL', 'https://secure.networkmerchants.com/api/transact.php'),
        'subscriptions_api_url' => env('NMI_SUBSCRIPTIONS_API_URL', 'https://sandbox.signup.nmi.com/api/v1'),
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
        'enable_from_nmi_webhook' => env('IPROCESS_ENABLE_FROM_NMI_WEBHOOK', false),
        // Temporal/operativo: permite probar iProcess -> contacto sin crear/sync invoice en GHL.
        'sync_invoice_to_ghl' => env('IPROCESS_SYNC_INVOICE_TO_GHL', true),
        // Cuando false, crea invoice pero omite record-payment (no cambia a paid en GHL).
        'mark_invoice_paid_in_ghl' => env('IPROCESS_MARK_INVOICE_PAID_IN_GHL', true),
        // Fallbacks para createInvoice cuando payload/contacto no trae email o telefono valido E.164.
        'invoice_fallback_email' => env('IPROCESS_INVOICE_FALLBACK_EMAIL'),
        'invoice_fallback_phone' => env('IPROCESS_INVOICE_FALLBACK_PHONE'),
        'fallback_ghl_contact_id' => env('IPROCESS_FALLBACK_GHL_CONTACT_ID'),
    ],

];
