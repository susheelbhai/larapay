<?php

return [

    'pinelabs' => [
        'ppc_MerchantID' => env('PINELAB_MERCHANT_ID'),
        'ppc_MerchantAccessCode' => env('PINELAB_ACCESS_CODE'),
        'secret_key' => env('PINELAB_SECRET_CODE'),
        'ppc_TransactionType' => '1',
        'ppc_NavigationMode'		=>	'2',
        'ppc_PayModeOnLandingPage' => '1,3,4,10,11,14',
    ]

    

    

];
