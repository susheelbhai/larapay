<?php

namespace Susheelbhai\Larapay\Http\Controllers;

use Razorpay\Api\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Susheelbhai\Larapay\Models\Payment;
use Razorpay\Api\Errors\SignatureVerificationError;

class PaymentController extends Controller
{

    public function form()
    {
        return view('larapay::payment.form');
    }
    public function index(Request $request)
    {
        $input = $request->all();
        $extra_input_array = array(
            'name' => 'Susheel Kumar Singh',
            'email' => 'susheelkrsingh306@gmail.com',
            'phone' => '9090653356',
            'redirect_url' => $input['redirect_url'],
        );
        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));
        $orderData = [
            'receipt'         => 'rcptid_11',
            'amount'          =>  $input['amount']*100, // 39900 rupees in paise
            'currency'        => 'INR'
        ];

        $razorpayOrder = $api->order->create($orderData);
        return view('larapay::payment.index', compact('razorpayOrder', 'input', 'extra_input_array'));
    }

    public function paymentResponce(Request $request)
    {
        $success = true;

        $error = "Payment Failed";

        if (empty($request['razorpay_payment_id']) === false) {
            $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

            try {
                // Please note that the razorpay order ID must
                // come from a trusted source (session here, but
                // could be database or something else)
                $attributes = array(
                    'razorpay_order_id' => $request['razorpay_order_id'],
                    'razorpay_payment_id' => $request['razorpay_payment_id'],
                    'razorpay_signature' => $request['razorpay_signature']
                );

                $api->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                $success = false;
                $error = 'Razorpay Error : ' . $e->getMessage();
            }
        }

        if ($success === true) {
            Payment::updateOrCreate(
                ['payment_id' => $request->razorpay_payment_id],
                [
                    'payment_gateway_id' => 1,
                    'order_id' => $request->razorpay_order_id,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'payment_status' => 1,
                ]
            );
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg'=>'payment successful'
            ] ;
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg'=>$error
            ];
        }
        return view('larapay::payment.response', compact('request', 'data'));

    }
}
