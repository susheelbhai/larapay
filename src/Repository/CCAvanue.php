<?php

namespace Susheelbhai\Larapay\Repository;

use Susheelbhai\Larapay\Models\Payment;


class CCAvanue
{

    public $merchant_id;
    public $access_code;
    public $working_key;
    public $payment_env;
    public $language;
    public $currency;
    public function __construct()
    {
        $this->merchant_id = config('larapay.ccavanue.merchant_id');
        $this->access_code = config('larapay.ccavanue.access_code');
        $this->working_key = config('larapay.ccavanue.working_key');
        $this->payment_env = config('larapay.settings.payment_env');
        $this->language = config('larapay.settings.language');
        $this->currency = config('larapay.settings.currency');
    }
    public function paymentRequest($input)
    {

        $merchant_data = '';
        $order_id =rand(11111111, 999999999);
        foreach ($input as $key => $value) {
            $merchant_data .= $key . '=' . urlencode($value) . '&';
        }

        $formdata = array(
            'merchant_id'   =>    $this->merchant_id,
            'order_id'      =>    $order_id,
            'amount'        =>    $input['amount'],
            'currency'      =>    $this->currency,
            'redirect_url'  =>    route('callback_url'),
            'cancel_url'    =>    route('callback_url'),
            'language'      =>    $this->language,
            
            //You can use custom values for the following key parameters:
            'billing_name'      =>     $input['name'],
            'billing_address'   =>     '',
            'billing_city'      =>     '',
            'billing_state'     =>     '',
            'billing_zip'       =>     '',
            'billing_country'   =>     '',
            'billing_tel'       =>     $input['phone'],
            'billing_email'     =>     $input['email'],
            'delivery_name'     =>     '201309',
            'delivery_address'  =>     '',
            'delivery_city'     =>     '',
            'delivery_state'    =>     '',
            'delivery_zip'      =>     '',
            'delivery_country'  =>     '',
            'delivery_tel'      =>     '',
            'merchant_param1'   =>     '5',
            'merchant_param2'   =>     '',
            'merchant_param3'   =>     '',
            'merchant_param4'   =>     '',
            'merchant_param5'   =>     '',
            'payment_option'    =>     'OPTNBK'
        );
        foreach ($formdata as $key => $value) {
            $merchant_data .= $key . '=' . urlencode($value) . '&';
        }

        $encrypted_data = encrypt($merchant_data, $this->working_key); // Method for encrypting the data.
        if ($this->payment_env == 'local') {
            $action_url = 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
        } elseif ($this->payment_env == 'production') {
            $action_url = 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
        }

        $response = array(
            'encrypted_data' => $encrypted_data,
            'merchant_data' => $merchant_data,
            'access_code' => $this->access_code,
            'action_url' => $action_url,
            'order_id' => $order_id,
        );
        return response(['data'=>$response], 200);
    }

    public function paymentResponce($request)
    {

        $encResponse = $_POST["encResp"];            //This is the response sent by the CCAvenue Server
        $rcvdString = decrypt($encResponse, $this->working_key);        //Crypto Decryption used as per the specified working key.
        $decryptValues = explode('&', $rcvdString);

        $response = [];
        foreach ($decryptValues as $key => $value) {
           $x = explode('=',$value);
            $response+= [$x[0]=>$x[1]];
        }

        $success = true;

        $error = "Payment Failed";
        if ($response['status_message'] != 'Y') {
            $success = false;
        }
        $request->redirect_url = route('pay');
        if ($success === true) {
            $data = [
                'success' =>  true,
                'redirect_url' => $request->redirect_url,
                'msg' => 'payment successful',
                'payment_data' => [
                    'order_id' => $request['razorpay_order_id'],
                    'payment_id' => $request['razorpay_payment_id'],
                    'amount' => $request['amount'],
                ]
            ];
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => $error,
                'success' => false
            ];
        }
        // return $response; 
        return $data;
    }
}

function encrypt($plainText, $key)
{
    $secretKey = hextobin(md5($key));
    $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $encryptedText = openssl_encrypt($plainText, "AES-128-CBC", $secretKey, OPENSSL_RAW_DATA, $initVector);
    $encryptedText = bin2hex($encryptedText);
    return $encryptedText;
}

function decrypt($encryptedText, $key)
{
    $secretKey         = hextobin(md5($key));
    $initVector         =  pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
    $encryptedText      = hextobin($encryptedText);
    $decryptedText         =  openssl_decrypt($encryptedText, "AES-128-CBC", $secretKey, OPENSSL_RAW_DATA, $initVector);
    return $decryptedText;
}
// *********** Padding Function *********************
function pkcs5_pad($plainText, $blockSize)
{
    $pad = $blockSize - (strlen($plainText) % $blockSize);
    return $plainText . str_repeat(chr($pad), $pad);
}

// ********** Hexadecimal to Binary function for php 4.0 version ********
function hextobin($hexString)
{
    $length = strlen($hexString);
    $binString = "";
    $count = 0;
    while ($count < $length) {
        $subString = substr($hexString, $count, 2);
        $packedString = pack("H*", $subString);
        if ($count == 0) {
            $binString = $packedString;
        } else {
            $binString .= $packedString;
        }

        $count += 2;
    }
    return $binString;
}
