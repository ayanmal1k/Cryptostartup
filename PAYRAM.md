# PayRam Payment Integration - What Was Done

This document provides a simple, clear explanation of all the changes made to add **PayRam Crypto Payments** to this project.

---

## 1. What is PayRam?
PayRam is a self-hosted cryptocurrency payment gateway. We integrated it into this platform alongside existing processors (Stripe, PayPal, Coinbase, NowPayments) so users can pay and deposit funds using crypto.

---

## 2. Summary of Changes (Files & What They Do)

### 1. Database Migration
* **File:** `database/migrations/2026_10_20_180500_add_payram_payment_provider.php`
* **What it did:**
  * Added 2 new tracking columns to the `transactions` table: `payram_payment_id` and `payram_order_id`.
  * Added 4 settings entries to the Voyager `settings` table (`payments.payram_api_url`, `payments.payram_api_key`, `payments.payram_webhook_secret`, and `payments.payram_checkout_disabled`).
  * **Status:** Migration has already been run and applied to the database.

### 2. Transaction Model
* **File:** `app/Model/Transaction.php`
* **What it did:**
  * Added constant `PAYRAM_PROVIDER = 'payram'`.
  * Added `payram` to allowed payment providers and pending processors (since crypto requires on-chain confirmation before approval).
  * Added `payram_payment_id` and `payram_order_id` to `$fillable`.

### 3. Payment Generator Helper
* **File:** `app/Helpers/PaymentHelper.php`
* **What it did:**
  * Added `generatePayramOrderId()`: Creates a unique, idempotent order ID for every checkout attempt.
  * Added `generatePayramTransaction()`: Calls your PayRam API (`POST /api/v1/payment`) with amount, customer info, and invoice ID, then returns the PayRam checkout link.

### 4. Controller & Webhook Handler
* **File:** `app/Http/Controllers/PaymentsController.php`
* **What it did:**
  * Wired `payram` into `initiatePayment()` for:
    * Wallet deposits
    * Post unlocks
    * User tips
    * Live-stream tickets
    * Paid messages
  * Added `payramHook()`: Receives webhook notifications from PayRam. Verifies the `X-Payram-Signature` HMAC-SHA256 signature, ensures safety against fake webhooks, and settles the transaction when payment is `FILLED`.
  * Added `checkAndUpdatePayramTransaction()`: Handles the user redirect when they return from the PayRam checkout page.

### 5. Web Routes & CSRF Protection
* **File:** `routes/web.php`
  * Added `GET /payment/payram/status` (redirect callback).
  * Added `POST /payment/payramStatusUpdate` (webhook endpoint).
* **File:** `app/Http/Middleware/VerifyCsrfToken.php`
  * Exempted `payment/payramStatusUpdate` from CSRF token checks so PayRam's server-to-server webhook requests are not blocked by Laravel.

### 6. User Interface (Frontend)
* **File:** `resources/views/elements/checkout/checkout-box.blade.php`
  * Added the PayRam Crypto button to the checkout modal (visible when PayRam is enabled).
* **File:** `resources/views/elements/settings/settings-wallet-deposit.blade.php`
  * Added PayRam Crypto as an option on the wallet deposit page.
* **File:** `public/img/logos/payram.svg`
  * Created a clean SVG logo for PayRam displayed in checkout buttons.
* **Files:** `public/js/pages/checkout.js` & `public/js/pages/settings/deposit.js`
  * Enabled PayRam selection, dynamic visibility toggling, and checkout submission.

### 7. Admin Panel (Voyager)
* **File:** `resources/views/vendor/voyager/settings/index.blade.php`
  * Added PayRam to the payment processor selector dropdown.
  * Added an info box displaying your webhook endpoint URL.
* **File:** `public/js/admin.js`
  * Added UI toggle logic to show/hide PayRam configuration fields in the admin settings.

### 8. Environment Configuration
* **File:** `.env`
  * Added placeholder variables so you can easily configure credentials:
    * `PAYRAM_API_URL=`
    * `PAYRAM_API_KEY=`
    * `PAYRAM_WEBHOOK_SECRET=`

---

## 3. How to Use It (Quick Setup)

### Step 1: Add Credentials
You can provide your credentials in either place:
* **Option A:** Put them in `.env`:
  ```env
  PAYRAM_API_URL=https://pay.yourdomain.com
  PAYRAM_API_KEY=pk_live_your_key_here
  PAYRAM_WEBHOOK_SECRET=whsec_your_secret_here
  ```
* **Option B:** Go to **Admin Dashboard &rarr; Settings &rarr; Payments**, select **PayRam**, enter the values, and save.

### Step 2: Configure Webhook in PayRam
In your self-hosted PayRam dashboard:
* **Webhook URL:** `https://yourdomain.com/payment/payramStatusUpdate`
* Copy the webhook secret and paste it into `.env` or the Admin Panel.

---

## 4. Verification Done
* All PHP files passed syntax checks (`php -l`).
* Routes tested and verified.
* Database columns and settings rows verified.
* Webhook HMAC security verified (valid signature accepted, invalid/forged signatures rejected with `400 Bad Request`).
