<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Susheelbhai\Larapay\Models\Payment;
use Susheelbhai\Larapay\Models\PaymentTemp;

// This controller is developed to publish in main controller
class LarapayController extends Controller
{
    public function preOrderMethod( $request, $gateway)
    {
        $input = $request->all();
        $request_from = request()->headers->get('referer');
        $request['gst_percentage'] = 18;
        $request['gst'] = 0.01 * $request['gst_percentage'];
        $request['patient_id'] = Auth::guard('web')->user()->id;
        $request['name'] = Auth::guard('web')->user()->name;
        $request['phone'] = Auth::guard('web')->user()->phone;
        $request['email'] = Auth::guard('web')->user()->email;
    }
    public function postOrderMethod( $request, $order_id, $gateway)
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
