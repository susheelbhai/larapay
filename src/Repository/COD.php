<?php

namespace Susheelbhai\Larapay\Repository;

use Susheelbhai\Larapay\Models\Payment;

class COD
{

    public function paymentRequest($data)
    {
        $data = [
            'order_id' => 'cod_' . uniqid() . '_' . rand(1000, 9999)
        ];
        return response(['data' => $data], 200);
    }

    public function paymentResponce($request)
    {
        $success = true;

        $error = "Payment Failed";



        if ($success === true) {

            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'payment_data' => [
                    'order_id' => $request['order_id'],
                    'payment_id' => $request['payment_id'] ?? 'cod_' . uniqid(),
                    'amount' => $request['amount'],
                ]
            ];
        } else {
            $data = [
                'success' => false,
                'redirect_url' => $request->redirect_url,
                'msg' => $error,
                'payment_data' => [
                    'order_id' => $request['order_id'] ?? null,
                    'payment_id' => null,
                    'amount' => 0,
                ]
            ];
        }
        return $data;
    }

    public function makeRefund($payment, $amount, $speed = "normal", $notes = null)
    {
        return [
            'refund_id' => null,
            'status' => 'failed',
            'message' => 'COD payments cannot be refunded through a payment gateway.',
        ];
    }
}
