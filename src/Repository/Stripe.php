<?php

namespace Susheelbhai\Larapay\Repository;

use Susheelbhai\Larapay\Models\Payment;

class Stripe{

    public function paymentResponce($request)
    {
        $success = true;

        $error = "Payment Failed";


        if ($success === true) {
            Payment::updateOrCreate(
                ['payment_id' => $request->razorpay_payment_id],
                [
                    'payment_gateway_id' => $request->gateway,
                    'order_id' => $request->razorpay_order_id,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'payment_status' => 1,
                ]
            );
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