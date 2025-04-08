<?php

namespace Susheelbhai\Larapay\Repository;


class PayU
{
    public $merchant_id;
    public $merchant_salt;
    public $cliect_id;
    public $cliect_secret;
    public $api_base_url;
    public $payment_env;
    public $language;
    public $currency;
    public function __construct()
    {
        $this->merchant_id = config('larapay.payu.merchant_id');
        $this->merchant_salt = config('larapay.payu.merchant_salt');
        $this->cliect_id = config('larapay.payu.cliect_id');
        $this->cliect_secret = config('larapay.payu.cliect_secret');
        $this->api_base_url = "https://test.payu.in";

        if (config('larapay.settings.payment_env') == 'production') {
            $this->api_base_url = "https://secure.payu.in";
        }
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }

    public function paymentRequest($data)
    {
        $merchantTransactionId = "order_" . rand(1000000000, 9999999999) . uniqid();
        $input = $this->merchant_id . '|' . $merchantTransactionId . '|' . $data['amount'] . '|' . 'a' . '|' . $data['name'] . '|' . $data['email'] . '|||||||||||' . $this->merchant_salt;
        $hash = hash('sha512', $input);
        $response = array(
            'hash' => $hash,
            'order_id' => $merchantTransactionId,
        );
        return response(['data' => $response], 200);
    }

    public function paymentResponce($request)
    {

        $response = $this->fetchPayment($request['order_id']);
        if ($response['payment_status'] === 'success') {
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

    private function baseApiCall($command, $order_id, $other_input = "")
    {
        $input = $this->merchant_id . '|' . $command . '|' . $order_id . '|' . $this->merchant_salt;
        $hash = hash('sha512', $input);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "$this->api_base_url/merchant/postservice.php?form=2",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "key=$this->merchant_id&command=$command&var1=$order_id&hash=$hash" . $other_input,
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return json_decode($response, true);
        }
    }

    public function fetchPayment($order_id)
    {
        $response = $this->baseApiCall("verify_payment", $order_id);
        $response = $response['transaction_details'][$order_id];
        return [
            'payment_time' => $response['addedon'],
            'payment_completion_time' => $response['addedon'],
            'payment_status' => $response['status'],
            'bank_reference' => $response['txnid'],
            'currency' => "INR",
            'amount' => $response['transaction_amount'],
            'order_id' => $order_id,
            'payment_id' => $response['mihpayid'],
            'original_data' => $response,
        ];
    }

    public function fetchRefund($order_id)
    {
        return $this->baseApiCall("getAllRefundsFromTxnIds", $order_id);
    }


    public function makeRefund($payment, $amount, $speed = "STANDARD", $notes = null)
    {

        $fetched_payment = $this->fetchPayment($payment['order_id']);
        $fetched_refunds = $this->fetchRefund($payment['order_id']);
        if ($fetched_refunds['msg'] == "No Refunds Found for the transaction.") {
            $amount_refunded = 0;
        } else {
            $amount_refunded = array_sum(array_column($fetched_refunds['Refund Details'][$payment['payment_id']], 'Amount'));
        }
        $available_amount = $fetched_payment['amount'] - $amount_refunded;
        $amount = $amount == 'full' ? $available_amount : $amount;
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

            $merchantTransactionId = "refund_" . rand(1000000000, 9999999999) . uniqid();

            $response = $this->baseApiCall("cancel_refund_transaction", $payment['payment_id'], "&var2=$merchantTransactionId&var3=$amount&var5=" . route('home'));;
            if (isset($response->status) && $response['status'] == 1) {
                $responce = [
                    'refund_id' => $response->request_id,
                    'status' => $response->status == 1 ? 'processed' : 'unknown',
                    'speed_processed' => 'normal',
                    'speed_requested' => 'normal',
                    'amount_refunded' => $amount,
                ];
            } else {
                $responce = [
                    'refund_id' => null,
                    'status' => $response->code,
                    'message' => $response->message,
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
