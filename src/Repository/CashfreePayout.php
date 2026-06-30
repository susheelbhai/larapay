<?php

namespace Susheelbhai\Larapay\Repository;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CashfreePayout
{
    public $client_id;

    public $client_secret;

    public $webhook_secret;

    public $base_url;

    public function __construct()
    {
        $this->client_id = config('larapay.payout.cashfree.client_id');
        $this->client_secret = config('larapay.payout.cashfree.client_secret');
        $this->webhook_secret = config('larapay.payout.cashfree.webhook_secret');
        $this->base_url = 'https://payout-gamma.cashfree.com/payout/v1';

        if (config('larapay.settings.payment_env') == 'production') {
            $this->base_url = 'https://payout-api.cashfree.com/payout/v1';
        }
    }

    /**
     * Register a beneficiary at Cashfree Payouts.
     *
     * @param  array  $data  [name, email, phone, account_number, ifsc, vpa, transfer_mode, bene_id]
     */
    public function createBeneficiary(array $data): array
    {
        try {
            $payload = [
                'beneId' => $data['bene_id'] ?? uniqid('bene_'),
                'name' => $data['name'],
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'transferMode' => $data['transfer_mode'] ?? 'banktransfer',
            ];

            if (isset($data['vpa']) && ! empty($data['vpa'])) {
                $payload['vpa'] = $data['vpa'];
            } else {
                $payload['bankAccount'] = $data['account_number'];
                $payload['ifsc'] = $data['ifsc'];
            }

            $response = Http::withHeaders([
                'X-Client-Id' => $this->client_id,
                'X-Client-Secret' => $this->client_secret,
                'Content-Type' => 'application/json',
            ])->post($this->base_url.'/addBeneficiary', $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['status']) && $body['status'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'gateway_beneficiary_id' => $payload['beneId'],
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'error' => $body['message'] ?? 'Failed to create beneficiary',
                'gateway_error' => $body,
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'error' => $th->getMessage(),
                'gateway_error' => null,
            ];
        }
    }

    /**
     * Initiate a payout transfer to an existing beneficiary.
     *
     * @param  array  $data  [transfer_id, amount, transfer_mode, remarks]
     * @param  string  $gatewayBeneficiaryId  The Cashfree beneId
     */
    public function createPayout(array $data, string $gatewayBeneficiaryId): array
    {
        try {
            $payload = [
                'beneId' => $gatewayBeneficiaryId,
                'transferId' => $data['transfer_id'] ?? uniqid('txn_'),
                'amount' => $data['amount'],
                'transferMode' => $data['transfer_mode'] ?? $data['mode'] ?? 'banktransfer',
            ];

            if (isset($data['remarks'])) {
                $payload['remarks'] = $data['remarks'];
            }

            $response = Http::withHeaders([
                'X-Client-Id' => $this->client_id,
                'X-Client-Secret' => $this->client_secret,
                'Content-Type' => 'application/json',
            ])->post($this->base_url.'/requestTransfer', $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['status']) && $body['status'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'gateway_payout_id' => $body['data']['referenceId'] ?? $payload['transferId'],
                    'transfer_id' => $payload['transferId'],
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'error' => $body['message'] ?? 'Failed to create payout',
                'gateway_error' => $body,
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'error' => $th->getMessage(),
                'gateway_error' => null,
            ];
        }
    }

    /**
     * Create beneficiary and payout in two sequential calls (transparent to caller).
     *
     * @param  array  $data  [name, email, phone, account_number, ifsc, vpa, transfer_mode, amount, transfer_id, remarks]
     */
    public function createPayoutInline(array $data): array
    {
        // Step 1: Create beneficiary
        $beneficiaryResult = $this->createBeneficiary($data);

        if (! $beneficiaryResult['success']) {
            return $beneficiaryResult;
        }

        // Step 2: Create payout using the newly registered beneficiary
        $payoutResult = $this->createPayout($data, $beneficiaryResult['gateway_beneficiary_id']);

        if (! $payoutResult['success']) {
            return $payoutResult;
        }

        return [
            'success' => true,
            'gateway_beneficiary_id' => $beneficiaryResult['gateway_beneficiary_id'],
            'gateway_payout_id' => $payoutResult['gateway_payout_id'],
            'transfer_id' => $payoutResult['transfer_id'],
            'data' => $payoutResult['data'],
        ];
    }

    /**
     * Fetch the status of a payout transfer from Cashfree.
     *
     * @param  string  $gatewayPayoutId  The referenceId or transferId
     */
    public function fetchPayoutStatus(string $gatewayPayoutId): array
    {
        try {
            $response = Http::withHeaders([
                'X-Client-Id' => $this->client_id,
                'X-Client-Secret' => $this->client_secret,
            ])->get($this->base_url.'/getTransferStatus', [
                'referenceId' => $gatewayPayoutId,
            ]);

            $body = $response->json();

            if ($response->successful() && isset($body['status']) && $body['status'] === 'SUCCESS') {
                $transferStatus = $body['data']['transfer']['status'] ?? null;

                $statusMap = [
                    'SUCCESS' => 'processed',
                    'FAILED' => 'failed',
                    'REVERSED' => 'reversed',
                    'PENDING' => 'processing',
                ];

                return [
                    'success' => true,
                    'status' => $statusMap[$transferStatus] ?? 'processing',
                    'utr' => $body['data']['transfer']['utr'] ?? null,
                    'reason' => $body['data']['transfer']['reason'] ?? null,
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'error' => $body['message'] ?? 'Failed to fetch payout status',
                'gateway_error' => $body,
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'error' => $th->getMessage(),
                'gateway_error' => null,
            ];
        }
    }

    /**
     * Verify webhook signature from Cashfree.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('x-webhook-signature');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $timestamp = $request->header('x-webhook-timestamp', '');

        $expectedSignature = hash_hmac('sha256', $timestamp.$payload, $this->webhook_secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse webhook payload and map Cashfree events to normalized status.
     *
     * Events: TRANSFER_SUCCESS, TRANSFER_FAILED, TRANSFER_REVERSED
     *
     * @return array [gateway_payout_id, event_type, reason]
     */
    public function parseWebhookPayload(Request $request): array
    {
        $payload = $request->all();
        $event = $payload['event'] ?? '';
        $transferData = $payload['data'] ?? $payload['transfer'] ?? [];

        $eventMap = [
            'TRANSFER_SUCCESS' => 'processed',
            'TRANSFER_FAILED' => 'failed',
            'TRANSFER_REVERSED' => 'reversed',
        ];

        return [
            'gateway_payout_id' => $transferData['referenceId'] ?? $transferData['transferId'] ?? null,
            'event_type' => $eventMap[$event] ?? $event,
            'reason' => $transferData['reason'] ?? null,
            'utr' => $transferData['utr'] ?? null,
        ];
    }
}
