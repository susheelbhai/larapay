<?php

namespace Susheelbhai\Larapay\Repository;

use Illuminate\Support\Facades\Http;

class Phonepe
{

    public $api_base_url;
    public $merchant_id;
    public $api_key;
    public $salt_key;
    public $payment_env;
    public $language;
    public $currency;
    public $salt_index; //key index 1

    public function __construct()
    {
        $this->api_base_url = "https://api-preprod.phonepe.com/apis/pg-sandbox";
        if (config('larapay.settings.payment_env') == 'production') {
            $this->api_base_url = "https://api.phonepe.com/apis/hermes";
        }
        $this->merchant_id = config('larapay.phonepe.merchant_id');
        $this->api_key = config('larapay.phonepe.api_key');
        $this->salt_key = config('larapay.phonepe.salt_key');
        $this->salt_index = config('larapay.phonepe.salt_index');
        $this->payment_env = config('larapay.settings.payment_env');
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }
    public function paymentRequest($input)
    {

        $api_url = $this->api_base_url."/pg/v1/pay";
        $action_url = 'undefined';
        $merchantTransactionId = "order_" . rand(1000000000, 9999999999).uniqid();
        $paymentData = array(
            'merchantId' => $this->merchant_id,
            "transactionId" => "TX123456789",
            "instrumentType" => "MOBILE",
            "instrumentReference" => "9999999999",
            "storeId" => "store1",
            "terminalId" => "terminal1",
            'merchantTransactionId' => $merchantTransactionId,
            "merchantUserId" => $input['customer_id'],
            'amount' => $input['amount'] * 100, // Amount in paisa (100 INR)
            'redirectUrl' => route('callback_url')."?redirect_url=". $input['redirect_url']."&order_id=".$merchantTransactionId,
            'param1' => $input['redirect_url'], //redirect user after complition of payment 
            'redirectMode' => "REDIRECT",
            'callbackUrl' => route('webhook',config('payment.gateway_id')),
            "merchantOrderId" => "YOUR_ORDER_ID",
            "mobileNumber" => $input['phone'],
            "message" => "Order description",
            "email" => $input['email'],
            "shortName" => $input['name'],
            "paymentInstrument" => array(
                "type" => "PAY_PAGE",
            )
        );
        // dd($paymentData);

        $jsonencode = json_encode($paymentData);
        $payloadMain = base64_encode($jsonencode);

        $payload = $payloadMain . "/pg/v1/pay" . $this->api_key;
        $sha256 = hash("sha256", $payload);
        $final_x_header = $sha256 . '###' . $this->salt_index;
        $request = json_encode(array('request' => $payloadMain));

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-VERIFY: " . $final_x_header,
                "accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $res = json_decode($response);

            if (isset($res->success) && $res->success == '1') {
                $paymentCode = $res->code;
                $paymentMsg = $res->message;
                $action_url = $res->data->instrumentResponse->redirectInfo->url;
                if ($this->payment_env == 'local') {
                    $action_url = $action_url;
                } elseif ($this->payment_env == 'production') {
                    $action_url = $action_url;
                }
            }
        }

        $response = array(
            'phonepe_data' => $res,
            'action_url' => $action_url,
            'order_id' => $merchantTransactionId,
        );
        // dd($response);
        return response(['data'=>$response], 200);
    }

    public function paymentResponce($request)
    {
        $api_end_point = "/pg/v1/status/$this->merchant_id/$request->order_id";
        $api_url = $this->api_base_url.$api_end_point;

        $sha256 = hash("sha256", $api_end_point.$this->salt_key);
        $final_x_header = $sha256 . '###' . $this->salt_index;
        $response = Http::withHeaders([
            'X-VERIFY' => $final_x_header,
        ])->get("$api_url");
        // dd($response['code']);
        if ($response['code'] == 'PAYMENT_SUCCESS') {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'waiting',
                'payment_data' => [
                    'order_id' => $response['data']['merchantTransactionId'],
                    'payment_id' => $response['data']['transactionId'],
                    'amount' => $response['data']['amount'],
                ]
            ];
        } else {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'waiting',
                'payment_data' => [
                    'order_id' => $request['order_id'],
                    'payment_id' => $request['providerReferenceId'],
                    'amount' => $request['amount'],
                ]
            ];
        }
        // dd($data);
        return $data;
    }
}
