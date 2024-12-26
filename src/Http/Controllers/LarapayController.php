<?php

namespace App\Http\Controllers;

use Susheelbhai\Larapay\Models\Payment;
use Susheelbhai\Larapay\Models\PaymentTemp;

// This controller is developed to publish in main controller
class LarapayController extends Controller
{
    public function updateTempTable( $request, $order_id, $gateway)
    {
        $input = $request->all();
        PaymentTemp::updateOrCreate(
            ['order_id' => $order_id],
            [
                'payment_gateway_id' => $gateway,
                'amount' => $input['amount'],
            ]
        );
    }
    public function paymentSuccessful($request, $data, $payment_temp)
    {

        Payment::updateOrCreate(
            ['payment_id' => $data['payment_data']['payment_id']],
            [
                'order_id' => $data['payment_data']['order_id'],
                'amount' => $payment_temp['amount'],
                'payment_gateway_id' => $payment_temp['payment_gateway_id'],
                'payment_status'=>1,
            ]
        );
        return true;
    }
    public function paymentFailed($request)
    {
        PaymentTemp::updateOrCreate(
            ['order_id' => $request['order_id']],
            [
                'payment_status' => 0,
            ]
        );
        return true;
    }
}
