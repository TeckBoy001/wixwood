# WixWood Crafts — Build Instructions for Antigravity

Read this whole file first. It is the single source of truth for what exists,
what to run, what to build next, and what you must never do.

---

## 1. What WixWood is

WixWood Crafts is a Nigerian custom woodworking / furniture studio
(Instagram: @wizwood_). Products: dining tables, chairs/stools, epoxy/river
tables, wooden bowls/cups, hardwood signage, custom interior builds
(consoles, bars), upholstered lounge sets, serving/cutting boards. Delivery:
Lagos and Abuja. Payment: Paystack (Flutterwave possible later).

**The product:** a premium, white-first furniture showroom website that also
takes real orders and online payment for the few fixed-price items, and routes
made-to-order furniture through a request-a-quote flow. Customer reviews are
moderated by WixWood before they appear.

---

## 2. What is in this bundle

| Folder | What it is | Status |
|---|---|---|
| `01_laravel_app/` | **The app to build and ship.** Laravel 13 (PHP ^8.3) skeleton from upstream `laravel/laravel`, plus hand-written reviews + orders + Paystack code. | Code written and `php -l` clean. **Never run yet** (no `vendor/`, packagist was blocked where it was written). |
| `02_static_site/index.html` | The finished front-end as one self-contained HTML file (~1.9 MB, real photos embedded as base64). Same file is already copied into the Laravel app as `public/wixwood.html`. | Working. Published and visually reviewed. |
| `03_node_reference_backend/` | Express + SQLite version of the same backend. **Reference only.** | **Tested end-to-end** (see section 6). Use it as the behavioural spec the Laravel port must match. |
| `04_design_docs/` | Brochure (.pptx), design-preview PDF, order/payment-flow PDF. | Reference for the business owner and for design intent. |
| `05_real_photos/` | Real WixWood photos supplied by the owner. | Source assets. |
| `06_original_briefs/` | The owner's original prompts. | Read `1_MASTER_ecommerce_brief.md` for the full intent. |

**Decision already made:** Laravel (`01_laravel_app/`) is the production
target. Do not start a third implementation. Do not rebuild the front-end from
scratch.

---

## 3. First commands (do these before anything else)

```bash
cd 01_laravel_app
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # if DB_CONNECTION=sqlite and the file is missing
php artisan migrate
php artisan serve
```

Then open:
- `http://localhost:8000/` → the WixWood site
- `http://localhost:8000/admin.html` → the admin page (Reviews + Orders tabs)
- `http://localhost:8000/up` → Laravel health check

Set `ADMIN_TOKEN` in `.env` first (generate one with
`php artisan tinker --execute="echo bin2hex(random_bytes(24));"`), otherwise
every `/api/admin/*` call correctly returns 401.

`01_laravel_app/CLAUDE.md` / `AGENTS.md` contain upstream Laravel Boost setup
steps. Run `composer require laravel/boost --dev && php artisan boost:install`
if you want the generated house guidelines.

---

## 4. First job: make the Laravel port actually run, and prove it

The Laravel code has **not** been executed. Treat every file in
`01_laravel_app/app`, `config`, `routes`, `database/migrations` as unverified
until it runs. In order:

1. `composer install` succeeds; `php artisan migrate` creates `reviews` and
   `orders`.
2. `php artisan route:list` shows the routes in section 5.
3. Fix anything that breaks. Known things to check specifically:
   - `Review::create([... 'status' => 'pending'])` — `status` is **not** in the
     `#[Fillable]` list, so it may be silently dropped. The DB default is
     `pending` so behaviour is still correct, but make it explicit and add the
     test below.
   - `bootstrap/app.php` registers the `admin.token` middleware alias and the
     `api:` routing line. Confirm `/api/*` routes resolve.
   - `config/cors.php`: the `array_filter(explode(...)) ?: ['*']` expression —
     confirm it behaves with `ALLOWED_ORIGINS` empty and set.
   - `OrderController::webhook` reads `$request->getContent()` for HMAC. Confirm
     the raw body is untouched by any middleware.
   - The webhook route must be reachable without CSRF/auth (it is under `api`,
     so it should be).
4. **Write Pest/PHPUnit feature tests** that reproduce the Node test results in
   section 6. This is the acceptance bar for the port.

---

## 5. API contract (must stay stable — the front-end and `admin.html` depend on it)

Public:
- `GET  /api/reviews` → `{reviews:[{id,rating,name,piece,comment,created_at}]}` (approved only)
- `POST /api/reviews` `{rating 1-5, name?, piece?, comment>=5 chars, _hp}` → 201 `{ok,id}`; `_hp` filled → 200 `{ok:true}` and **nothing stored**; throttle 5/hour
- `POST /api/orders` `{items:[{id,qty}], customer:{name,phone,email?}, delivery:{address,zone,city?,state?}, notes?}` → 201 `{ok,id,subtotal,deliveryFee,total,currency}`; throttle 10/hour
- `POST /api/orders/{id}/pay/paystack/init` → `{ok,authorization_url,reference}`; 503 if no Paystack key; 409 if already paid
- `GET  /api/orders/{id}/status` → `{id,order_status,payment_status,total,currency}` (no PII)
- `POST /api/webhooks/paystack` — Paystack only
- `GET  /api/config/public` → currency, `onlinePaymentEnabled`, products, deliveryZones

Admin (header `x-admin-token: <ADMIN_TOKEN>` or `?token=`):
- `GET /api/admin/reviews?status=pending|approved|rejected`
- `POST /api/admin/reviews/{id}/approve` · `.../reject` · `DELETE /api/admin/reviews/{id}`
- `GET /api/admin/orders?status=` · `GET /api/admin/orders/{id}`
- `POST /api/admin/orders/{id}/status` `{status}` ∈ processing, in_production, ready_for_delivery, delivered, cancelled
- `POST /api/admin/orders/{id}/mark-paid-manually` (sets `payment_provider='manual'`)

Order statuses: `pending_payment → paid → processing → in_production → ready_for_delivery → delivered`, or `cancelled`.

---

## 6. Acceptance tests (run by hand against the Node reference)

Items 1-8, 10-12 were exercised against the Node reference and behaved as
described. Item 9 (null-price product) and the "order state unchanged" half of
item 13 are implemented in the Node code but were not explicitly run — verify
them in the Laravel tests rather than assuming. Reproduce each one as an
automated Laravel test:

1. Submit a valid review → stored `pending` → **not** in `GET /api/reviews`.
2. Admin lists pending (with token) → approve → now **in** `GET /api/reviews`.
3. Invalid rating (9) → 400/422.
4. Honeypot `_hp` filled → 200 but nothing stored.
5. Any `/api/admin/*` without token → 401.
6. **Price integrity:** with `hardwood-stool` priced 45000 and `lagos-mainland`
   fee 5000, `POST /api/orders` with `items:[{id:"hardwood-stool",qty:2,price:1}]`
   returns `subtotal 90000, deliveryFee 5000, total 95000`. The client-sent
   `price` is ignored.
7. Product id not in the catalog (e.g. `luxury-dining`) → 400.
8. Delivery zone whose fee is `null` → 400.
9. Product whose price is `null` → 400.
10. Pickup zone (fee 0) + 1 stool → total 45000.
11. Admin can list/filter orders, change status; invalid status → 4xx.
12. Mark-paid-manually flips `payment_status` and `order_status` to paid.
13. Webhook with a bad signature changes nothing.

**Not yet tested anywhere** (needs a Paystack account — be honest about this):
the real Paystack initialize call, a real webhook delivery, and the amount
verification path. Test with `Http::fake()` in automated tests, then with
Paystack **test** keys (`sk_test_…`) and their test cards. Never claim online
payment works until a test-key charge has gone through end to end.

---

## 7. Hard rules (from the owner — do not break these)

**Never fabricate:** prices, delivery fees, phone numbers, emails, addresses,
reviews, testimonials, review counts, ratings, customer counts, awards,
certifications, company history, years in business, product specs, payment
credentials, social stats. If a fact is missing, leave a clearly visible
config placeholder or remove the claim.

**Prices:** every price in `config/catalog.php` is `null` on purpose. Do **not**
invent numbers. A `null` price means the item stays "Request a quote". Only the
four fixed-price items (`hardwood-stool`, `handled-cup`, `serving-bowl`,
`serving-board`) can ever be sold online. Everything else is made-to-order and
must not be forced into checkout.

**Money security:**
- Totals are computed server-side from `config/catalog.php` only. Never read a
  price or total from the request.
- An order becomes `paid` only after (a) a valid Paystack webhook signature
  (HMAC-SHA512 of the raw body) **and** (b) a server-to-server Paystack Verify
  call confirming status `success`, matching amount (in kobo) and currency.
  A browser redirect or a frontend "success" message is never proof of payment.
- Secret keys live in `.env` only. Never in front-end code, never committed.
- Handle duplicate webhook deliveries without double-processing.

**Reviews:** nothing is public until an admin approves it. No placeholder or
sample reviews anywhere. An empty state is the correct display when there are none.

**Design (white-first luxury showroom):** white / warm off-white dominates;
natural wood tones are an accent only; charcoal for text and contrast. No large
solid-brown areas, no brown cards/buttons everywhere. The real furniture
photography supplies the warmth. Do not replace real photos with generic
illustrations. No empty grids or placeholder cards — reduce columns, reorder, or
hide a section rather than fill it with fake content.

**Do not** turn this into a big platform. WixWood is a furniture studio. Keep the
admin simple (one shared token is fine for now).

---

## 8. Known missing business information (placeholders still in the site)

These must come from the owner. Do not guess them:

- Phone number (`CONFIG.phoneDisplay` — currently `[ WIXWOOD PHONE NUMBER ]`)
- Email address (`CONFIG.emailDisplay` — currently `[ WIXWOOD EMAIL ADDRESS ]`)
- WhatsApp number digits (`CONFIG.whatsappDigits`, e.g. `2348012345678`)
- All product prices (`config/catalog.php`, mirrored by `price:null` in the site's `PRODUCTS`)
- Delivery fees per zone (`config/catalog.php` → `delivery_zones`)
- Production lead times
- Physical address / workshop location
- Paystack keys and a Paystack payment-confirmation page URL

Product photos: 7 of 14 products have real photos; 7 still show a minimal icon
fallback because no photo was supplied. `05_real_photos/` has the raw files.
Ask the owner for more rather than using stock or AI imagery.

---

## 9. Build backlog, in priority order

**A. Make it run and test it** (section 4 and 6). Nothing else is trustworthy
until this is done.

**B. Product data out of the HTML.** Right now products live in a `PRODUCTS`
array inside `public/wixwood.html`, with base64 photos embedded (1.9 MB). Move
them to real storage: a `products` table (or config), images in
`public/images/products/` (or `storage`) served as files with sensible sizes,
lazy loading and alt text. Keep prices coming from `config/catalog.php` /
the API. Preserve all 14 product ids exactly:
`luxury-dining, exec-epoxy, egg-table, hardwood-chairs, hardwood-stool,
river-table, handled-cup, serving-bowl, signage, interior, lounge-set,
accent-armchair, tv-console, serving-board`.

**C. Convert the front-end to Blade** (or keep the single file and just serve it —
your call, but justify it). The visual design must not regress. Keep the
checkout fallback: if the API is unreachable the site must still offer the
WhatsApp/email order summary.

**D. Real order confirmation page.** After Paystack, send the customer to a
page that polls `GET /api/orders/{id}/status` and shows the true state. Set
`PAYSTACK_CALLBACK_URL` to it. Show "payment pending" until the webhook has
confirmed — never "paid" from the redirect alone.

**E. Notifications.** Email WixWood on new order and new review (Laravel
Notifications/Mail). Optionally email the customer a receipt after verified
payment. WhatsApp automation needs a paid external API — leave it clearly
separated and unconfigured rather than faking it.

**F. Admin hardening.** Constant-time token compare (`hash_equals`), login
throttle, and consider moving to Laravel auth with a seeded admin user. Add
"hide/unpublish an approved review" (a status like `hidden`, or reuse
`rejected`) since the owner asked for it.

**G. Reviews UX stays a 3-step flow:** Rate → Tell us → Submit, then a success
state. Already built in the front-end; keep it working and mobile-friendly.

**H. Pre-launch:** responsive pass (phone, tablet, laptop, large), no horizontal
overflow, page `<title>` / meta description, alt text on every photo,
accessible form labels, keyboard navigation, sensible image sizes. Move off
SQLite to Postgres/MySQL only if you deploy somewhere without persistent disk.

**I. Deploy:** any PHP host with a persistent disk, or managed DB. Set `APP_ENV=production`,
`APP_DEBUG=false`, `ADMIN_TOKEN`, `ALLOWED_ORIGINS`, Paystack keys; point the
Paystack dashboard webhook at `https://<host>/api/webhooks/paystack`. Switch to
live keys only after a full test-key run-through.

---

## 10. Where to look for details

- Intent and tone: `06_original_briefs/1_MASTER_ecommerce_brief.md`
- Expected behaviour of the backend: `03_node_reference_backend/server.js`
  (`catalog.json` there ↔ `config/catalog.php` here)
- Laravel translation table and honest gap list: `01_laravel_app/WIXWOOD_README.md`
- Customer order/payment flow diagram: `04_design_docs/WixWood_Order_Payment_Flow.pdf`
- Design intent: `04_design_docs/WixWood_Website_Design_Preview.pdf`

When something here conflicts with something you would normally do, follow this
file. When something is unknown, leave a visible placeholder and list it for the
owner; do not invent it.

---

## 11. Definition of done for the first milestone

- [ ] `composer install`, `migrate`, `serve` work from a clean clone
- [ ] All 13 acceptance tests in section 6 pass as automated tests
- [ ] Site loads at `/`, review flow submits to the API, admin approves, review appears
- [ ] Checkout with a priced test item creates an order visible in admin
- [ ] Paystack path covered by faked-HTTP tests; test-key run documented
- [ ] No fabricated content anywhere; all prices still `null` unless the owner supplied them
- [ ] A short `CHANGELOG`/notes file listing what you changed and what is still blocked on the owner
