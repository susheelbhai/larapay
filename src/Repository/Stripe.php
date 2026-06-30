<?php

namespace Susheelbhai\Larapay\Repository;

class Stripe
{
    public $stripe_publishable_key;
    public $stripe_secret_key;
    public $currency;

    public function __construct()
    {
        $this->stripe_publishable_key = config('larapay.stripe.stripe_publishable_key');
        $this->stripe_secret_key = config('larapay.stripe.stripe_secret_key');
        $this->currency = strtolower(config('larapay.settings.currency', 'inr'));
    }

    public function paymentRequest($data)
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripe_secret_key);

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int) ($data['amount'] * 100), // amount in smallest currency unit (paise/cents)
                'currency' => $this->currency,
                'metadata' => [
                    'customer_name' => $data['name'] ?? '',
                    'customer_email' => $data['email'] ?? '',
                    'customer_phone' => $data['phone'] ?? '',
                ],
            ]);

            $orderData = [
                'order_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'redirect_url' => route('callback_url') . "?redirect_url=" . ($data['redirect_url'] ?? '') . "&order_id=" . $paymentIntent->id,
            ];

            return response(['data' => $orderData], 200);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    public function paymentResponce($request)
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripe_secret_key);

            $paymentIntent = \Stripe\PaymentIntent::retrieve($request['order_id']);

            if ($paymentIntent->status === 'succeeded') {
                $data = [
                    'success' => true,
                    'redirect_url' => $request->redirect_url ?? '',
                    'msg' => 'payment successful',
                    'payment_data' => [
                        'order_id' => $paymentIntent->id,
                        'payment_id' => $paymentIntent->latest_charge ?? $paymentIntent->id,
                        'amount' => $paymentIntent->amount / 100,
                    ]
                ];
            } else {
                $data = [
                    'success' => false,
                    'redirect_url' => $request->redirect_url ?? '',
                    'msg' => 'Payment not completed. Status: ' . $paymentIntent->status,
                    'payment_data' => [
                        'order_id' => $paymentIntent->id,
                        'payment_id' => null,
                        'amount' => $paymentIntent->amount / 100,
                    ]
                ];
            }
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'redirect_url' => $request->redirect_url ?? '',
                'msg' => 'Stripe Error: ' . $e->getMessage(),
                'payment_data' => [
                    'order_id' => $request['order_id'] ?? null,
                    'payment_id' => null,
                    'amount' => 0,
                ]
            ];
        }

        return $data;
    }

    public function makeRefund($payment, $amount, $speed = "normal", $notes = null)
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripe_secret_key);

            $paymentIntent = \Stripe\PaymentIntent::retrieve($payment['order_id']);
            $chargeId = $paymentIntent->latest_charge;

            if (!$chargeId) {
                return [
                    'refund_id' => null,
                    'status' => 'failed',
                    'message' => 'No charge found for this payment.',
                ];
            }

            $refundParams = ['charge' => $chargeId];

            if ($amount !== 'full') {
                $refundParams['amount'] = (int) ($amount * 100);
            }

            $refund = \Stripe\Refund::create($refundParams);

            return [
                'refund_id' => $refund->id,
                'status' => $refund->status === 'succeeded' ? 'processed' : $refund->status,
                'speed_processed' => 'normal',
                'speed_requested' => $speed,
                'amount_refunded' => $refund->amount / 100,
            ];
        } catch (\Exception $e) {
            return [
                'refund_id' => null,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }
}
