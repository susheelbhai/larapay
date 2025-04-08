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

    public function makeRefund($payment, $amount, $speed, $notes = null)
    {
        $api = new Api($this->razorpay_key_id, $this->razorpay_key_secret);
        $fetched_payment = $api->payment->fetch($payment['payment_id']);
        $available_amount = $fetched_payment->amount - $fetched_payment->amount_refunded;
        $amount = $amount == 'full' ? $available_amount : $amount * 100;
        if ($amount > $available_amount) {
            return $responce = [
                'refund_id' => null,
                'status' => 'insufficient_balance',
            ];
        }
        try {
            $data = $api->payment->fetch($payment['payment_id'])->refund(array("amount" => $amount, "speed" => $speed, "notes" => $notes));
            $responce = [
                'refund_id' => $data->id,
                'status' => $data->status,
                'speed_processed' => $data->speed_processed,
                'speed_requested' => $data->speed_requested,
                'amount_refunded' => $data->amount / 100,
            ];
        } catch (\Throwable $th) {
            $responce = [
                'refund_id' => null,
                'status' => 'already_refunded',
                'message' => $th->getMessage(),
            ];
        }
        return $responce;
    }
}
