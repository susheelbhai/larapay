<?php

namespace Susheelbhai\Larapay\Repository;

class Stripe
{
    public function paymentRequest($data)
    {
        
        $data = [
            'order_id' => rand(1111, 9999)
        ];
        return response(['data' => $data], 200);
    }
    public function paymentResponce($request)
    {
        $success = true;

        $error = "Payment Failed";

        \Stripe\Stripe::setApiKey(config('larapay.stripe.stripe_secret_key'));

        if ($success === true) {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'payment_data' => [
                    'order_id' => $request['razorpay_order_id'],
                    'payment_id' => $request['razorpay_payment_id'],
                    'amount' => $request['amount'],
                ]
            ];
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment failed',
                'msg' => $error
            ];
        }
        return $data;
    }
}
