<?php

return [

    'settings' => [
        'language' => 'EN',
        'currency' => 'INR',
        'payment_env' => env('PAYMENT_ENV', 'local'),
        'redirection_waiting_time' => env('PAYMENT_REDIRECTION_WAITIONG_TIME', 7),
        'unable_payment_response' => env('UNABLE_PAYMENT_RESPONSE', 0),
    ],

    'razorpay' => [
        'razorpay_key_id' => env('RAZORPAY_KEY_ID','sjkdhg'),
        'razorpay_key_secret' => env('RAZORPAY_KEY_SECRET','sjkdhg'),
    ],
    'pinelabs' => [
        'ppc_MerchantID' => env('PINELAB_MERCHANT_ID','sjkdhg'),
        'ppc_MerchantAccessCode' => env('PINELAB_ACCESS_CODE','sjkdhg'),
        'secret_key' => env('PINELAB_SECRET_CODE','sjkdhg'),
        'ppc_TransactionType' => '1',
        'ppc_NavigationMode'		=>	'2',
        'ppc_PayModeOnLandingPage' => '1,3,4,10,11,14',
    ],
    'stripe' => [
        'stripe_publishable_key' => env('STRIPE_PUBLISHABLE_KEY', 'jhdsfg'),
        'stripe_secret_key' => env('STRIPE_SECRET_KEY', 'jhdsfg'),
    ],
    'ccavanue' => [
        'merchant_id' => env('CCAVANUE_MERCHANT_ID', 'jhsdfg'),
        'access_code' => env('CCAVANUE_ACCESS_CODE', 'jhsdfg'),
        'working_key' => env('CCAVANUE_WORKING_KEY', 'jhsdfg'),
    ],
    'phonepe' => [
        'merchant_id' => env('PHONEPE_MERCHANT_ID', '625325'),
        'api_key' => env('PHONEPE_API_KEY', '625325'),
        'salt_key' => env('PHONEPE_SALT_KEY', '625325'),
        'salt_index' => env('PHONEPE_SALT_INDEX', '1'),
    ],
    'cashfree' => [
        'app_id' => env('CASHFREE_APP_ID', '625325'),
        'secret_key' => env('CASHFREE_SECRET_KEY', '625325'),
        'api_version' => env('CASHFREE_API_VERSION', '2023-08-01'),
    ],
    
];
