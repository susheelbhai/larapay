<?php

namespace App\Http\Controllers;

use Susheelbhai\Larapay\Models\Payment;
use Susheelbhai\Larapay\Models\PaymentTemp;

// This controller is developed to publish in main controller
class LarapayController extends Controller
{
    public function updateTempTable($order_id, $input)
    {
        PaymentTemp::updateOrCreate(
            ['order_id' => $order_id],
            [
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
                'amount' => $request['amount'],
            ]
        );
        return true;
    }
    public function paymentFailed($request)
    {
        return true;
    }
}
