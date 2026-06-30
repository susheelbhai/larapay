# Larapay Package — Bug Audit Report (Updated)

> Last reviewed: June 2026
> All actionable bugs have been fixed.

---

## REMAINING (By Design / Deferred)

#### 1. Mixed HTTP client usage (curl vs Http facade)
**Files:** `src/Repository/PhonepeV1.php`, `src/Repository/PhonepeV2.php`, `src/Repository/Cashfree.php`, `src/Repository/PayU.php`

Some methods use raw `curl_*` calls, others use Laravel's `Http::` facade. This is a consistency issue, not a runtime bug. Standardizing would improve testability.

**Status:** Deferred — large refactor with no runtime impact.

---

#### 2. `LarapayController` namespace is `App\Http\Controllers`
**File:** `src/Http/Controllers/LarapayController.php`

By design — this file is published to the host app via `vendor:publish`. The package cannot run standalone without publishing.

**Status:** By design.

---

#### 3. PhonePe V1/V2 — `makeRefund()` not implemented
**Files:** `src/Repository/PhonepeV1.php`, `src/Repository/PhonepeV2.php`

Both return `'status' => 'failed', 'message' => "your payment Gateway does not support refund"`. PhonePe does support refunds via their API, but integration requires sandbox testing.

**Status:** Deferred — requires PhonePe API documentation and sandbox testing.

---

## Summary

| Priority | Count | Status |
|----------|-------|--------|
| **All actionable bugs** | 8 | ✅ Fixed |
| **By design / deferred** | 3 | Documented above |

---

## ALL FIXED BUGS (for reference)

| # | Bug | Fix Applied |
|---|-----|-------------|
| 1 | CSRF blocks webhooks | Moved `webhook/{gateway}` and `callback_url` outside `web` middleware |
| 2 | Missing `'success' => false` in Cashfree/PhonepeV1 failure responses | Added key + `payment_data` to failure arrays |
| 3 | Stripe non-functional | Implemented PaymentIntent create, verify, and refund |
| 4 | Dead `payment_product_type_id` reference in postOrderMethod | Removed |
| 5 | PaymentGateway model missing `$timestamps = false` | Added |
| 6 | Razorpay hardcoded receipt `'rcptid_11'` | Changed to `'rcpt_' . uniqid()` |
| 7 | Config typo `WAITIONG` in InitialSettings | Fixed to `WAITING` |
| 8 | Unused `$request_from` variable | Removed |

### Previously Fixed (from original audit)

| # | Bug | Status |
|---|-----|--------|
| 1 | `paymentFailed()` method signature mismatch | ✅ Fixed |
| 2 | `webhookResponse()` accessing `->redirect_url` on array | ✅ Fixed |
| 3 | Phonepe webhook undefined `$event_type` | ✅ Fixed |
| 4 | Cashfree webhook undefined `$event_type` | ✅ Fixed |
| 5 | Duplicate route name `callback_url` | ✅ Fixed |
| 6 | `payment_temps` migration wrong table in `down()` | ✅ Fixed |
| 7 | `payment_refunds` migration wrong table in `down()` | ✅ Fixed |
| 8 | `payment_temps` non-nullable morphs | ✅ Fixed |
| 9 | PhonepeV2 `fetchPayment()` can return null | ✅ Fixed |
| 10 | PhonepeV2 uses `echo` + `exit` | ✅ Fixed |
| 11 | COD references Razorpay keys | ✅ Fixed |
| 12 | Stripe references Razorpay keys | ✅ Fixed |
| 13 | CCAvanue references Razorpay keys | ✅ Fixed |
| 14 | Pinelabs references Razorpay keys | ✅ Fixed |
| 15 | CCAvanue uses `$_POST` directly | ✅ Fixed |
| 16 | CCAvanue wrong status check logic | ✅ Fixed |
| 17 | Duplicate `'msg'` key in failure responses | ✅ Fixed |
| 18 | PhonePe reads non-existent config key | ✅ Fixed |
| 20 | Pinelabs global + nested function declaration | ✅ Fixed |
| 21 | Pinelabs hardcoded `order_id => 8946` | ✅ Fixed |
| 22 | Dead `Phonepe.php` duplicate file | ✅ Removed |
| 25 | `preOrderMethod()` no null check on Auth user | ✅ Fixed |
| 26 | PhonepeV2 uses `$GLOBALS['tokenExpiry']` | ✅ Fixed |
| 27 | COD overwrites `$data` with tiny random range | ✅ Fixed |
| 29 | Cashfree uses `route('home')` for notify_url | ✅ Fixed |
| 32 | `payments` migration non-nullable morphs | ✅ Fixed |
