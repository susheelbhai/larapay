<?php

namespace App\Http\Controllers;

// This controller is developed to publish in main controller
class LarapayPayoutController extends Controller
{
    /**
     * Called before sending payout request to gateway.
     * Host app can validate, enrich, or reject.
     */
    public function prePayoutMethod(array $data, int $gateway): void {}

    /**
     * Called after gateway responds successfully with payout_id.
     */
    public function postPayoutMethod(array $data, string $gatewayPayoutId, int $gateway): void {}

    /**
     * Called when payout status becomes "processed".
     */
    public function payoutSuccessful(array $payoutRecord): void {}

    /**
     * Called when payout status becomes "failed".
     */
    public function payoutFailed(array $payoutRecord, ?string $reason): void {}

    /**
     * Called when payout status becomes "reversed".
     */
    public function payoutReversed(array $payoutRecord, ?string $reason): void {}
}
