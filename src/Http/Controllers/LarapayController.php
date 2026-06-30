<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Susheelbhai\Larapay\Models\Payment;
use Susheelbhai\Larapay\Models\PaymentTemp;
use Susheelbhai\Larapay\Models\PaymentRefund;

// This controller is developed to publish in main controller
class LarapayController extends Controller
{
    public function preOrderMethod( $request, $gateway)
    {
        $request_from = request()->headers->get('referer');
        
        $user = Auth::guard('web')->user();
        if (!$user) {
            abort(401, 'User must be authenticated to make a payment.');
        }
        
        $request['gst_percentage'] = 18;
        $request['gst'] = 0.01 * $request['gst_percentage'];
        $request['customer_id'] = $user->id;
        $request['name'] = $user->name;
        $request['phone'] = $user->phone;
        $request['email'] = $user->email;
    }
    public function postOrderMethod( $request, $order_id, $gateway)
    {
        $input = $request->all();
        $temp_data = [
            'payment_gateway_id' => $gateway,
            'amount' => $input['amount'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'payment_product_type_id' => $input['payment_product_type_id'] ?? null,
            'user_id' => Auth::guard('web')->user()->id,
        ];
        PaymentTemp::updateOrCreate(
            ['order_id' => $order_id],
            $temp_data
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
    public function paymentFailed($request, $data, $payment_temp)
    {
        PaymentTemp::updateOrCreate(
            ['order_id' => $data['payment_data']['order_id'] ?? $request['order_id']],
            [
                'payment_status' => 0,
            ]
        );
        return true;
    }

    public function paymentRefunded( $payment_data, $response)
    {
        if ($response['status'] == "processed") {
            PaymentRefund::updateOrCreate(
                [
                    'payment_id' => $payment_data['id'],
                    'refund_id' => $response['refund_id'],
                    'amount' => $response['amount_refunded'],
                ]
            );
        }

        return true;
    }
}
