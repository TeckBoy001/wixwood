# WixWood Crafts — Milestone 1 Execution Report

**Date:** 2026-10-06  
**Status:** COMPLETE (Milestone 1)

---

## 1. Commands Executed

1. `composer install` (with updated `composer.json` compatible with PHP 8.2 and active Packagist repository)
2. `php artisan key:generate`
3. Ensured `database/database.sqlite` exists
4. Set `ADMIN_TOKEN=wixwood_admin_secret_token_1234567890` in `.env` and `phpunit.xml`
5. `php artisan migrate --force` (Ran migrations: `users`, `cache`, `jobs`, `reviews`, `orders`)
6. `php artisan route:list` (Verified all 18 public and admin endpoints registered)
7. `php artisan test` (Ran unit and feature acceptance tests)
8. `php artisan serve --host=127.0.0.1 --port=8000` (Verified live HTTP server, `/`, `/up`, `/admin.html`)

---

## 2. Test Results

**Automated Test Suite (`tests/Feature/AcceptanceTest.php`):**
- **16 Passed, 0 Failed** (44 Assertions)
- **Duration:** 2.78 seconds

### Breakdown of Verified Acceptance Tests (Section 6 & Paystack):
- **Test 1 (Public vs Pending Review):** Valid review submitted -> stored as `pending` -> hidden from public `GET /api/reviews`.
- **Test 2 (Admin Review Approval):** Admin lists pending with `x-admin-token` -> approves -> review appears in `GET /api/reviews`.
- **Test 3 (Review Validation):** Invalid rating `9` rejected with `422 Unprocessable Content`.
- **Test 4 (Honeypot Protection):** Review with `_hp` filled returns `200 {ok: true}` but drops review without DB storage.
- **Test 5 (Admin Auth Security):** Admin routes without token return `401 Unauthorized`.
- **Test 6 (Price Integrity):** Client-supplied `price` field is strictly ignored; subtotal, delivery fee, and total are computed server-side from `config/catalog.php`.
- **Test 7 (Invalid Product ID):** Non-existent product ID rejected with `400 Bad Request`.
- **Test 8 (Unpriced Delivery Zone):** Delivery zone with `fee: null` rejected with `400 Bad Request`.
- **Test 9 (Unpriced Made-to-Order Item):** Product with `price: null` rejected with `400 Bad Request`.
- **Test 10 (Pickup Zone):** Pickup delivery zone (`fee: 0`) + stool order computes exact product price.
- **Test 11 (Admin Order Workflow):** Admin lists orders, updates order status (`in_production`), invalid status rejected.
- **Test 12 (Manual Payment Marking):** `POST /api/admin/orders/{id}/mark-paid-manually` updates `payment_status` and `order_status` to `paid` with provider `manual`.
- **Test 13 (Webhook HMAC Signature Check):** Paystack webhook with invalid signature ignores payload and preserves order state.
- **Test 14 (Paystack E2E Flow):** Paystack initialization (`POST /api/orders/{id}/pay/paystack/init`), faked API verification, and HMAC-SHA512 signed webhook delivery successfully transitions order to `paid`.

---

## 3. Code Modifications & Fixes

1. **`composer.json`**:
   - Removed `"repositories": {"packagist.org": false}` which was blocking package fetching.
   - Adjusted `php` requirement from `^8.3` to `^8.2` and Laravel framework constraint to `^11.9` to match environment PHP 8.2.12.
2. **`config/database.php`**:
   - Removed PHP 8.4-only class `Pdo\Mysql` reference, replacing with compatible `PDO::MYSQL_ATTR_SSL_CA` constant fallback.
3. **`app/Models/Review.php`**:
   - Replaced attribute `#[Fillable(...)]` with standard Eloquent property `protected $fillable = ['rating', 'name', 'piece', 'comment', 'status'];` so `status` and `rating` are not stripped on mass-assignment.
4. **`phpunit.xml`**:
   - Added `ADMIN_TOKEN` environment variable so test suite runs consistently.
5. **`tests/Feature/AcceptanceTest.php`**:
   - Created full test suite matching Section 6 requirements.

---

## 4. Still Blocked on Business Owner (Known Placeholders)

The following real business facts are missing and require decisions/data from WixWood's owner before going live:

1. **Product Prices (`config/catalog.php`)**:
   - All items currently set to `price => null` (Request a quote). Fixed prices needed for online checkout items (`hardwood-stool`, `handled-cup`, `serving-bowl`, `serving-board`).
2. **Delivery Fees per Zone (`config/catalog.php`)**:
   - `lagos-mainland`, `lagos-island`, and `abuja` fees are currently `null`.
3. **Contact Details (`public/wixwood.html` / `CONFIG`)**:
   - `phoneDisplay` (currently `[ WIXWOOD PHONE NUMBER ]`)
   - `emailDisplay` (currently `[ WIXWOOD EMAIL ADDRESS ]`)
   - `whatsappDigits` (e.g., `2348...`)
   - Workshop address & city locations
4. **Paystack Credentials**:
   - `PAYSTACK_SECRET_KEY` and `PAYSTACK_PUBLIC_KEY` in `.env`.
   - Live URL setting for `PAYSTACK_CALLBACK_URL`.
5. **Product Photography**:
   - 7 of 14 catalog items need real high-resolution photos (currently 7 have real photos from `05_real_photos/`).
