<?php

return [

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
    

    

];
