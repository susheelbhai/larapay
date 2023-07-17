<?php

namespace Susheelbhai\Larapay\Repository;

use Razorpay\Api\Api;
use Susheelbhai\Larapay\Models\Payment;
use Razorpay\Api\Errors\SignatureVerificationError;

class Razorpay
{

    public $razorpay_key_id;
    public $razorpay_key_secret;
    public $payment_env;
    public $language;
    public $currency;
    public function __construct()
    {
        $this->razorpay_key_id = config('larapay.razorpay.razorpay_key_id');
        $this->razorpay_key_secret = config('larapay.razorpay.razorpay_key_secret');
        $this->payment_env = config('larapay.settings.payment_env');
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }

    public function paymentRequest($data)
    {
        $api = new Api($this->razorpay_key_id, $this->razorpay_key_secret);
        $orderData = [
            'receipt'         => 'rcptid_11',
            'amount'          =>  $data['amount'] * 100, // 39900 rupees in paise
            'currency'        => $this->currency
        ];

        $razorpayOrder = $api->order->create($orderData);
        return $razorpayOrder;
    }

    public function paymentResponce($request)
    {
        $success = true;

        $error = "Payment Failed";

        if (empty($request['razorpay_payment_id']) === false) {
            $api = new Api($this->razorpay_key_id, $this->razorpay_key_secret);

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
