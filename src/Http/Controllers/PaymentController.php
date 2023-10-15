<?php

namespace Susheelbhai\Larapay\Http\Controllers;

use Stripe\Charge;
use Stripe\Stripe;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LarapayController;
use Susheelbhai\Larapay\Repository\COD;
use Susheelbhai\Larapay\Repository\CCAvanue;
use Susheelbhai\Larapay\Repository\Pinelabs;
use Susheelbhai\Larapay\Repository\Razorpay;
use Susheelbhai\Larapay\Models\PaymentGateway;
use Susheelbhai\Larapay\Repository\Stripe as StripeRepository;

class PaymentController extends Controller
{
    
    public function __construct()
    {
       
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
            'order_id' => $input['order_id'],
            'name' => 'Susheel Kumar Singh',
            'email' => 'susheelkrsingh306@gmail.com',
            'phone' => '9090653356',
            'redirect_url' => $input['redirect_url'],
            'gateway' => $input['gateway'],
        );

        if ($request->gateway == 1) {
            return view('larapay::gateways.cod.confirmation', compact('input'));
        }
        if ($request->gateway == 2) {
            $order = new Razorpay();
            $razorpayOrder = $order->paymentRequest($input);
            return view('larapay::payment.index', compact('razorpayOrder', 'input', 'extra_input_array'));
        }
        if ($request->gateway == 3) {
            $order = new Pinelabs();
            $orderData = $order->paymentRequest($input);
            return view('larapay::gateways.pinelabs.purchase_redirect', compact('orderData', 'input', 'extra_input_array'));
        }
        if ($request->gateway == 4) {
            return view('larapay::gateways.stripe.card_payment', compact('input'));
        }
        if ($request->gateway == 5) {
            $order = new CCAvanue();
            $orderData = $order->paymentRequest($input, $extra_input_array);
            return view('larapay::gateways.ccavanue.purchase_redirect', compact('orderData', 'input', 'extra_input_array'));
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
            $response = new COD();
            $data = $response->paymentResponce($request);
            return view('larapay::payment.response', compact('request', 'data'));
        }
        if ($request->gateway == 2) {
            $response = new Razorpay();
            $data = $response->paymentResponce($request);
            if ($data['success']==true) {
                $payment = new LarapayController();
                $payment->paymentSuccessful($request->all());
            };
            return view('larapay::payment.response', compact('request', 'data'));
        }
        if ($request->gateway == 3) {
            $response = new Pinelabs();
            $data = $response->paymentResponce($request);
            return view('larapay::payment.response', compact('request', 'data'));
        }
        if ($request->gateway == 4) {
            Stripe::setApiKey(config('larapay.stripe.stripe_secret_key'));

            Charge::create([
                "amount" => $request->amount * 100,
                "currency" => $request->currency,
                "source" => $request->stripeToken,
                "description" => $request->desscription
            ]);

            $response = new StripeRepository();
            $data = $response->paymentResponce($request);
            return view('larapay::payment.response', compact('request', 'data'));

            return back();
        }
        if (isset($request->encResp)) {
            $response = new CCAvanue();
             $data = $response->paymentResponce($request);
            return view('larapay::payment.response', compact('request', 'data'));
        }
    }
}
