<?php

namespace Susheelbhai\Larapay\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Susheelbhai\Larapay\Repository\COD;
use Susheelbhai\Larapay\Repository\Stripe;
use App\Http\Controllers\LarapayController;
use Susheelbhai\Larapay\Models\PaymentTemp;
use Susheelbhai\Larapay\Repository\Phonepe;
use Susheelbhai\Larapay\Repository\CCAvanue;
use Susheelbhai\Larapay\Repository\Pinelabs;
use Susheelbhai\Larapay\Repository\Razorpay;
use Susheelbhai\Larapay\Models\PaymentGateway;
use Susheelbhai\Larapay\Repository\Stripe as StripeRepository;

class PaymentController extends Controller
{
    public $gateway;
    public function __construct()
    {
        $this->gateway = config('payment.gateway_id');
    }

    public function form()
    {
        if (config('app.env') != 'local') {
            abort(404);
        }
        $gateways = PaymentGateway::whereIsActive(1)->get();
        return view('larapay::payment.form', compact('gateways'));
    }

    public function index(Request $request)
    {
        $gateway = $request['gateway'] ?? $this->gateway;
        $input = $request->all();
        if ($gateway == 1) {
            $order = new COD();
            $view = 'larapay::gateways.cod.confirmation';
        }
        if ($gateway == 2) {
            $order = new Razorpay();
            $view = 'larapay::payment.index';
        }
        if ($gateway == 3) {
            $order = new Pinelabs();
            $view = 'larapay::gateways.pinelabs.purchase_redirect';
        }
        if ($gateway == 4) {
            $order = new Stripe();
            $view = 'larapay::gateways.stripe.card_payment';
        }
        if ($gateway == 5) {
            $order = new CCAvanue();
            $view = 'larapay::gateways.ccavanue.purchase_redirect';
        }
        if ($gateway == 6) {
            $order = new Phonepe();
            $view = 'larapay::gateways.phonepe.purchase_redirect';
        }
        $obj = new LarapayController();
        $response = $order->paymentRequest($input);
        if ($response->status() != 200) {
            return view('larapay::errors.error', compact('response'));
        }
        $orderData = $response->getOriginalContent()['data'];
        $obj->updateTempTable($orderData['order_id'], $input);
        return view($view, compact('orderData', 'input'));
    }

    function webhook(Request $request, $gateway)
    {
        if (config('app.env') != 'local') {
            return $this->webhookResponse($request, $gateway);
        }
    }

    public function paymentResponce(Request $request)
    {
        $gateway = $request['gateway'] ?? '';
        if (isset($request->ppc_MerchantID)) {
            $response = new Pinelabs();
        }
        if ($gateway == 1) {
            $response = new COD();
        }
        if ($gateway == 2) {
            $response = new Razorpay();
        }
        if ($gateway == 3) {
            $response = new Pinelabs();
        }
        if (isset($request->stripeToken)) {
            $response = new StripeRepository();
        }

        if (isset($request->encResp)) {
            $response = new CCAvanue();
        }
        if (isset($request['merchantId']) && $request['merchantId'] == config('larapay.phonepe.merchant_id')) {
            $response = new Phonepe();
        }
        $payment_temp = PaymentTemp::whereOrderId($request['razorpay_order_id'])->first();
        $data = $response->paymentResponce($request);
        $payment = new LarapayController();
        if ($data['success'] == true) {
            $payment->paymentSuccessful($request->all(), $data, $payment_temp);
        } else {
            $payment->paymentFailed($request->all(), $data, $payment_temp);
        }

        return view('larapay::payment.response', compact('request', 'data'));
    }

    public function webhookResponse($request, $gateway)
    {
        if ($gateway == 'razorpay') {
            $response = new Razorpay();
        }
        return $response->webhook($request);
    }
}
