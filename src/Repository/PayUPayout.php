<?php

namespace Susheelbhai\Larapay\Repository;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayUPayout
{
    public string $merchant_id;

    public string $merchant_salt;

    public string $client_id;

    public string $client_secret;

    public string $webhook_secret;

    public string $base_url;

    public function __construct()
    {
        $config = config('larapay.payout.payu');

        $this->merchant_id = $config['merchant_id'] ?? '';
        $this->merchant_salt = $config['merchant_salt'] ?? '';
        $this->client_id = $config['client_id'] ?? '';
        $this->client_secret = $config['client_secret'] ?? '';
        $this->webhook_secret = $config['webhook_secret'] ?? '';

        $this->base_url = 'https://payout.payumoney.com';

        if (config('larapay.settings.payment_env') !== 'production') {
            $this->base_url = 'https://sandbox.payumoney.com/payout';
        }
    }

    /**
     * Get OAuth Bearer token from PayU.
     */
    private function getAuthToken(): ?string
    {
        $response = Http::asForm()->post($this->base_url.'/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        return null;
    }

    /**
     * Make an authenticated API request.
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getAuthToken();

        if (! $token) {
            return [
                'success' => false,
                'error' => 'Failed to obtain auth token from PayU',
                'gateway_error' => null,
            ];
        }

        $request = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ]);

        if (strtoupper($method) === 'GET') {
            $response = $request->get($this->base_url.$endpoint, $data);
        } else {
            $response = $request->post($this->base_url.$endpoint, $data);
        }

        return [
            'http_successful' => $response->successful(),
            'status_code' => $response->status(),
            'body' => $response->json() ?? [],
            'raw' => $response->body(),
        ];
    }

    /**
     * Register a beneficiary at PayU.
     *
     * @param  array  $data  [name, email, phone, account_number, ifsc, vpa, account_type, beneficiary_id]
     */
    public function createBeneficiary(array $data): array
    {
        $payload = [
            'beneficiaryName' => $data['name'] ?? '',
            'beneficiaryEmail' => $data['email'] ?? '',
            'beneficiaryPhone' => $data['phone'] ?? '',
            'beneficiaryId' => $data['beneficiary_id'] ?? uniqid('bene_'),
        ];

        if (($data['account_type'] ?? 'bank_account') === 'vpa') {
            $payload['beneficiaryAccountNumber'] = $data['vpa'] ?? '';
            $payload['beneficiaryIfsc'] = 'UPI';
            $payload['paymentType'] = 'UPI';
        } else {
            $payload['beneficiaryAccountNumber'] = $data['account_number'] ?? '';
            $payload['beneficiaryIfsc'] = $data['ifsc'] ?? '';
            $payload['paymentType'] = 'NEFT';
        }

        $response = $this->makeRequest('POST', '/api/v1/beneficiary/create', $payload);

        if (! $response['http_successful']) {
            return [
                'success' => false,
                'error' => $response['body']['message'] ?? $response['error'] ?? 'Beneficiary creation failed',
                'gateway_error' => $response['raw'] ?? $response['body'] ?? null,
            ];
        }

        $body = $response['body'];

        if (isset($body['status']) && $body['status'] === 0) {
            return [
                'success' => false,
                'error' => $body['message'] ?? 'Beneficiary creation failed',
                'gateway_error' => $body,
            ];
        }

        return [
            'success' => true,
            'gateway_beneficiary_id' => $payload['beneficiaryId'],
            'data' => $body,
        ];
    }

    /**
     * Initiate a payout transfer to an existing beneficiary.
     *
     * @param  array  $data  [amount, purpose, mode, reference_id]
     * @param  string  $gatewayBeneficiaryId  The gateway beneficiary ID
     */
    public function createPayout(array $data, string $gatewayBeneficiaryId): array
    {
        $payload = [
            'beneficiaryId' => $gatewayBeneficiaryId,
            'amount' => $data['amount'] ?? 0,
            'purpose' => $data['purpose'] ?? 'payout',
            'paymentMode' => $this->mapMode($data['mode'] ?? 'IMPS'),
            'referenceId' => $data['reference_id'] ?? uniqid('payout_'),
        ];

        $response = $this->makeRequest('POST', '/api/v1/transfer/initiate', $payload);

        if (! $response['http_successful']) {
            return [
                'success' => false,
                'error' => $response['body']['message'] ?? $response['error'] ?? 'Payout creation failed',
                'gateway_error' => $response['raw'] ?? $response['body'] ?? null,
            ];
        }

        $body = $response['body'];

        if (isset($body['status']) && $body['status'] === 0) {
            return [
                'success' => false,
                'error' => $body['message'] ?? 'Payout creation failed',
                'gateway_error' => $body,
            ];
        }

        return [
            'success' => true,
            'gateway_payout_id' => $body['transferId'] ?? $body['data']['transferId'] ?? $payload['referenceId'],
            'status' => 'processing',
            'data' => $body,
        ];
    }

    /**
     * Create a payout with Auto Beneficiary Registration — single API call.
     * Passes beneficiary details inline in the transfer request.
     *
     * @param  array  $data  [name, email, phone, account_number, ifsc, vpa, account_type, amount, purpose, mode, reference_id]
     */
    public function createPayoutInline(array $data): array
    {
        $payload = [
            'beneficiaryName' => $data['name'] ?? '',
            'beneficiaryEmail' => $data['email'] ?? '',
            'beneficiaryPhone' => $data['phone'] ?? '',
            'amount' => $data['amount'] ?? 0,
            'purpose' => $data['purpose'] ?? 'payout',
            'paymentMode' => $this->mapMode($data['mode'] ?? 'IMPS'),
            'referenceId' => $data['reference_id'] ?? uniqid('payout_'),
        ];

        if (($data['account_type'] ?? 'bank_account') === 'vpa') {
            $payload['beneficiaryAccountNumber'] = $data['vpa'] ?? '';
            $payload['beneficiaryIfsc'] = 'UPI';
            $payload['paymentType'] = 'UPI';
        } else {
            $payload['beneficiaryAccountNumber'] = $data['account_number'] ?? '';
            $payload['beneficiaryIfsc'] = $data['ifsc'] ?? '';
            $payload['paymentType'] = 'NEFT';
        }

        $response = $this->makeRequest('POST', '/api/v1/transfer/initiate/autoBeneficiary', $payload);

        if (! $response['http_successful']) {
            return [
                'success' => false,
                'error' => $response['body']['message'] ?? $response['error'] ?? 'Inline payout creation failed',
                'gateway_error' => $response['raw'] ?? $response['body'] ?? null,
            ];
        }

        $body = $response['body'];

        if (isset($body['status']) && $body['status'] === 0) {
            return [
                'success' => false,
                'error' => $body['message'] ?? 'Inline payout creation failed',
                'gateway_error' => $body,
            ];
        }

        return [
            'success' => true,
            'gateway_payout_id' => $body['transferId'] ?? $body['data']['transferId'] ?? $payload['referenceId'],
            'gateway_beneficiary_id' => $body['beneficiaryId'] ?? $body['data']['beneficiaryId'] ?? null,
            'status' => 'processing',
            'data' => $body,
        ];
    }

    /**
     * Fetch payout transfer status from PayU.
     *
     * @param  string  $gatewayPayoutId  The gateway payout/transfer ID
     */
    public function fetchPayoutStatus(string $gatewayPayoutId): array
    {
        $response = $this->makeRequest('GET', '/api/v1/transfer/status', [
            'transferId' => $gatewayPayoutId,
        ]);

        if (! $response['http_successful']) {
            return [
                'success' => false,
                'error' => $response['body']['message'] ?? $response['error'] ?? 'Failed to fetch payout status',
                'gateway_error' => $response['raw'] ?? $response['body'] ?? null,
            ];
        }

        $body = $response['body'];

        if (isset($body['status']) && $body['status'] === 0) {
            return [
                'success' => false,
                'error' => $body['message'] ?? 'Failed to fetch payout status',
                'gateway_error' => $body,
            ];
        }

        $transferStatus = $body['data']['status'] ?? $body['transferStatus'] ?? $body['status'] ?? 'unknown';

        return [
            'success' => true,
            'gateway_payout_id' => $gatewayPayoutId,
            'status' => $this->mapTransferStatus($transferStatus),
            'utr' => $body['data']['utr'] ?? $body['utr'] ?? null,
            'data' => $body,
        ];
    }

    /**
     * Verify PayU payout webhook signature using HMAC-SHA256.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-PayU-Signature')
            ?? $request->header('x-payu-signature')
            ?? '';

        if (empty($signature) || empty($this->webhook_secret)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse PayU payout webhook payload and map event types to standard statuses.
     *
     * @return array ['gateway_payout_id' => ..., 'event_type' => ..., 'reason' => ...]
     */
    public function parseWebhookPayload(Request $request): array
    {
        $payload = $request->all();

        $eventType = $payload['event'] ?? $payload['eventType'] ?? $payload['status'] ?? '';
        $transferId = $payload['transferId']
            ?? $payload['data']['transferId']
            ?? $payload['payoutId']
            ?? $payload['data']['payoutId']
            ?? '';
        $reason = $payload['reason']
            ?? $payload['data']['reason']
            ?? $payload['failureReason']
            ?? $payload['data']['failureReason']
            ?? null;

        $mappedEvent = $this->mapWebhookEventType($eventType);

        return [
            'gateway_payout_id' => $transferId,
            'event_type' => $mappedEvent,
            'reason' => $reason,
        ];
    }

    /**
     * Map PayU webhook event types to standard payout statuses.
     */
    private function mapWebhookEventType(string $eventType): string
    {
        $eventType = strtolower($eventType);

        return match (true) {
            str_contains($eventType, 'success'),
            str_contains($eventType, 'processed'),
            str_contains($eventType, 'completed') => 'processed',

            str_contains($eventType, 'fail'),
            str_contains($eventType, 'rejected') => 'failed',

            str_contains($eventType, 'reverse'),
            str_contains($eventType, 'reversed') => 'reversed',

            default => 'unknown',
        };
    }

    /**
     * Map internal transfer status to standard payout status.
     */
    private function mapTransferStatus(string $status): string
    {
        $status = strtolower($status);

        return match (true) {
            str_contains($status, 'success'),
            str_contains($status, 'processed'),
            str_contains($status, 'completed') => 'processed',

            str_contains($status, 'fail'),
            str_contains($status, 'rejected') => 'failed',

            str_contains($status, 'reverse'),
            str_contains($status, 'reversed') => 'reversed',

            str_contains($status, 'pending'),
            str_contains($status, 'initiated'),
            str_contains($status, 'processing'),
            str_contains($status, 'in_progress') => 'processing',

            default => 'pending',
        };
    }

    /**
     * Map standard payout mode to PayU payment mode.
     */
    private function mapMode(string $mode): string
    {
        return match (strtoupper($mode)) {
            'IMPS' => 'IMPS',
            'NEFT' => 'NEFT',
            'RTGS' => 'RTGS',
            'UPI' => 'UPI',
            default => 'IMPS',
        };
    }
}
