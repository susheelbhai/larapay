<?php

namespace Susheelbhai\Larapay\Repository;

class Phonepe
{

    public $merchant_id;
    public $api_key;
    public $payment_env;
    public $language;
    public $currency;
    public function __construct()
    {
        $this->merchant_id = config('larapay.phonepe.merchant_id');
        $this->api_key = config('larapay.phonepe.api_key');
        $this->payment_env = config('larapay.settings.payment_env');
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }
    public function paymentRequest($input)
    {

        // return $input['order_id'];
        $order_id =rand(11111111, 999999999);
        $action_url = 'undefined';
        $merchantTransactionId = "gd" . rand(2424, 444444444444);
        $paymentData = array(
            'merchantId' => $this->merchant_id,
            "transactionId" => "TX123456789",
            "instrumentType" => "MOBILE",
            "instrumentReference" => "9999999999",
            "storeId" => "store1",
            "terminalId" => "terminal1",
            'merchantTransactionId' => $merchantTransactionId,
            "merchantUserId" => "CUSTOMER_UNIQUE_ID",
            'amount' => $input['amount'] * 100, // Amount in paisa (100 INR)
            'redirectUrl' => route('callback_url'),
            'param1' => 'ggf',
            'redirectMode' => "POST",
            'callbackUrl' => $input['redirect_url'],
            "merchantOrderId" => "YOUR_ORDER_ID",
            "mobileNumber" => "7979851485",
            "message" => "Order description",
            "email" => "CUSTMER_EMAIL_ID",
            "shortName" => "CUSTMER_Name",
            "paymentInstrument" => array(
                "type" => "PAY_PAGE",
            )
        );

        $jsonencode = json_encode($paymentData);
        $payloadMain = base64_encode($jsonencode);

        $salt_index = 1; //key index 1
        $payload = $payloadMain . "/pg/v1/pay" . $this->api_key;
        $sha256 = hash("sha256", $payload);
        $final_x_header = $sha256 . '###' . $salt_index;
        $request = json_encode(array('request' => $payloadMain));
        $api_url = "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay";

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
            'order_id' => $order_id,
        );
        return response(['data'=>$response], 200);
    }

    public function paymentResponce($request)
    {
        // return $request;

        $request->redirect_url = route('pay');
        if ($request['code'] == 'PAYMENT_SUCCESS') {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'payment_data' => [
                    'order_id' => $request['transactionId'],
                    'payment_id' => '',
                    'amount' => $request['amount'],
                ]
            ];
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => '',
                'success' => false
            ];
        }
        // return $response; 
        return $data;
    }
}
