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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id_process' => env('TELEGRAM_CHAT_ID_PROCESS'),
        'chat_id_document' => env('TELEGRAM_CHAT_ID_DOCUMENT'),
    ],

    'nubefact' => [
        'token' => env('NUBEFACT_TOKEN'),
        'url'   => env('NUBEFACT_API_URL'),

        'serie_factura' => env('NUBEFACT_SERIE_FACTURA', 'FFF1'),
        'serie_boleta' => env('NUBEFACT_SERIE_BOLETA', 'BBB1'),

        'serie_nc_factura' => env('NUBEFACT_SERIE_NC_FACTURA', 'FFF1'),
        'serie_nc_boleta' => env('NUBEFACT_SERIE_NC_BOLETA', 'BBB1'),

        'system_user_id' => env('SYSTEM_USER_ID', 1),
    ],

    'decolecta' => [
        'token' => env('DECOLECTA_TOKEN'),
        'url'   => env('DECOLECTA_URL', 'https://api.decolecta.com/v1'),
    ],

];
