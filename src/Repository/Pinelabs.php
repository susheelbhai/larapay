<?php

namespace Susheelbhai\Larapay\Repository;

use Susheelbhai\Larapay\Models\Payment;


class Pinelabs
{

    public function paymentRequest($input)
    {
        global $formdata;
        global $hash;

        $secret_key = config('larapay.pinelabs.secret_key');

        function Hex2String($hex)
        {
            $string = '';

            for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
                $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
            }

            return $string;
        }

        $secret_key = Hex2String($secret_key);

        $ppc_UniqueMerchantTxnID = uniqid();

        $ppc_MerchantReturnURL = route('callback_url');

        $ppc_DIA_SECRET_TYPE = "SHA256";

        $formdata = array(
            'ppc_MerchantID'            =>    config('larapay.pinelabs.ppc_MerchantID'),
            'ppc_Amount'                =>    $input['amount'] * 100,
            'ppc_MerchantAccessCode'    =>    config('larapay.pinelabs.ppc_MerchantAccessCode'),
            'ppc_UniqueMerchantTxnID'    =>    $ppc_UniqueMerchantTxnID,
            'ppc_NavigationMode'        =>    '2',
            'ppc_TransactionType'        =>    '1',
            'ppc_LPC_SEQ'                =>    '1',
            'ppc_MerchantReturnURL'        =>    $ppc_MerchantReturnURL,
            'ppc_Product_Code'            =>    "djdgjh",
            'ppc_PayModeOnLandingPage'    =>    config('larapay.pinelabs.ppc_PayModeOnLandingPage'),
            'ppc_CustomerEmail'         =>    $input['email'],
            'ppc_CustomerMobile'         =>     $input['phone'],

            //You can use custom values for the following key parameters:
            'gateway' => 'pinelabs',

            'ppc_MerchantProductInfo'     =>     'Test_MerchantProductInfo',
            'ppc_CustomerFirstName'     =>     'Test_CustomerFirstName',
            'ppc_CustomerLastName'         =>     'Test_CustomerLastName',
            'ppc_CustomerAddress1'         =>     'Test_CustomerAddress1',
            'ppc_CustomerAddress2'         =>     'Test_CustomerAddress2',
            'ppc_CustomerCity'             =>     'Noida',
            'ppc_CustomerState'         =>     'Uttar Pradesh',
            'ppc_CustomerCountry'         =>     'India',
            'ppc_CustomerAddressPIN'     =>     '201309',
            'ppc_ShippingFirstName'     =>     'Test_ShippingFirstName',
            'ppc_ShippingLastName'         =>     'Test_ShippingLastName',
            'ppc_ShippingAddress1'         =>     'Test_ShippingAddress1',
            'ppc_ShippingAddress2'         =>     'Test_ShippingAddress2',
            'ppc_ShippingCity'             =>     'Noida',
            'ppc_ShippingState'         =>     'Uttar Pradesh',
            'ppc_ShippingCountry'         =>     'India',
            'ppc_ShippingZipCode'         =>     '201309',
            'ppc_ShippingPhoneNumber'     =>     '1234567890'
        );

        //sort formdata according to key value
        ksort($formdata);

        $strFormdata = "";

        // convert formdata key and value to a single string variable
        foreach ($formdata as $key => $val) {
            $strFormdata .= $key . "=" . $val . "&";
        }

        // trim last character from string
        $strFormdata = substr($strFormdata, 0, -1);

        $hash = strtoupper(hash_hmac('sha256', $strFormdata, $secret_key));

        $response = array(
            'order_id' => 8946,
            'formdata' => $formdata,
            'ppc_DIA_SECRET_TYPE' => $ppc_DIA_SECRET_TYPE,
            'hash' => $hash,
        );
        return $response;
    }

    public function paymentResponce($request)
    {
        $success = true;

        $error = "Payment Failed";

        if ($request['ppc_TxnResponseMessage'] != 'SUCCESS') {
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
                ]
            ];
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => $error,
                'success' => false
            ];
        }
        return $data;
    }
}
