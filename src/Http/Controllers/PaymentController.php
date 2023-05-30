<?php

namespace Susheelbhai\Larapay\Http\Controllers;

use Razorpay\Api\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Susheelbhai\Larapay\Models\Payment;
use Susheelbhai\Larapay\Repository\Pinelabs;
use Susheelbhai\Larapay\Repository\Razorpay;
use Susheelbhai\Larapay\Models\PaymentGateway;
use Razorpay\Api\Errors\SignatureVerificationError;

class PaymentController extends Controller
{
    public $gateway;
    public function __construct()
    {
        $this->gateway = PaymentGateway::whereIsActive(1)->first()->id;
    }

    public function form()
    {
        $gateways = PaymentGateway::whereIsActive(1)->get();
        return view('larapay::payment.form', compact('gateways'));
    }
    public function index(Request $request)
    {
        $input = $request->all();
        $extra_input_array = array(
            'name' => 'Susheel Kumar Singh',
            'email' => 'susheelkrsingh306@gmail.com',
            'phone' => '9090653356',
            'redirect_url' => $input['redirect_url'],
            'gateway' => $input['gateway'],
        );

        if ($request->gateway == 1) {
            return $input;
        }
        if ($request->gateway == 2) {
            $order = new Razorpay();
            $razorpayOrder = $order->paymentRequest($input);
            return view('larapay::payment.index', compact('razorpayOrder', 'input', 'extra_input_array'));
        }
        if ($request->gateway == 3) {
            $order = new Pinelabs();
            return  $razorpayOrder = $order->paymentRequest($input);
            return view('larapay::payment.index', compact('razorpayOrder', 'input', 'extra_input_array'));
        }
        if ($request->gateway == 4) {
            $order = new Pinelabs();
            $orderData = $order->paymentRequest($input);
            return view('larapay::gateways.pinelabs.purchase_redirect', compact('orderData', 'input', 'extra_input_array'));
        }
    }

    public function paymentResponce(Request $request)
    {
        // return $request;
        if (isset($request->ppc_MerchantID)) {
            $response = new Pinelabs();
              $data = $response->paymentResponce($request);
            return view('larapay::payment.response', compact('request', 'data'));
        }
        if ($request->gateway == 1) {
            return $request;
        }
        if ($request->gateway == 2) {
            $response = new Razorpay();
            $data = $response->paymentResponce($request);
            return view('larapay::payment.response', compact('request', 'data'));
        }
        if ($request->gateway == 3) {
            $response = new Pinelabs();
            return  $razorpayOrder = $response->paymentResponce($request);
            return view('larapay::payment.response', compact('request', 'data'));
        }
        if ($request->gateway == 4) {
            $order = new Pinelabs();
            $orderData = $order->paymentRequest($request);
            return view('larapay::gateways.pinelabs.purchase_redirect', compact('orderData', 'input', 'extra_input_array'));
        }
    }
}
