<?php

namespace Susheelbhai\Larapay\Repository;

use Susheelbhai\Larapay\Models\Payment;

class COD{

    public function paymentRequest($data)
    {
        $data = [
            'order_id' => rand(1111,9999)
        ];
        return $data;
    }

    public function paymentResponce($request)
    {
        $success = true;

        $error = "Payment Failed";

        

        if ($success === true) {
            
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'success' =>  true
            ];
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => $error
            ];
        }
        return $data;
    }
}