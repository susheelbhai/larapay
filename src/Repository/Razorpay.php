<?php

namespace Susheelbhai\Larapay\Repository;

use Razorpay\Api\Api;
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
        try {
            $api = new Api($this->razorpay_key_id, $this->razorpay_key_secret);
        $orderData = [
            'receipt'         => 'rcptid_11',
            'amount'          =>  $data['amount'] * 100, // amount rupees in paise
            'currency'        => $this->currency
        ];

        $razorpayOrder = $api->order->create($orderData);
        $razorpayOrder['order_id'] = $razorpayOrder['id'];
            return response(['data' => $razorpayOrder], 200);
        } catch (\Exception $th) {
            $code = $th->getCode() == 0 ? 500 : $th->getHttpStatusCode();
            return response($th, $code);
        }
    }

    public function paymentResponce($request)
    {
        $success = true;
        $error = "Payment Failed";
        // dd($request);
        if (empty($request['payment_id']) === false) {
            $api = new Api($this->razorpay_key_id, $this->razorpay_key_secret);

            try {
                // Please note that the razorpay order ID must
                // come from a trusted source (session here, but
                // could be database or something else)
                $attributes = array(
                    'razorpay_order_id' => $request['order_id'],
                    'razorpay_payment_id' => $request['payment_id'],
                    'razorpay_signature' => $request['razorpay_signature']
                );
                $api->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                $success = false;
                $error = 'Razorpay Error : ' . $e->getMessage();
            }
        }

        if ($success === true) {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'payment_data' => [
                    'order_id' => $request['order_id'],
                    'payment_id' => $request['payment_id'],
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
