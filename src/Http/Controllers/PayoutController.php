<?php

namespace Susheelbhai\Larapay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LarapayPayoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Larapay\Models\Payout;
use Susheelbhai\Larapay\Models\PayoutBeneficiary;
use Susheelbhai\Larapay\Repository\CashfreePayout;
use Susheelbhai\Larapay\Repository\PayUPayout;
use Susheelbhai\Larapay\Repository\RazorpayPayout;

class PayoutController extends Controller
{
    /**
     * Gateway name mapping: database payment_gateways.id → string name.
     */
    private function gatewayName(int $gateway): string
    {
        return match ($gateway) {
            2 => 'razorpay',
            7 => 'cashfree',
            8 => 'payu',
            default => throw new \InvalidArgumentException("Unsupported payout gateway: {$gateway}"),
        };
    }

    /**
     * Resolve the gateway repository instance by database payment_gateways.id.
     */
    private function resolveGatewayRepository(int $gateway): object
    {
        return match ($gateway) {
            2 => new RazorpayPayout,
            7 => new CashfreePayout,
            8 => new PayUPayout,
            default => throw new \InvalidArgumentException("Unsupported payout gateway: {$gateway}"),
        };
    }

    /**
     * Create/register a beneficiary at the gateway.
     *
     * @param  array  $data  [name, account_number, ifsc, vpa, account_type, user_id, user_type, email, phone]
     * @param  int  $gateway  Gateway identifier (1=razorpay, 2=cashfree, 3=payu)
     * @return array Gateway references + local record
     */
    public function createBeneficiary(array $data, int $gateway): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Beneficiary name is required'];
        }

        if (empty($data['account_type'])) {
            return ['success' => false, 'error' => 'Account type is required'];
        }

        $hasBankAccount = ! empty($data['account_number']) && ! empty($data['ifsc']);
        $hasVpa = ! empty($data['vpa']);

        if (! $hasBankAccount && ! $hasVpa) {
            return ['success' => false, 'error' => 'Either account_number + ifsc or vpa is required'];
        }

        $gatewayName = $this->gatewayName($gateway);

        // Idempotency check: look for existing beneficiary with same details + gateway
        $existingBeneficiary = null;

        if ($hasBankAccount) {
            $existingBeneficiary = PayoutBeneficiary::where('account_number', $data['account_number'])
                ->where('ifsc', $data['ifsc'])
                ->where('gateway_name', $gatewayName)
                ->first();
        } elseif ($hasVpa) {
            $existingBeneficiary = PayoutBeneficiary::where('vpa', $data['vpa'])
                ->where('gateway_name', $gatewayName)
                ->first();
        }

        if ($existingBeneficiary) {
            return [
                'success' => true,
                'data' => $existingBeneficiary->toArray(),
                'existing' => true,
            ];
        }

        // Call gateway repository to create beneficiary
        $repo = $this->resolveGatewayRepository($gateway);
        $gatewayResponse = $repo->createBeneficiary($data);

        if (! $gatewayResponse['success']) {
            return $gatewayResponse;
        }

        // Store local PayoutBeneficiary record
        $beneficiary = PayoutBeneficiary::create([
            'user_id' => $data['user_id'] ?? null,
            'user_type' => $data['user_type'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'gateway_beneficiary_id' => $gatewayResponse['gateway_beneficiary_id'] ?? null,
            'gateway_contact_id' => $gatewayResponse['gateway_contact_id'] ?? null,
            'gateway_fund_account_id' => $gatewayResponse['gateway_fund_account_id'] ?? null,
            'account_type' => $data['account_type'],
            'account_number' => $data['account_number'] ?? null,
            'ifsc' => $data['ifsc'] ?? null,
            'vpa' => $data['vpa'] ?? null,
            'gateway_name' => $gatewayName,
            'payment_gateway_id' => $gateway,
            'is_active' => true,
        ]);

        return [
            'success' => true,
            'data' => $beneficiary->toArray(),
            'existing' => false,
        ];
    }

    /**
     * Initiate a payout — supports stored beneficiary_id OR inline raw details.
     *
     * @param  array  $data  [beneficiary_id|account_details, amount, currency, purpose, mode, name, account_number, ifsc, vpa, account_type, email, phone]
     * @param  int  $gateway  Gateway identifier (1=razorpay, 2=cashfree, 3=payu)
     * @return array Payout record + gateway response
     */
    public function createPayout(array $data, int $gateway): array
    {
        // Validate: either beneficiary_id OR raw account details required
        $hasBeneficiaryId = ! empty($data['beneficiary_id']);
        $hasBankAccount = ! empty($data['account_number']) && ! empty($data['ifsc']);
        $hasVpa = ! empty($data['vpa']);
        $hasRawDetails = $hasBankAccount || $hasVpa;

        if (! $hasBeneficiaryId && ! $hasRawDetails) {
            return ['success' => false, 'error' => 'Either beneficiary_id or account details required'];
        }

        // Validate amount
        $amount = $data['amount'] ?? 0;
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be positive'];
        }

        $gatewayName = $this->gatewayName($gateway);
        $repo = $this->resolveGatewayRepository($gateway);
        $beneficiary = null;
        $inlineCreated = false;

        // Resolve beneficiary
        if ($hasBeneficiaryId) {
            $beneficiary = PayoutBeneficiary::find($data['beneficiary_id']);

            if (! $beneficiary) {
                return ['success' => false, 'error' => 'Beneficiary not found'];
            }

            if (! $beneficiary->is_active) {
                return ['success' => false, 'error' => 'Beneficiary is inactive'];
            }
        }

        // Call prePayoutMethod hook
        $hookController = app(LarapayPayoutController::class);
        $hookController->prePayoutMethod($data, $gateway);

        // Execute payout via gateway
        $gatewayResponse = null;

        if ($hasBeneficiaryId && $beneficiary) {
            // Use stored beneficiary — call createPayout with gateway reference
            $gatewayBeneficiaryId = $beneficiary->gateway_fund_account_id
                ?? $beneficiary->gateway_beneficiary_id;

            $gatewayResponse = $repo->createPayout($data, $gatewayBeneficiaryId);
        } else {
            // Inline creation — call createPayoutInline
            $inlineCreated = true;
            $gatewayResponse = $repo->createPayoutInline($data);

            // If inline succeeded, store the new beneficiary locally
            if ($gatewayResponse['success']) {
                $beneficiary = PayoutBeneficiary::create([
                    'user_id' => $data['user_id'] ?? null,
                    'user_type' => $data['user_type'] ?? null,
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'gateway_beneficiary_id' => $gatewayResponse['gateway_beneficiary_id'] ?? null,
                    'gateway_contact_id' => $gatewayResponse['gateway_contact_id'] ?? null,
                    'gateway_fund_account_id' => $gatewayResponse['gateway_fund_account_id'] ?? null,
                    'account_type' => $data['account_type'] ?? ($hasVpa ? 'vpa' : 'bank_account'),
                    'account_number' => $data['account_number'] ?? null,
                    'ifsc' => $data['ifsc'] ?? null,
                    'vpa' => $data['vpa'] ?? null,
                    'gateway_name' => $gatewayName,
                    'payment_gateway_id' => $gateway,
                    'is_active' => true,
                ]);
            }
        }

        // If gateway returned an error, store Payout as failed
        if (! $gatewayResponse['success']) {
            $payout = Payout::create([
                'payout_beneficiary_id' => $beneficiary?->id,
                'payment_gateway_id' => $gateway,
                'gateway_payout_id' => null,
                'amount' => $amount,
                'currency' => $data['currency'] ?? 'INR',
                'purpose' => $data['purpose'] ?? null,
                'mode' => $data['mode'] ?? null,
                'status' => 'failed',
                'failure_reason' => $gatewayResponse['error'] ?? 'Gateway error',
                'inline_created' => $inlineCreated,
            ]);

            return [
                'success' => false,
                'error' => $gatewayResponse['error'] ?? 'Gateway error',
                'data' => $payout->toArray(),
            ];
        }

        // Gateway succeeded — store Payout as processing
        $payout = Payout::create([
            'payout_beneficiary_id' => $beneficiary->id,
            'payment_gateway_id' => $gateway,
            'gateway_payout_id' => $gatewayResponse['gateway_payout_id'] ?? null,
            'amount' => $amount,
            'currency' => $data['currency'] ?? 'INR',
            'purpose' => $data['purpose'] ?? null,
            'mode' => $data['mode'] ?? null,
            'status' => 'processing',
            'utr' => $gatewayResponse['utr'] ?? null,
            'inline_created' => $inlineCreated,
        ]);

        // Call postPayoutMethod hook
        $hookController->postPayoutMethod(
            $data,
            $gatewayResponse['gateway_payout_id'] ?? '',
            $gateway
        );

        return [
            'success' => true,
            'data' => $payout->toArray(),
        ];
    }

    /**
     * Fetch current payout status from gateway and sync local record.
     *
     * @param  int  $payoutId  Local Payout record ID
     * @return array Updated payout data
     */
    public function fetchPayoutStatus(int $payoutId): array
    {
        $payout = Payout::find($payoutId);

        if (! $payout) {
            return ['success' => false, 'error' => 'Payout not found'];
        }

        $repo = $this->resolveGatewayRepository($payout->payment_gateway_id);
        $gatewayResponse = $repo->fetchPayoutStatus($payout->gateway_payout_id);

        if (! $gatewayResponse['success']) {
            return $gatewayResponse;
        }

        $newStatus = $gatewayResponse['status'] ?? null;
        $oldStatus = $payout->status;

        // Update local record if status changed
        if ($newStatus && $newStatus !== $oldStatus) {
            $updateData = ['status' => $newStatus];

            if (! empty($gatewayResponse['utr'])) {
                $updateData['utr'] = $gatewayResponse['utr'];
            }

            if ($newStatus === 'failed' && ! empty($gatewayResponse['failure_reason'])) {
                $updateData['failure_reason'] = $gatewayResponse['failure_reason'];
            }

            if ($newStatus === 'reversed') {
                $updateData['reversal_reason'] = $gatewayResponse['failure_reason'] ?? 'Payout reversed';
            }

            $payout->update($updateData);
            $payout->refresh();

            // Call appropriate hook
            $hookController = app(LarapayPayoutController::class);

            match ($newStatus) {
                'processed' => $hookController->payoutSuccessful($payout->toArray()),
                'failed' => $hookController->payoutFailed($payout->toArray(), $payout->failure_reason),
                'reversed' => $hookController->payoutReversed($payout->toArray(), $payout->reversal_reason),
                default => null,
            };
        }

        return [
            'success' => true,
            'data' => $payout->toArray(),
        ];
    }

    /**
     * Handle incoming payout webhook from gateway.
     *
     * @param  int  $gateway  Gateway identifier (1=razorpay, 2=cashfree, 3=payu)
     */
    public function payoutWebhook(Request $request, int $gateway): bool
    {
        $repo = $this->resolveGatewayRepository($gateway);

        // Verify webhook signature
        if (! $repo->verifyWebhookSignature($request)) {
            return false;
        }

        // Parse the webhook payload
        $payload = $repo->parseWebhookPayload($request);

        $gatewayPayoutId = $payload['gateway_payout_id'] ?? null;
        $eventType = $payload['event_type'] ?? null;
        $reason = $payload['reason'] ?? null;

        if (! $gatewayPayoutId) {
            Log::warning('Payout webhook received without gateway_payout_id', [
                'gateway' => $gateway,
                'payload' => $payload,
            ]);

            return true;
        }

        // Find local Payout by gateway_payout_id
        $payout = Payout::where('gateway_payout_id', $gatewayPayoutId)->first();

        if (! $payout) {
            Log::warning('Payout webhook: no local record found for gateway_payout_id', [
                'gateway_payout_id' => $gatewayPayoutId,
                'gateway' => $gateway,
            ]);

            return true;
        }

        // Update status based on event type
        $updateData = [];

        switch ($eventType) {
            case 'processed':
                $updateData['status'] = 'processed';
                break;
            case 'failed':
                $updateData['status'] = 'failed';
                $updateData['failure_reason'] = $reason;
                break;
            case 'reversed':
                $updateData['status'] = 'reversed';
                $updateData['reversal_reason'] = $reason;
                break;
            default:
                // Unknown event — log and return without updating
                Log::info('Payout webhook: unknown event type', [
                    'event_type' => $eventType,
                    'gateway_payout_id' => $gatewayPayoutId,
                ]);

                return true;
        }

        $payout->update($updateData);
        $payout->refresh();

        // Call appropriate hook on LarapayPayoutController
        $hookController = app(LarapayPayoutController::class);

        match ($eventType) {
            'processed' => $hookController->payoutSuccessful($payout->toArray()),
            'failed' => $hookController->payoutFailed($payout->toArray(), $reason),
            'reversed' => $hookController->payoutReversed($payout->toArray(), $reason),
            default => null,
        };

        return true;
    }
}
