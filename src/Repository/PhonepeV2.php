<?php

namespace Susheelbhai\Larapay\Repository;

use Illuminate\Support\Facades\Http;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;
use PhonePe\Env;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutPayRequestBuilder;

class PhonepeV2
{

    public $api_base_url;
    public $merchant_id;
    public $api_key;
    public $salt_key;
    public $payment_env;
    public $language;
    public $currency;
    public $salt_index; //key index 1
    public $auth_url; //key index 1
    public $payload;
    public $accessToken;
    public $tokenExpiry;
    public $environment;


    public function __construct()
    {

        $this->environment = config('larapay.settings.payment_env') == 'local' ? 'uat' : 'prod';

        $auth_urls = [
            "prod" => "https://api.phonepe.com/apis/identity-manager/v1/oauth/token",
            "uat"  => "https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token"
        ];

        // Select URLs dynamically
        $this->auth_url = $auth_urls[$this->environment];

        // Payload with credentials — make sure these are securely stored in production
        $this->payload = [
            "client_id" => config('larapay.phonepe.client_id'),
            "client_version" => config('larapay.phonepe.client_version'),
            "client_secret" => config('larapay.phonepe.client_secret'),
            "grant_type" => "client_credentials"
        ];

        // Token storage
        $this->accessToken = null;
        $this->tokenExpiry = null;

        $this->api_base_url = "https://api-preprod.phonepe.com/apis/pg-sandbox";
        if (config('larapay.settings.payment_env') == 'production') {
            $this->api_base_url = "https://api.phonepe.com/apis/hermes";
        }
        $this->merchant_id = config('larapay.phonepe.merchant_id');
        $this->salt_key = config('larapay.phonepe.salt_key');
        $this->salt_index = config('larapay.phonepe.salt_index');
        $this->payment_env = config('larapay.settings.payment_env');
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }
    public function paymentRequest($input)
    {
        $payment_urls = [
            "prod" => "https://api.phonepe.com/apis/pg/checkout/v2/pay",
            "uat"  => "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay"
        ];
        $action_url = route('pay');
        $merchantTransactionId = "order_" . rand(1000000000, 9999999999) . uniqid();
        $token = $this->getValidToken();

        if (!$token) {
            http_response_code(500);
            echo json_encode(['error' => 'OAuth token not available. Please try again later.']);
            exit;
        }
        // dd($input);
        $requestData = [
            "merchantOrderId" => $merchantTransactionId,
            "amount" => $input['amount'] * 100,
            "expireAfter" => 1200,
            "metaInfo" => [
                "udf1" => "Name : " . $input['name'],
                "udf2" => "Email : " . $input['email'],
                "udf3" => "Phone : " . $input['phone'],
                "udf4" => "Address : " . $input['current_address'],
                "udf5" => "City : " . $input['city']
            ],
            "paymentFlow" => [
                "type" => "PG_CHECKOUT",
                "message" => "Payment message used for collect requests",
                "merchantUrls" => [
                    "redirectUrl" => route('callback_url') . "?redirect_url=" . $input['redirect_url'] . "&order_id=" . $merchantTransactionId
                ]
            ]
        ];

        $payment_url = $payment_urls[$this->environment];

        $ch = curl_init($payment_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: O-Bearer $token"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "cURL Error", "error" => $error_msg]);
            exit;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = json_decode($response, true);

        if ($httpCode == 200 && isset($result['redirectUrl'])) {
            $redirectUrl = $result['redirectUrl'];
            $response = array(
                'phonepe_data' => $result,
                'action_url' => $redirectUrl,
                'order_id' => $merchantTransactionId,
            );
        } else {
            $response = array(
                'phonepe_data' => $result,
                'action_url' => $action_url,
                'order_id' => $merchantTransactionId,
            );
        }
        // dd($response);
        return response(['data' => $response], $httpCode);
    }

    public function paymentResponce($request)
    {
        $response = $this->fetchPayment($request->order_id);
        // dd($response);
        if (!isset($response['payment_status']) || $response['payment_status'] === 'FAILED') {
            $data = [
                'success' =>  false,
                'redirect_url' => $request->redirect_url,
                'msg' => 'something went wrong'
            ];
        }
        elseif ($response['payment_status'] === 'COMPLETED') {
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
        }
        return $data;
    }

    public function fetchPayment($order_id)
    {
        $orderStatus_urls = [
            "prod" => "https://api.phonepe.com/apis/pg/checkout/v2/order/{merchantOrderId}/status",
            "uat"  => "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{merchantOrderId}/status"
        ];
        $token = $this->getValidToken();

        if (!$token) {
            http_response_code(500);
            echo json_encode(['error' => 'OAuth token not available. Please try again later.']);
            exit;
        }

        $urlTemplate = $orderStatus_urls[$this->environment];
        $url = str_replace('{merchantOrderId}', $order_id, $urlTemplate);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: O-Bearer $token"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // dd(json_decode($response, true));
        if ($httpCode === 200) {
            $data = json_decode($response, true);

            if (isset($data['state'])) {
                $state = $data['state'];
                $amount = $data['amount'] ?? 0;

                if ($state === "COMPLETED") {
                    $data1 = [
                        'payment_status' => $data['state'],
                        'bank_reference' => $data['paymentDetails'],
                        'currency' => "INR",
                        'amount' => $amount,
                        'order_id' => $order_id,
                        'payment_id' => $data['paymentDetails'][0]['transactionId'],
                        'original_data' => json_decode($response),
                    ];
                    return $data1;
                } elseif ($state === "FAILED") {
                    $data1 = [
                        'payment_status' => $data['state'],
                        'bank_reference' => $data['paymentDetails'],
                        'currency' => "INR",
                        'amount' => $amount,
                        'order_id' => $order_id,
                        'payment_id' => $data['paymentDetails'][0]['transactionId'],
                        'original_data' => json_decode($response),
                    ];
                    return $data1;
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Unexpected response from payment gateway."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to fetch payment status."]);
        }
    }
    public function fetchRefund($order_id)
    {

        $api_end_point = "/pg/v1/refund/$this->merchant_id/$order_id";
        $api_url = $this->api_base_url . $api_end_point;

        $sha256 = hash("sha256", $api_end_point . $this->salt_key);
        $final_x_header = $sha256 . '###' . $this->salt_index;
        $response = Http::withHeaders([
            'X-VERIFY' => $final_x_header,
        ])->get("$api_url");
        return 0;
    }

    public function makeRefund($payment, $amount, $speed, $notes = null)
    {
        $fetched_payment = $this->fetchPayment($payment['order_id']);
        $fetched_refunds = $this->fetchRefund($payment['order_id']);
        $available_amount = $fetched_payment['amount'] - $fetched_refunds;
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
            $responce = [
                'refund_id' => null,
                'status' => 'failed',
                'message' => "your payment Gateway does not support refund",
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




    private function fetchOAuthToken()
    {

        // Convert payload to URL-encoded string
        $postData = http_build_query($this->payload);

        $ch = curl_init($this->auth_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);

            if (isset($data['access_token'], $data['expires_at'])) {
                $this->accessToken = $data['access_token'];
                $this->tokenExpiry = $data['expires_at'] * 1000; // Convert to milliseconds
            } else {
                error_log("Missing token or expiry info in response: " . $response);
            }
        } else {
            error_log("Failed to fetch token. HTTP Code: $httpCode. Response: $response");
        }
    }

    /**
     * Get valid token, refresh if needed
     */
    private function getValidToken()
    {
        $currentTime = round(microtime(true) * 1000); // current time in ms

        // Refresh if token is about to expire (within 5 minutes)
        if (!$this->tokenExpiry || $this->tokenExpiry - $currentTime <= (5 * 60 * 1000)) {
            $this->fetchOAuthToken();
        }

        return $this->accessToken;
    }

    /**
     * Example endpoint: Return token if valid
     */
    public function tokenHandler()
    {
        $token = $this->getValidToken();

        if ($token) {
            $expiresIn = floor(($GLOBALS['tokenExpiry'] - round(microtime(true) * 1000)) / 1000);
            header('Content-Type: application/json');
            // echo json_encode([
            //     'access_token' => $token,
            //     'expires_in' => $expiresIn
            // ]);

            $output = (json_encode([
                'access_token' => $token,
                'expires_in' => $expiresIn
            ]));
            // print_r($output);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Token not available or expired.']);
            error_log("Access token is null or expired.");
        }
    }
}
