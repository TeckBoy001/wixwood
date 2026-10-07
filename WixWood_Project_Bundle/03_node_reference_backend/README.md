# WixWood Backend

One small service, two jobs:

1. **Reviews** — customer submits, WixWood approves/rejects, only
   approved ones show on the site.
2. **Orders + payment** — for the handful of items that have a real,
   fixed price (currently: stool, cup, bowl, serving board), a
   customer can check out and pay online via Paystack, with the total
   calculated and verified **on this server**, never trusted from the
   browser. Custom/made-to-order furniture (dining tables, epoxy
   tables, custom interiors, etc.) still goes through the existing
   WhatsApp/email quote flow — those don't have fixed prices and
   aren't meant to.

## What's in here

- `server.js` — the whole API (Express + SQLite via better-sqlite3)
- `catalog.json` — **the only place prices and delivery fees live.**
  Every price starts as `null` (quote-only) until you fill in a real
  number. This file is never trusted from the browser, so it's the
  one truthful source for what something costs.
- `public/admin.html` — one page, two tabs: Reviews (approve/reject)
  and Orders (see what's been ordered, update its status, or mark it
  paid manually for an offline/bank-transfer payment)
- `.env.example` — the configuration you need to fill in
- The database (`reviews.db`) is created automatically the first time
  the server starts. There is no migration step, no seed script to
  run, nothing to set up in a database dashboard.

## Deploy it (Render.com free tier — simplest path)

1. Push this folder to its own GitHub repo (or a subfolder of an
   existing one).
2. On [render.com](https://render.com), click **New → Web Service**,
   connect the repo.
3. Build command: `npm install`. Start command: `npm start`.
4. Under **Environment**, add the variables from `.env.example`:
   - `ADMIN_TOKEN` — generate one with `openssl rand -hex 24`, or any
     long random string. This is your moderation password.
   - `ALLOWED_ORIGINS` — the exact URL(s) of the live WixWood site.
   - Leave the `SMTP_*` ones blank unless you want email alerts (see
     below).
5. Under Render's **Disks**, add a small persistent disk (1 GB is
   plenty) mounted at `/opt/render/project/src` — without this the
   SQLite file resets every time Render restarts the service. (Any
   host that gives you a persistent disk works the same way; a
   serverless platform without persistent storage will not, unless
   you swap SQLite for a hosted database — see "if you outgrow SQLite"
   below.)
6. Deploy. Render gives you a URL like
   `https://wixwood-reviews.onrender.com`.

Railway and Fly.io work the same way (persistent volume + those three
env vars) if you'd rather use one of those.

## Wire it to the website

In the WixWood site's `index.html`, find `CONFIG.reviewsApiBase` **and**
`CONFIG.ordersApiBase` near the top of the `<script>` block and paste
the deployed URL into both (same backend, same URL), e.g.:

```js
reviewsApiBase: "https://wixwood-reviews.onrender.com",
ordersApiBase:  "https://wixwood-reviews.onrender.com",
```

Until these are set, the site works exactly as it did before this
backend existed — reviews go over WhatsApp, and checkout shows the
order summary with WhatsApp/email/manual-payment-link options.
Nothing breaks in the meantime, and nothing breaks if the backend
goes down later either (checkout automatically falls back).

## Set real prices

Edit `catalog.json` on the server and fill in a Naira number for each
`"price": null`. Only `hardwood-stool`, `handled-cup`, `serving-bowl`,
and `serving-board` are in there — those are the only products
currently marked `priceType:"fixed"` on the site; everything else
(dining tables, epoxy tables, custom interiors, etc.) is intentionally
quote-only and isn't in `catalog.json` at all. Fill in `deliveryZones`
the same way. The website picks these up automatically — you don't
need to touch `index.html` for a price change, just redeploy with the
updated `catalog.json` (or edit it directly on the host if it supports
that).

## Set up Paystack

1. Create a Paystack account, get your **Secret Key** and **Public
   Key** from Settings → API Keys & Webhooks (use the `sk_test_...` /
   `pk_test_...` pair first).
2. Put them in `.env` as `PAYSTACK_SECRET_KEY` and `PAYSTACK_PUBLIC_KEY`.
3. In the same Paystack dashboard page, set the **Webhook URL** to
   `https://<your-backend-url>/api/webhooks/paystack`. This is what
   actually marks an order paid — Paystack calls it directly,
   server-to-server, after independently verifying the payment. A
   "payment successful" message in the customer's browser is never
   trusted on its own.
4. Optionally set `PAYSTACK_CALLBACK_URL` to a page on the site you
   want the customer's browser sent back to after paying — this is
   just where they land, it has no bearing on whether the order is
   actually marked paid.
5. Switch to live keys (`sk_live_...` / `pk_live_...`) once you're
   ready to accept real payments.

Until `PAYSTACK_SECRET_KEY` is set, orders are still created and
visible in the admin page, and checkout tells the customer online
payment isn't ready yet — it offers WhatsApp/email instead, same as
the current site.

## Moderating reviews & managing orders

Go to `https://<your-backend-url>/admin.html`, enter the `ADMIN_TOKEN`
you set. Two tabs:

- **Reviews** — Pending / Approved / Rejected, with Approve / Reject /
  Delete. Approved reviews appear on the live site within a few
  seconds.
- **Orders** — every order, with its items, customer, delivery info,
  and total. You can move an order through its fulfilment stages
  (Processing → In production → Ready for delivery → Delivered /
  Cancelled), and there's a "Mark paid (offline)" button for the rare
  case someone pays by bank transfer instead of Paystack — it's kept
  clearly separate from a real Paystack-verified payment in the
  database.

## Email notifications (optional)

Fill in the `SMTP_*` and `NOTIFY_EMAIL_TO` values in `.env` to get an
email every time a new review comes in. Any SMTP provider works
(Gmail with an app password, SendGrid, Mailgun, etc.). Leave them
blank to skip this — you'd just check the admin page periodically
instead.

## Spam / abuse protection

- Reviews: a hidden honeypot field silently drops bot submissions;
  5 submissions/hour/IP; every submission lands as "pending" —
  nothing reaches the public site without a human clicking Approve.
- Orders: 10/hour/IP. Prices and delivery fees always come from
  `catalog.json` on this server — whatever the browser sends for an
  item's price is ignored outright.

## Testing payment safely

Use Paystack's **test** keys (`sk_test_...`) and their [test
cards](https://paystack.com/docs/payments/test-payments/) — they
simulate a full charge without moving real money. A transaction only
ever gets marked paid here after two independent checks: Paystack's
webhook signature, and a direct server-to-server call back to
Paystack's own Verify Transaction endpoint to confirm the amount
matches the order exactly. A browser redirect by itself — including a
customer closing the tab and claiming success — can never mark an
order paid.

## If you outgrow SQLite

SQLite comfortably handles a small business's review volume for
years. If WixWood ever needs multiple servers running at once or a
managed database, swap `better-sqlite3` for `pg` (Postgres) — the
SQL in `server.js` is plain enough to port in an afternoon. Not
something to worry about now.
