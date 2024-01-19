<?php

use Illuminate\Support\Facades\DB;

return [
    /*
    |--------------------------------------------------------------------------
    | Default API Provider
    |--------------------------------------------------------------------------
    |
    | $Fintech supports more than one API providers. This configuration file gives
    | you convenient access to each API provider which is defined in the model.
    | This configuration setts the default API provider.
    |
    */

    'default' => DB::table('configuration')->first(),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | The provider array defines services for every API provider which are
    | being used in he project. There may be further services defined for
    | a specific API. This will give th API links for the same.
    |
    | Providers: "eko", "paysprint"
    |
    */

    'eko' => [
        'aeps' => [
            'merchant_authentication' => 'https://staging.eko.in/ekoapi/v2/aeps/aepsmerchantauth',
            'transaction' => 'https://staging.eko.in/ekoapi/v2/aeps',
            'inquiry' =>  'https://staging.eko.in/ekoapi/v1/transactions'
        ],

        'bbps' => [
            'list' => [
                'category' => 'https://staging.eko.in/ekoapi/v2/billpayments/operators_category',
                'location' => 'https://staging.eko.in/ekoapi/v2/billpayments/operators_location',
                'operator' => 'https://staging.eko.in/ekoapi/v2/billpayments/operators_location'
            ],

            'parameter' => 'https://staging.eko.in/ekoapi/v2/billpayments/operators',

            'bill' => [
                'fetch' => 'https://staging.eko.in/ekoapi/v2/billpayments/fetchbill?initiator_id=9962981729',
                'pay' => 'https://staging.eko.in/ekoapi/v2/billpayments/paybill?initiator_id=9962981729'
            ]
        ],

        'dmt' => [
            'customer' => [
                'info' => 'https://staging.eko.in/ekoapi/v2/customers/mobile_number:',
                'create' => 'https://staging.eko.in/ekoapi/v2/customers/mobile_number:',
                'verify' => 'https://staging.eko.in/ekoapi/v2/customers/verification/otp:',
            ],

            'recipient' => [
                'list' => 'https://staging.eko.in/ekoapi/v2/customers/mobile_number:',
            ],

            'transaction' => [
                'initiate' => 'https://staging.eko.in/ekoapi/v2/transactions',
                'inquiry' => 'https://staging.eko.in/ekoapi/v2/transactions/',
            ],
        ]
    ]
];
