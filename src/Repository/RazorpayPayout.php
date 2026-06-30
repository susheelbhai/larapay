<?php

namespace Susheelbhai\Larapay\Repository;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RazorpayPayout
{
    protected string $keyId;

    protected string $keySecret;

    protected string $accountNumber;

    protected string $webhookSecret;

    protected string $baseUrl = 'https://api.razorpay.com/v1';

    public function __construct()
    {
        $config = config('larapay.payout.razorpay');
        $this->keyId = $config['key_id'] ?? '';
        $this->keySecret = $config['key_secret'] ?? '';
        $this->accountNumber = $config['account_number'] ?? '';
        $this->webhookSecret = $config['webhook_secret'] ?? '';
    }

    /**
     * Create a beneficiary by creating a Contact and then a Fund Account (two API calls).
     *
     * @param  array  $data  [name, email, phone, type, account_type, account_number, ifsc, vpa]
     */
    public function createBeneficiary(array $data): array
    {
        try {
            // Step 1: Create Contact
            $contactPayload = [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'contact' => $data['phone'] ?? null,
                'type' => $data['type'] ?? 'vendor',
            ];

            $contactResponse = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post("{$this->baseUrl}/contacts", $contactPayload);

            if ($contactResponse->failed()) {
                return [
                    'success' => false,
                    'error' => $contactResponse->json('error.description', 'Contact creation failed'),
                    'gateway_error' => $contactResponse->json(),
                ];
            }

            $contactId = $contactResponse->json('id');

            // Step 2: Create Fund Account
            $fundAccountPayload = [
                'contact_id' => $contactId,
                'account_type' => $data['account_type'],
            ];

            if ($data['account_type'] === 'bank_account') {
                $fundAccountPayload['bank_account'] = [
                    'name' => $data['name'],
                    'ifsc' => $data['ifsc'],
                    'account_number' => $data['account_number'],
                ];
            } elseif ($data['account_type'] === 'vpa') {
                $fundAccountPayload['vpa'] = [
                    'address' => $data['vpa'],
                ];
            }

            $fundAccountResponse = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post("{$this->baseUrl}/fund_accounts", $fundAccountPayload);

            if ($fundAccountResponse->failed()) {
                return [
                    'success' => false,
                    'error' => $fundAccountResponse->json('error.description', 'Fund account creation failed'),
                    'gateway_error' => $fundAccountResponse->json(),
                ];
            }

            $fundAccountId = $fundAccountResponse->json('id');

            return [
                'success' => true,
                'gateway_contact_id' => $contactId,
                'gateway_fund_account_id' => $fundAccountId,
                'gateway_beneficiary_id' => $fundAccountId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway_error' => null,
            ];
        }
    }

    /**
     * Create a payout using an existing fund_account_id.
     *
     * @param  array  $data  [amount, currency, mode, purpose, notes]
     * @param  string  $gatewayBeneficiaryId  The fund_account_id at Razorpay
     */
    public function createPayout(array $data, string $gatewayBeneficiaryId): array
    {
        try {
            $payload = [
                'account_number' => $this->accountNumber,
                'fund_account_id' => $gatewayBeneficiaryId,
                'amount' => (int) ($data['amount'] * 100), // Convert to paise
                'currency' => $data['currency'] ?? 'INR',
                'mode' => $data['mode'] ?? 'IMPS',
                'purpose' => $data['purpose'] ?? 'payout',
            ];

            if (! empty($data['notes'])) {
                $payload['notes'] = $data['notes'];
            }

            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post("{$this->baseUrl}/payouts", $payload);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => $response->json('error.description', 'Payout creation failed'),
                    'gateway_error' => $response->json(),
                ];
            }

            return [
                'success' => true,
                'gateway_payout_id' => $response->json('id'),
                'status' => $response->json('status'),
                'utr' => $response->json('utr'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway_error' => null,
            ];
        }
    }

    /**
     * Create a payout using the Composite API — single POST with contact, fund_account, and payout details.
     *
     * @param  array  $data  [name, email, phone, type, account_type, account_number, ifsc, vpa, amount, currency, mode, purpose, notes]
     */
    public function createPayoutInline(array $data): array
    {
        try {
            $payload = [
                'account_number' => $this->accountNumber,
                'amount' => (int) ($data['amount'] * 100), // Convert to paise
                'currency' => $data['currency'] ?? 'INR',
                'mode' => $data['mode'] ?? 'IMPS',
                'purpose' => $data['purpose'] ?? 'payout',
                'fund_account' => [
                    'account_type' => $data['account_type'],
                    'contact' => [
                        'name' => $data['name'],
                        'email' => $data['email'] ?? null,
                        'contact' => $data['phone'] ?? null,
                        'type' => $data['type'] ?? 'vendor',
                    ],
                ],
            ];

            if ($data['account_type'] === 'bank_account') {
                $payload['fund_account']['bank_account'] = [
                    'name' => $data['name'],
                    'ifsc' => $data['ifsc'],
                    'account_number' => $data['account_number'],
                ];
            } elseif ($data['account_type'] === 'vpa') {
                $payload['fund_account']['vpa'] = [
                    'address' => $data['vpa'],
                ];
            }

            if (! empty($data['notes'])) {
                $payload['notes'] = $data['notes'];
            }

            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post("{$this->baseUrl}/payouts", $payload);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => $response->json('error.description', 'Inline payout creation failed'),
                    'gateway_error' => $response->json(),
                ];
            }

            return [
                'success' => true,
                'gateway_payout_id' => $response->json('id'),
                'gateway_contact_id' => $response->json('fund_account.contact_id'),
                'gateway_fund_account_id' => $response->json('fund_account_id'),
                'gateway_beneficiary_id' => $response->json('fund_account_id'),
                'status' => $response->json('status'),
                'utr' => $response->json('utr'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway_error' => null,
            ];
        }
    }

    /**
     * Fetch the current status of a payout from Razorpay.
     *
     * @param  string  $gatewayPayoutId  The Razorpay payout ID (e.g., pout_xxxxx)
     */
    public function fetchPayoutStatus(string $gatewayPayoutId): array
    {
        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->get("{$this->baseUrl}/payouts/{$gatewayPayoutId}");

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => $response->json('error.description', 'Failed to fetch payout status'),
                    'gateway_error' => $response->json(),
                ];
            }

            return [
                'success' => true,
                'gateway_payout_id' => $response->json('id'),
                'status' => $response->json('status'),
                'utr' => $response->json('utr'),
                'failure_reason' => $response->json('failure_reason'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'gateway_error' => null,
            ];
        }
    }

    /**
     * Verify webhook signature using HMAC-SHA256 on X-Razorpay-Signature header.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Razorpay-Signature');

        if (empty($signature) || empty($this->webhookSecret)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse webhook payload to extract payout_id, event_type, and reason.
     * Handles events: payout.processed, payout.reversed, payout.failed
     *
     * @return array [gateway_payout_id, event_type, reason]
     */
    public function parseWebhookPayload(Request $request): array
    {
        $payload = $request->all();

        $event = $payload['event'] ?? '';
        $payoutEntity = $payload['payload']['payout']['entity'] ?? [];

        $gatewayPayoutId = $payoutEntity['id'] ?? null;
        $reason = null;

        // Map Razorpay event names to normalized event types
        $eventType = match ($event) {
            'payout.processed' => 'processed',
            'payout.reversed' => 'reversed',
            'payout.failed' => 'failed',
            default => $event,
        };

        // Extract reason for failure/reversal
        if ($eventType === 'failed') {
            $reason = $payoutEntity['failure_reason'] ?? null;
        } elseif ($eventType === 'reversed') {
            $reason = $payoutEntity['status_details']['description']
                ?? $payoutEntity['failure_reason']
                ?? 'Payout reversed';
        }

        return [
            'gateway_payout_id' => $gatewayPayoutId,
            'event_type' => $eventType,
            'reason' => $reason,
        ];
    }
}
