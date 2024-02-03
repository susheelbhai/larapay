<?php

return [

    'settings' => [
        'language' => 'EN',
        'currency' => 'INR',
        'payment_env' => env('PAYMENT_ENV', 'local'),
        'redirection_waiting_time' => env('PAYMENT_REDIRECTION_WAITIONG_TIME', 7),
    ],

    'razorpay' => [
        'razorpay_key_id' => env('RAZORPAY_KEY_ID'),
        'razorpay_key_secret' => env('RAZORPAY_KEY_SECRET'),
    ],
    'pinelabs' => [
        'ppc_MerchantID' => env('PINELAB_MERCHANT_ID'),
        'ppc_MerchantAccessCode' => env('PINELAB_ACCESS_CODE'),
        'secret_key' => env('PINELAB_SECRET_CODE'),
        'ppc_TransactionType' => '1',
        'ppc_NavigationMode'		=>	'2',
        'ppc_PayModeOnLandingPage' => '1,3,4,10,11,14',
    ],
    'stripe' => [
        'stripe_publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'stripe_secret_key' => env('STRIPE_SECRET_KEY'),
    ],
    'ccavanue' => [
        'merchant_id' => env('CCAVANUE_MERCHANT_ID'),
        'access_code' => env('CCAVANUE_ACCESS_CODE'),
        'working_key' => env('CCAVANUE_WORKING_KEY'),
    ],
    'phonepe' => [
        'merchant_id' => env('PHONEPE_MERCHANT_ID'),
        'api_key' => env('PHONEPE_API_KEY'),
    ],
    

    

];
