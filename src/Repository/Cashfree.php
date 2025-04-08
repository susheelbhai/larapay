<?php

namespace Susheelbhai\Larapay\Repository;

use Exception;

class Cashfree
{
    public $app_id;
    public $secret_key;
    public $api_version;
    public $api_base_url;
    public $payment_env;
    public $language;
    public $currency;
    public function __construct()
    {
        $this->app_id = config('larapay.cashfree.app_id');
        $this->secret_key = config('larapay.cashfree.secret_key');
        $this->api_version = config('larapay.cashfree.api_version');
        $this->api_base_url = "https://sandbox.cashfree.com/pg";

        if (config('larapay.settings.payment_env') == 'production') {
            $this->api_base_url = "https://api.cashfree.com/pg";
        }
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }

    public function paymentRequest($data)
    {
        // dd($data);
        $post_data = [
            'order_currency' => $this->currency,
            'order_amount' => $data['amount'],
            'order_meta' => [
                'return_url' => $data['redirect_url'],
                'notify_url' => route('home'),
                'payment_methods' => "",
            ],
            'customer_details' => [
                'customer_id' => $data['customer_id'],
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['email'],
            ],
        ];
        $json_post_data = json_encode($post_data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_post_data);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_base_url . "/orders",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "x-api-version: $this->api_version",
                "x-client-id: $this->app_id",
                "x-client-secret: $this->secret_key"
            ],
        ]);

        $res = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response($err, 500);
        } else {
            $res = json_decode($res);
            $response = array(
                'cashfree_data' => $res,
                'order_id' => $res->order_id,
            );
            // dd($response);
            return response(['data' => $response], 200);
        }
    }

    public function paymentResponce($request)
    {
        $response = $this->fetchPayment($request['order_id']);
        if ($response['payment_status'] === 'SUCCESS') {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'payment_data' => [
                    'order_id' => $response['order_id'],
                    'payment_id' => $response['payment_id'],
                    'amount' => $response['amount'],
                ]
            ];
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => 'something went wrong'
            ];
        }
        return $data;
    }

    public function fetchPayment($order_id)
    {


        $api_url = $this->api_base_url . "/orders/" . $order_id . "/payments";
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-api-version: $this->api_version",
                "x-client-id: $this->app_id",
                "x-client-secret: $this->secret_key"
            ],
        ]);

        $api_response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = json_decode($api_response)[0];
            // dd($response);
            return [
                'payment_time' => $response->payment_time,
                'payment_completion_time' => $response->payment_time,
                'payment_status' => $response->payment_status,
                'bank_reference' => $response->bank_reference,
                'currency' => $response->payment_currency,
                'amount' => $response->payment_amount,
                'order_id' => $response->order_id,
                'payment_id' => $response->cf_payment_id,
                'original_data' => json_decode($api_response),
            ];
        }
    }

    public function fetchRefund($order_id)
    {
        $api_url = $this->api_base_url . "/orders/" . $order_id . "/refunds";
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-api-version: $this->api_version",
                "x-client-id: $this->app_id",
                "x-client-secret: $this->secret_key"
            ],
        ]);

        $api_response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response = json_decode($api_response);
        }
    }


    public function makeRefund($payment, $amount, $speed = "STANDARD", $notes = null)
    {

        $fetched_payment = $this->fetchPayment($payment['order_id']);
        $fetched_refunds = $this->fetchRefund($payment['order_id']);
        $amount_refunded = array_sum(array_column($fetched_refunds, 'refund_amount'));
        $available_amount = $fetched_payment['amount'] - $amount_refunded;
        $amount = $amount == 'full' ? $available_amount : $amount * 100;
        if ($available_amount == 0) {
            return $responce = [
                'refund_id' => null,
                'status' => 'already_refunded',
                'message' => 'Payment is already refunded',
            ];
        }
        if ($amount > $available_amount) {
            return $responce = [
                'refund_id' => null,
                'status' => 'insufficient_balance',
            ];
        }
        try {
            $api_url = $this->api_base_url . "/orders/" . $payment['order_id'] . "/refunds";
            $speed = $speed == 'normal' ? 'STANDARD' : "INSTANT";
            $post_data = [
                'refund_amount' => $amount,
                'refund_id' => uniqid(),
                'refund_note' => $notes,
                'refund_speed' => $speed,
            ];
            $json_post_data = json_encode($post_data);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json_post_data);

            curl_setopt_array($curl, [
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "x-api-version: $this->api_version",
                    "x-client-id: $this->app_id",
                    "x-client-secret: $this->secret_key"
                ],
            ]);

            $res = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                $data = response($err, 500);
            } else {
                $data = json_decode($res);
            }
            if (isset($data->cf_refund_id)) {
                // dd($data->status_description);
                $responce = [
                    'refund_id' => $data->cf_refund_id,
                    'status' => $data->status_description == 'In Progress' ? 'processed' : 'unknown',
                    'speed_processed' => $data->refund_speed->processed,
                    'speed_requested' => $data->refund_speed->requested,
                    'amount_refunded' => $data->refund_amount,
                ];
            } else {
                $responce = [
                    'refund_id' => null,
                    'status' => $data->code,
                    'message' => $data->message,
                ];
            }
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
