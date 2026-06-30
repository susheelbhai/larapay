<?php

namespace Susheelbhai\Larapay\Repository;

use Susheelbhai\Larapay\Models\Payment;


class Pinelabs
{

    public function paymentRequest($input)
    {
        $secret_key = config('larapay.pinelabs.secret_key');

        $secret_key = self::hex2String($secret_key);

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
            'order_id' => $ppc_UniqueMerchantTxnID,
            'formdata' => $formdata,
            'ppc_DIA_SECRET_TYPE' => $ppc_DIA_SECRET_TYPE,
            'hash' => $hash,
        );
        return response(['data'=>$response], 200);
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
                    'order_id' => $request['ppc_UniqueMerchantTxnID'] ?? $request['order_id'],
                    'payment_id' => $request['ppc_TxnResponseCode'] ?? $request['payment_id'],
                    'amount' => $request['ppc_Amount'] ? $request['ppc_Amount'] / 100 : $request['amount'],
                ]
            ];
        } else {
            $data = [
                'redirect_url' => $request->redirect_url,
                'msg' => $error,
                'success' => false,
                'payment_data' => [
                    'order_id' => $request['ppc_UniqueMerchantTxnID'] ?? $request['order_id'] ?? null,
                    'payment_id' => null,
                    'amount' => $request['ppc_Amount'] ? $request['ppc_Amount'] / 100 : ($request['amount'] ?? 0),
                ]
            ];
        }
        return $data;
    }

    public function makeRefund($payment, $amount, $speed = "normal", $notes = null)
    {
        return [
            'refund_id' => null,
            'status' => 'failed',
            'message' => 'PineLabs does not support refunds via API in this integration.',
        ];
    }

    /**
     * Convert hex string to binary string.
     */
    private static function hex2String(string $hex): string
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $string;
    }
}
