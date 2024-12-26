<?php

namespace Susheelbhai\Larapay\Repository;

use Exception;

class Cashfree
{

    public $app_id;
    public $secret_key;
    public $payment_env;
    public $language;
    public $currency;
    public function __construct()
    {
        $this->app_id = config('larapay.cashfree.app_id');
        $this->secret_key = config('larapay.cashfree.secret_key');
        if (config('larapay.settings.payment_env') == 'local') {
            $this->payment_env = \Cashfree\Cashfree::$SANDBOX;
        }
        if (config('larapay.settings.payment_env') == 'production') {
            $this->payment_env = \Cashfree\Cashfree::$PRODUCTION;
        }
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }

    public function paymentRequest($data)
    {
        \Cashfree\Cashfree::$XClientId = $this->app_id;
        \Cashfree\Cashfree::$XClientSecret = $this->secret_key;
        \Cashfree\Cashfree::$XEnvironment = $this->payment_env;
        $cashfree = new \Cashfree\Cashfree();
        // dd($data);
        $x_api_version = "2023-08-01";
        $create_orders_request = new \Cashfree\Model\CreateOrderRequest();
        $create_orders_request->setOrderAmount($data['amount']);
        $create_orders_request->setOrderCurrency($this->currency);
        $customer_details = new \Cashfree\Model\CustomerDetails();
        $customer_details->setCustomerId($data['customer_id']);
        $customer_details->setCustomerName($data['name']);
        $customer_details->setCustomerPhone($data['phone']);
        $customer_details->setCustomerEmail($data['email']);
        $create_orders_request->setCustomerDetails($customer_details);

        try {
             $result = $cashfree->PGCreateOrder($x_api_version, $create_orders_request);
            return response(['data' => $result[0]], 200);
        } catch (Exception $e) {
            $code = 'Exception when calling PGCreateOrder: '.$e->getMessage();
            return response($e, $code);
        }
    }

    public function paymentResponce($request)
    {
        $error = "Payment Failed";
        \Cashfree\Cashfree::$XClientId = $this->app_id;
        \Cashfree\Cashfree::$XClientSecret = $this->secret_key;
        \Cashfree\Cashfree::$XEnvironment = $this->payment_env;
        $cashfree = new \Cashfree\Cashfree();
        $x_api_version = "2023-08-01";

        try {
            $response = $cashfree->PGOrderFetchPayments($x_api_version, $request['order_id']);
            $response = $response[0][0];
        } catch (Exception $e) {
            echo 'Exception when calling PGOrderFetchPayments: ', $e->getMessage(), PHP_EOL;
        }

        if ($response['payment_status'] === 'SUCCESS') {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'payment_data' => [
                    'order_id' => $response['order_id'],
                    'payment_id' => $response['cf_payment_id'],
                    'amount' => $response['payment_amount'],
                ]
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
