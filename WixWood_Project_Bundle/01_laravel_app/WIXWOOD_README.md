# WixWood Crafts — Laravel Port

This is a Laravel translation of the working Node/Express backend built
earlier in this project (reviews moderation + fixed-price checkout with
Paystack), on top of a genuine, unmodified `laravel/laravel` skeleton
(cloned from GitHub, Laravel ^13.17). It's meant as a continuation
point — hand it to Antigravity (or any dev/agent with normal internet
access) to install and keep building.

## Why this needs one more step before it runs

This sandbox's network policy blocks `repo.packagist.org`, so
`composer install` can't fetch the Laravel framework and its
dependencies from here — I confirmed this is a hard policy denial
(403), not a flaky connection, and there's no safe way around it from
inside this environment. Everything in `app/`, `config/`, `routes/`,
and `database/migrations/` is real, hand-written, syntax-checked PHP
(`php -l` passes on every file) — it's just sitting on top of a
skeleton with no `vendor/` yet.

**First command to run, wherever this ends up with real internet access:**

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Then visit `http://localhost:8000` — that serves the site
(`public/wixwood.html`, same page as the static version of this
project) wired to this app's own `/api/*` routes.

## What's been translated from the Node version

| Node version | Laravel equivalent |
|---|---|
| `server.js` (reviews logic) | `app/Http/Controllers/ReviewController.php` |
| `server.js` (orders + Paystack logic) | `app/Http/Controllers/OrderController.php` + `app/Services/PaystackService.php` |
| SQLite `reviews`/`orders` tables (auto-created) | `database/migrations/*_create_reviews_table.php`, `*_create_orders_table.php` (run via `php artisan migrate`) |
| `requireAdmin` middleware function | `app/Http/Middleware/RequireAdminToken.php` (alias `admin.token`) |
| `catalog.json` | `config/catalog.php` — same role: the only place prices/delivery fees live, still all `null` until WixWood provides real numbers |
| `public/admin.html` | Copied as-is to `public/admin.html` — it only talks to `/api/admin/*`, which is route-compatible, so it needed no changes |
| the site itself | Copied to `public/wixwood.html`, served at `/` via `routes/web.php`; `CONFIG.reviewsApiBase`/`ordersApiBase` default to `""` (same origin) instead of needing a separate deployed URL |

Security properties carried over exactly, not weakened in translation:

- Order totals are computed from `config/catalog.php` server-side;
  a price sent from the browser is never read.
- A Paystack payment is marked paid only after (1) verifying the
  webhook's HMAC-SHA512 signature and (2) an independent server-to-
  server call to Paystack's own Verify Transaction endpoint confirming
  the amount matches. A browser redirect alone proves nothing.
- Admin routes are grouped under `admin.token` middleware the same way
  the Node version gated `/api/admin/*`.

## What still needs doing (same list as before, nothing new)

1. `composer install` (blocked from here, see above)
2. Real prices in `config/catalog.php` — currently all `null`, same
   honest state as the Node version
3. A Paystack account + API keys
4. Deploy somewhere with persistent storage for the SQLite file (or
   switch `DB_CONNECTION` to Postgres/MySQL — the migrations are plain
   Laravel and will work on either without changes)
5. `ADMIN_TOKEN`, `ALLOWED_ORIGINS` in `.env`

## Not yet done in this port (fair to flag)

- No automated tests written for the new controllers (the skeleton
  ships with Pest/PHPUnit already configured — straightforward to add)
- No email notification on new review/order yet (Node version had an
  optional nodemailer hook; Laravel's `Notification` system is the
  natural equivalent, just not wired up here)
- `CLAUDE.md` in this repo has Laravel Boost install instructions from
  the upstream skeleton — worth running once there's internet access,
  it'll generate house-style conventions for whoever continues this
