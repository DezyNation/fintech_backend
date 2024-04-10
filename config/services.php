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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        'base_url' => 'https://api.razorpay.com'
    ],

    'paydeer' => [
        'key' => env('PAYDEER_CLIENT_KEY'),
        'secret' => env('PAYDEER_CLIENT_SECRET'),
        'base_url' => 'https://paydeer.in'
    ],

    'eko' => [
        'initiator_id' => env('EKO_INITIATOR_ID'),
        'key' => env('EKO_KEY'),
        'developer_key' => env('EKO_DEVELOPER_KEY'),
        'base_url' => 'https://api.eko.in:25002/ekoicici'
    ],

];
