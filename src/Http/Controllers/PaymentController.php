<?php

namespace Susheelbhai\Larapay\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Susheelbhai\Larapay\Models\Payment;
use Susheelbhai\Larapay\Repository\COD;
use Susheelbhai\Larapay\Repository\PayU;
use Susheelbhai\Larapay\Mail\WebhookEmail;
use Susheelbhai\Larapay\Repository\Stripe;
use App\Http\Controllers\LarapayController;
use Susheelbhai\Larapay\Models\PaymentTemp;
use Susheelbhai\Larapay\Repository\Phonepe;
use Susheelbhai\Larapay\Repository\Cashfree;
use Susheelbhai\Larapay\Repository\CCAvanue;
use Susheelbhai\Larapay\Repository\Pinelabs;
use Susheelbhai\Larapay\Repository\Razorpay;
use Susheelbhai\Larapay\Models\PaymentGateway;

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

        $obj = new LarapayController();
        $gateway = $request['gateway'] ?? $this->gateway;
        $obj->preOrderMethod($request, $gateway);
        $input = $request->all();
        $input['gateway'] = $gateway;
        // please dont change the order of above line of codes because some data in updated in other class

        
        switch ($gateway) {
            case 1:
                $order = new COD();
                $view = 'larapay::gateways.cod.confirmation';
                break;
            case 2:
                $order = new Razorpay();
                $view = 'larapay::gateways.razorpay.pay';
                break;
            case 3:
                $order = new Pinelabs();
                $view = 'larapay::gateways.pinelabs.purchase_redirect';
                break;
            case 4:
                $order = new Stripe();
                $view = 'larapay::gateways.stripe.card_payment';
                break;
            case 5:
                $order = new CCAvanue();
                $view = 'larapay::gateways.ccavanue.purchase_redirect';
                break;
            case 6:
                $order = new Phonepe();
                $view = 'larapay::gateways.phonepe.purchase_redirect';
                break;
            case 7:
                $order = new Cashfree();
                $view = 'larapay::gateways.cashfree.pay';
                break;
            case 8:
                $order = new PayU();
                $view = 'larapay::gateways.payu.pay';
                break;

            default:
                return view('larapay::errors.error');
                break;
        }

        $response = $order->paymentRequest($input);
        if ($response->status() != 200) {
            return view('larapay::errors.error', compact('response'));
        }
        $orderData = $response->getOriginalContent()['data'];
        $obj->postOrderMethod($request, $orderData['order_id'], $gateway);
        $input = $request->all();
        return view($view, compact('orderData', 'input'));
    }

    function manualWebhook(Request $request)
    {
        $data = [
            'order_id' => $request['order_id'],
            'payment_id' => $request['payment_id'],
            'event' => $request['event'],
        ];
        return $this->webhookResponse($data);
    }
    function webhook(Request $request, $gateway)
    {
        // to test whether webhook called or nor or to see the response of webhook, you can send an test email with the response data by uncommenting the below code
        // Mail::send("test@wxample.com")->send(new WebhookEmail($request));


        switch ($gateway) {
            case 2:
                $data = [
                    'order_id' => $request['payload']['payment']['entity']['order_id'],
                    'payment_id' => $request['payload']['payment']['entity']['id'],
                    'event' => $request['event'],
                ];
                break;
            case 6:
                $request = json_decode(base64_decode($request['response']));
                if ($request->code == 'PAYMENT_SUCCESS') {
                    $event_type = 'payment.captured';
                }

                $data = [
                    'order_id' => $request->data->merchantTransactionId,
                    'payment_id' => $request->data->transactionId,
                    'event' => $event_type,
                ];

                break;
            case 7:
                if ($request['type'] == 'PAYMENT_SUCCESS_WEBHOOK') {
                    $event_type = 'payment.captured';
                }
                if ($request['type'] == 'PAYMENT_FAILED_WEBHOOK') {
                    $event_type = 'payment.failed';
                }
                $data = [
                    'order_id' => $request['data']['order']['order_id'],
                    'payment_id' => $request['data']['payment']['cf_payment_id'],
                    'event' => $event_type,
                ];
                break;

            default:
                $data = [
                    'order_id' => null,
                    'payment_id' => null,
                    'event' => null,
                ];
                break;
        }

        return $this->webhookResponse($data);
    }

    public function paymentResponce(Request $request)
    {

        $payment_temp = PaymentTemp::whereOrderId($request['order_id'])->first();
        // return $payment_temp;
        switch ($payment_temp['payment_gateway_id']) {
            case 1:
                $response = new COD();
                break;
            case 2:
                $response = new Razorpay();
                break;
            case 3:
                $response = new Pinelabs();
                break;
            case 4:
                $response = new Stripe();
                break;
            case 5:
                $response = new CCAvanue();
                break;
            case 6:
                $response = new Phonepe();
                break;
            case 7:
                $response = new Cashfree();
                break;
            case 8:
                $response = new PayU();
                break;

            default:
                # code...
                break;
        }
        $data = $response->paymentResponce($request);
        if (config('larapay.settings.unable_payment_response') == 1) {
            $payment = new LarapayController();
            if ($data['success'] == true) {
                $payment->paymentSuccessful($request->all(), $data, $payment_temp);
            } else {
                $payment->paymentFailed($request->all(), $data, $payment_temp);
            }
        }


        $payment_data = [
            'order_id' => $data['payment_data']['order_id'],
            'payment_id' => $data['payment_data']['payment_id'],
            'redirect_url' => $data['redirect_url'],
        ];
        // dd($data);
        return view('larapay::payment.response', compact('request', 'payment_data'));
    }

    public function webhookResponse($request)
    {
        $order_id = $request['order_id'];
        $payment_temp = PaymentTemp::whereOrderId($order_id)->first();
        $payment_count = Payment::whereOrderId($order_id)->count();
        $data = [
            'success' =>  true,
            'redirect_url' => $request->redirect_url ?? '',
            'msg' => 'payment successful',
            'payment_data' => [
                'order_id' => $request['order_id'],
                'payment_id' => $request['payment_id'],
            ]
        ];
        $payment = new LarapayController();
        if ($request['event'] == 'payment.captured') {
            if (isset($payment_temp) && $payment_count == 0) {
                $payment->paymentSuccessful($request, $data, $payment_temp);
            }
            return true;
        }
        if ($request['event'] == 'payment.failed') {
            if (isset($payment_temp)) {
                $payment->paymentFailed($request, $data, $payment_temp);
            }
            return true;
        }
    }

    public function checkPayment(Request $request)
    {
        $payment_count = Payment::whereOrderId($request['order_id'])->count();
        $failed_count = PaymentTemp::whereOrderId($request['order_id'])->where('payment_status', 0)->count();
        if ($payment_count > 0) {
            return 'success';
        } elseif ($failed_count == 1) {
            return 'failed';
        } else {
            return false;
        }
    }
    public function paymentSuccess(Request $request)
    {
        return view('larapay::payment.success')->with('data', $request);
    }
    public function paymentFailed(Request $request)
    {
        return view('larapay::payment.failed')->with('data', $request);
    }
    public function refundPayment($id, $amount, $speed="normal")
    {
        $payment_data = Payment::whereId($id)->first();
        switch ($payment_data['payment_gateway_id']) {
            case 1:
                $repo = new COD();
                break;
            case 2:
                $repo = new Razorpay();
                break;
            case 3:
                $repo = new Pinelabs();
                break;
            case 4:
                $repo = new Stripe();
                break;
            case 5:
                $repo = new CCAvanue();
                break;
            case 6:
                $repo = new Phonepe();
                break;
            case 7:
                $repo = new Cashfree();
                break;
            case 8:
                $repo = new PayU();
                break;

            default:
                # code...
                break;
        }
        $response = $repo->makeRefund($payment_data, $amount, $speed);
        $obj = new LarapayController();
        $obj->paymentRefunded($payment_data, $response);
        return $response;
    }
}
