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
            return response(['data'=>$response], 200);
        }

    }

    public function paymentResponce($request)
    {

        $api_url = $this->api_base_url . "/orders/" . $request['order_id'] . "/payments";
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

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = json_decode($response)[0];
            if ($response->payment_status === 'SUCCESS') {
                $data = [
                    'success' =>  true,
                    'redirect_url' => $request->redirect_url,
                    'msg' => 'payment successful',
                    'payment_data' => [
                        'order_id' => $response->order_id,
                        'payment_id' => $response->cf_payment_id,
                        'amount' => $response->order_amount,
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
    }
}
