// WixWood Crafts — Backend
//
// Two jobs, same small service: (1) let customers submit a review that
// WixWood moderates before it's shown publicly, and (2) let a customer
// order one of the small number of fixed-price items and pay for it
// online via Paystack, with the order total computed and verified
// server-side — never trusted from the browser. Custom/quote furniture
// still goes through WhatsApp/email, same as before; this does not
// become a full inventory or fulfilment system.
//
// Setup: copy .env.example to .env, fill in ADMIN_TOKEN, ALLOWED_ORIGINS,
// and (if you want online payment live) PAYSTACK_SECRET_KEY,
// PAYSTACK_PUBLIC_KEY, PAYSTACK_CALLBACK_URL. Fill real prices into
// catalog.json — until you do, every item stays request-a-quote-only,
// exactly as the site behaves today. `npm install && npm start`. The
// SQLite database file and its tables are created automatically on
// first run — nothing to migrate or seed by hand.

require("dotenv").config();
const express = require("express");
const cors = require("cors");
const path = require("path");
const fs = require("fs");
const crypto = require("crypto");
const rateLimit = require("express-rate-limit");
const Database = require("better-sqlite3");

const PORT = process.env.PORT || 3000;
const ADMIN_TOKEN = process.env.ADMIN_TOKEN || "";
const ALLOWED_ORIGINS = (process.env.ALLOWED_ORIGINS || "")
  .split(",").map(s => s.trim()).filter(Boolean);
const PAYSTACK_SECRET_KEY = process.env.PAYSTACK_SECRET_KEY || "";
const PAYSTACK_PUBLIC_KEY = process.env.PAYSTACK_PUBLIC_KEY || "";
const PAYSTACK_CALLBACK_URL = process.env.PAYSTACK_CALLBACK_URL || "";

if (!ADMIN_TOKEN) {
  console.warn("[wixwood] WARNING: ADMIN_TOKEN is not set. The admin page " +
    "will refuse every request until you set it in .env.");
}
if (!PAYSTACK_SECRET_KEY) {
  console.warn("[wixwood] NOTE: PAYSTACK_SECRET_KEY is not set. Online " +
    "payment is disabled — orders can still be created, but checkout " +
    "will say online payment isn't available yet.");
}

// ---------------------------------------------------------------
// Catalog: the ONLY source of truth for prices. The frontend sends a
// product id and quantity, never a price — so nothing from the browser
// can change an order's total.
// ---------------------------------------------------------------
function loadCatalog() {
  const raw = fs.readFileSync(path.join(__dirname, "catalog.json"), "utf8");
  return JSON.parse(raw);
}

// ---------------------------------------------------------------
// Database (auto-created, no manual migration step)
// ---------------------------------------------------------------
const db = new Database(path.join(__dirname, "reviews.db"));
db.pragma("journal_mode = WAL");
db.exec(`
  CREATE TABLE IF NOT EXISTS reviews (
    id TEXT PRIMARY KEY,
    rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
    name TEXT NOT NULL,
    piece TEXT,
    comment TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','approved','rejected')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
  );
  CREATE INDEX IF NOT EXISTS idx_reviews_status ON reviews(status);

  CREATE TABLE IF NOT EXISTS orders (
    id TEXT PRIMARY KEY,
    customer_name TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT,
    address TEXT NOT NULL,
    city TEXT,
    state TEXT,
    notes TEXT,
    items_json TEXT NOT NULL,
    currency TEXT NOT NULL DEFAULT 'NGN',
    subtotal INTEGER NOT NULL,
    delivery_fee INTEGER NOT NULL,
    total INTEGER NOT NULL,
    delivery_zone TEXT,
    payment_provider TEXT,
    payment_reference TEXT,
    payment_status TEXT NOT NULL DEFAULT 'unpaid' CHECK(payment_status IN ('unpaid','paid','failed')),
    order_status TEXT NOT NULL DEFAULT 'pending_payment'
      CHECK(order_status IN ('pending_payment','paid','processing','in_production','ready_for_delivery','delivered','cancelled')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
  );
  CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(order_status);
  CREATE INDEX IF NOT EXISTS idx_orders_payref ON orders(payment_reference);
`);

// ---------------------------------------------------------------
// Optional email notification — silently disabled if unconfigured
// ---------------------------------------------------------------
let mailer = null;
if (process.env.SMTP_HOST && process.env.SMTP_USER && process.env.NOTIFY_EMAIL_TO) {
  const nodemailer = require("nodemailer");
  mailer = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: Number(process.env.SMTP_PORT || 587),
    secure: Number(process.env.SMTP_PORT) === 465,
    auth: { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS },
  });
}

async function notifyNewReview(review) {
  if (!mailer) return;
  try {
    await mailer.sendMail({
      from: process.env.NOTIFY_EMAIL_FROM || process.env.SMTP_USER,
      to: process.env.NOTIFY_EMAIL_TO,
      subject: `New WixWood review awaiting approval (${review.rating}/5)`,
      text: `${review.name} left a ${review.rating}/5 review` +
        (review.piece ? ` for "${review.piece}"` : "") + `:\n\n${review.comment}\n\n` +
        `Approve or reject it on the admin page.`,
    });
  } catch (err) {
    console.error("[wixwood-reviews] Email notification failed:", err.message);
  }
}

// ---------------------------------------------------------------
// App
// ---------------------------------------------------------------
const app = express();
// Keep the raw request body around for Paystack webhook signature
// verification, in addition to the normal parsed JSON body.
app.use(express.json({ limit: "20kb", verify: (req, res, buf) => { req.rawBody = buf; } }));
app.use(cors({
  origin: ALLOWED_ORIGINS.length ? ALLOWED_ORIGINS : true,
  methods: ["GET", "POST", "DELETE"],
}));
app.use(express.static(path.join(__dirname, "public")));

// Basic spam/abuse protection: rate limit + honeypot field.
const submitLimiter = rateLimit({
  windowMs: 60 * 60 * 1000,
  max: 5,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: "Too many submissions from this connection. Please try again later." },
});

function requireAdmin(req, res, next) {
  const token = req.get("x-admin-token") || req.query.token;
  if (!ADMIN_TOKEN || token !== ADMIN_TOKEN) {
    return res.status(401).json({ error: "Invalid or missing admin token." });
  }
  next();
}

// ---- Public: list approved reviews (what the live site displays) ----
app.get("/api/reviews", (req, res) => {
  const rows = db.prepare(
    `SELECT id, rating, name, piece, comment, created_at
     FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 100`
  ).all();
  res.json({ reviews: rows });
});

// ---- Public: submit a new review (goes in as "pending") ----
app.post("/api/reviews", submitLimiter, (req, res) => {
  const { rating, name, piece, comment, _hp } = req.body || {};

  // Honeypot: a hidden field real customers never fill in.
  if (_hp) return res.status(200).json({ ok: true }); // pretend success, drop silently

  const r = Number(rating);
  if (!Number.isInteger(r) || r < 1 || r > 5) {
    return res.status(400).json({ error: "Rating must be a whole number from 1 to 5." });
  }
  const cleanName = String(name || "Anonymous").trim().slice(0, 80) || "Anonymous";
  const cleanPiece = piece ? String(piece).trim().slice(0, 120) : null;
  const cleanComment = String(comment || "").trim().slice(0, 2000);
  if (cleanComment.length < 5) {
    return res.status(400).json({ error: "Please write a few words about your experience." });
  }

  const id = crypto.randomUUID();
  const now = new Date().toISOString();
  db.prepare(
    `INSERT INTO reviews (id, rating, name, piece, comment, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)`
  ).run(id, r, cleanName, cleanPiece, cleanComment, now, now);

  const review = { id, rating: r, name: cleanName, piece: cleanPiece, comment: cleanComment };
  notifyNewReview(review);

  res.status(201).json({ ok: true, id });
});

// ---- Admin: view all reviews, any status ----
app.get("/api/admin/reviews", requireAdmin, (req, res) => {
  const status = ["pending", "approved", "rejected"].includes(req.query.status)
    ? req.query.status : null;
  const rows = status
    ? db.prepare(`SELECT * FROM reviews WHERE status = ? ORDER BY created_at DESC`).all(status)
    : db.prepare(`SELECT * FROM reviews ORDER BY created_at DESC`).all();
  res.json({ reviews: rows });
});

// ---- Admin: approve / reject / delete ----
app.post("/api/admin/reviews/:id/approve", requireAdmin, (req, res) => {
  const now = new Date().toISOString();
  const info = db.prepare(`UPDATE reviews SET status='approved', updated_at=? WHERE id=?`).run(now, req.params.id);
  if (!info.changes) return res.status(404).json({ error: "Review not found." });
  res.json({ ok: true });
});

app.post("/api/admin/reviews/:id/reject", requireAdmin, (req, res) => {
  const now = new Date().toISOString();
  const info = db.prepare(`UPDATE reviews SET status='rejected', updated_at=? WHERE id=?`).run(now, req.params.id);
  if (!info.changes) return res.status(404).json({ error: "Review not found." });
  res.json({ ok: true });
});

app.delete("/api/admin/reviews/:id", requireAdmin, (req, res) => {
  const info = db.prepare(`DELETE FROM reviews WHERE id=?`).run(req.params.id);
  if (!info.changes) return res.status(404).json({ error: "Review not found." });
  res.json({ ok: true });
});

// =================================================================
// ORDERS — fixed-price catalog items only. Custom/quote furniture
// still goes through WhatsApp/email, same as before.
// =================================================================

const orderLimiter = rateLimit({
  windowMs: 60 * 60 * 1000, max: 10, standardHeaders: true, legacyHeaders: false,
  message: { error: "Too many orders from this connection. Please try again later, or message WixWood directly." },
});

// ---- Public: create an order. Server computes the total — the
// browser only ever sends product ids, quantities, and a zone id. ----
app.post("/api/orders", orderLimiter, (req, res) => {
  const { items, customer, delivery, notes } = req.body || {};
  const catalog = loadCatalog();

  if (!Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: "Cart is empty." });
  }
  if (!customer || !String(customer.name || "").trim() || !String(customer.phone || "").trim()) {
    return res.status(400).json({ error: "Name and phone are required." });
  }
  if (!delivery || !String(delivery.address || "").trim() || !String(delivery.zone || "").trim()) {
    return res.status(400).json({ error: "Delivery address and zone are required." });
  }

  const zone = catalog.deliveryZones[delivery.zone];
  if (!zone || zone.fee == null) {
    return res.status(400).json({ error: "That delivery zone isn't configured for online orders yet. Please order via WhatsApp instead." });
  }

  let subtotal = 0;
  const lineItems = [];
  for (const raw of items) {
    const id = String(raw && raw.id || "");
    const qty = Number(raw && raw.qty);
    if (!Number.isInteger(qty) || qty < 1 || qty > 50) {
      return res.status(400).json({ error: `Invalid quantity for ${id || "an item"}.` });
    }
    const product = catalog.products[id];
    if (!product) {
      return res.status(400).json({ error: `"${id}" isn't an item this site sells online. Please request a quote for it instead.` });
    }
    if (product.price == null) {
      return res.status(400).json({ error: `"${product.name}" doesn't have a price configured yet — please request a quote instead.` });
    }
    subtotal += product.price * qty;
    lineItems.push({ id, name: product.name, qty, unitPrice: product.price, lineTotal: product.price * qty });
  }

  const deliveryFee = zone.fee;
  const total = subtotal + deliveryFee;
  const id = "WW-" + Date.now().toString(36).toUpperCase() + "-" + crypto.randomBytes(2).toString("hex").toUpperCase();
  const now = new Date().toISOString();

  db.prepare(`
    INSERT INTO orders (id, customer_name, phone, email, address, city, state, notes,
      items_json, currency, subtotal, delivery_fee, total, delivery_zone,
      payment_status, order_status, created_at, updated_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'unpaid','pending_payment', ?, ?)
  `).run(
    id, String(customer.name).trim(), String(customer.phone).trim(),
    customer.email ? String(customer.email).trim() : null,
    String(delivery.address).trim(), delivery.city ? String(delivery.city).trim() : null,
    delivery.state ? String(delivery.state).trim() : null,
    notes ? String(notes).trim().slice(0, 1000) : null,
    JSON.stringify(lineItems), catalog.currency || "NGN",
    subtotal, deliveryFee, total, delivery.zone, now, now
  );

  res.status(201).json({ ok: true, id, subtotal, deliveryFee, total, currency: catalog.currency || "NGN" });
});

// ---- Public: start Paystack payment for an order ----
app.post("/api/orders/:id/pay/paystack/init", async (req, res) => {
  if (!PAYSTACK_SECRET_KEY) {
    return res.status(503).json({ error: "Online payment isn't configured yet. Please complete this order via WhatsApp." });
  }
  const order = db.prepare(`SELECT * FROM orders WHERE id = ?`).get(req.params.id);
  if (!order) return res.status(404).json({ error: "Order not found." });
  if (order.payment_status === "paid") return res.status(409).json({ error: "This order is already paid." });

  const email = order.email || `order-${order.id}@no-email.wixwood`;
  try {
    const r = await fetch("https://api.paystack.co/transaction/initialize", {
      method: "POST",
      headers: { Authorization: `Bearer ${PAYSTACK_SECRET_KEY}`, "Content-Type": "application/json" },
      body: JSON.stringify({
        email,
        amount: order.total * 100, // kobo
        currency: order.currency,
        reference: order.id + "-" + Date.now(), // unique per attempt, so a retry after a failed payment works
        callback_url: PAYSTACK_CALLBACK_URL || undefined,
        metadata: { order_id: order.id },
      }),
    });
    const data = await r.json();
    if (!r.ok || !data.status) {
      console.error("[wixwood] Paystack init failed:", data);
      return res.status(502).json({ error: "Could not start payment. Please try again or use WhatsApp." });
    }
    db.prepare(`UPDATE orders SET payment_provider='paystack', payment_reference=?, updated_at=? WHERE id=?`)
      .run(data.data.reference, new Date().toISOString(), order.id);
    res.json({ ok: true, authorization_url: data.data.authorization_url, reference: data.data.reference });
  } catch (err) {
    console.error("[wixwood] Paystack init error:", err.message);
    res.status(502).json({ error: "Could not reach Paystack. Please try again shortly." });
  }
});

// ---- Paystack webhook: the only thing allowed to mark an order paid.
// Verifies the signature, then re-verifies the transaction directly
// with Paystack (never trusts the webhook payload's amount on its own). ----
app.post("/api/webhooks/paystack", async (req, res) => {
  res.status(200).end(); // ack immediately; Paystack retries on non-2xx
  if (!PAYSTACK_SECRET_KEY || !req.rawBody) return;

  const signature = req.get("x-paystack-signature") || "";
  const expected = crypto.createHmac("sha512", PAYSTACK_SECRET_KEY).update(req.rawBody).digest("hex");
  if (signature !== expected) {
    console.warn("[wixwood] Paystack webhook: bad signature, ignoring.");
    return;
  }

  const event = req.body;
  if (!event || event.event !== "charge.success") return;
  const reference = event.data && event.data.reference;
  if (!reference) return;

  try {
    const r = await fetch(`https://api.paystack.co/transaction/verify/${encodeURIComponent(reference)}`, {
      headers: { Authorization: `Bearer ${PAYSTACK_SECRET_KEY}` },
    });
    const verify = await r.json();
    if (!verify.status || verify.data.status !== "success") {
      console.warn("[wixwood] Paystack verify did not confirm success for", reference);
      return;
    }
    const order = db.prepare(`SELECT * FROM orders WHERE payment_reference = ?`).get(reference);
    if (!order) { console.warn("[wixwood] No order matches reference", reference); return; }
    if (order.payment_status === "paid") return; // already processed, avoid double-handling

    if (verify.data.amount !== order.total * 100 || verify.data.currency !== order.currency) {
      console.error("[wixwood] Amount/currency mismatch for", order.id, "— NOT marking paid.", verify.data.amount, order.total * 100);
      return;
    }

    const now = new Date().toISOString();
    db.prepare(`UPDATE orders SET payment_status='paid', order_status='paid', updated_at=? WHERE id=?`).run(now, order.id);
    console.log("[wixwood] Order", order.id, "marked paid via Paystack.");
  } catch (err) {
    console.error("[wixwood] Paystack webhook verify error:", err.message);
  }
});

// ---- Public: minimal order status for the confirmation page to poll
// (no customer PII returned) ----
app.get("/api/orders/:id/status", (req, res) => {
  const order = db.prepare(
    `SELECT id, order_status, payment_status, total, currency FROM orders WHERE id = ?`
  ).get(req.params.id);
  if (!order) return res.status(404).json({ error: "Order not found." });
  res.json(order);
});

// ---- Admin: list / view / update orders ----
app.get("/api/admin/orders", requireAdmin, (req, res) => {
  const status = req.query.status;
  const rows = status
    ? db.prepare(`SELECT * FROM orders WHERE order_status = ? ORDER BY created_at DESC`).all(status)
    : db.prepare(`SELECT * FROM orders ORDER BY created_at DESC`).all();
  res.json({ orders: rows.map(o => ({ ...o, items: JSON.parse(o.items_json) })) });
});

app.get("/api/admin/orders/:id", requireAdmin, (req, res) => {
  const o = db.prepare(`SELECT * FROM orders WHERE id = ?`).get(req.params.id);
  if (!o) return res.status(404).json({ error: "Order not found." });
  res.json({ ...o, items: JSON.parse(o.items_json) });
});

const NEXT_STATUSES = ["processing", "in_production", "ready_for_delivery", "delivered", "cancelled"];
app.post("/api/admin/orders/:id/status", requireAdmin, (req, res) => {
  const status = req.body && req.body.status;
  if (!NEXT_STATUSES.includes(status)) {
    return res.status(400).json({ error: "status must be one of: " + NEXT_STATUSES.join(", ") });
  }
  const now = new Date().toISOString();
  const info = db.prepare(`UPDATE orders SET order_status=?, updated_at=? WHERE id=?`).run(status, now, req.params.id);
  if (!info.changes) return res.status(404).json({ error: "Order not found." });
  res.json({ ok: true });
});

// Manual "mark paid" for an offline/bank-transfer payment WixWood confirmed
// themselves — kept separate and clearly labelled so it's never confused
// with a Paystack-verified payment.
app.post("/api/admin/orders/:id/mark-paid-manually", requireAdmin, (req, res) => {
  const now = new Date().toISOString();
  const info = db.prepare(
    `UPDATE orders SET payment_status='paid', order_status='paid', payment_provider='manual', updated_at=? WHERE id=?`
  ).run(now, req.params.id);
  if (!info.changes) return res.status(404).json({ error: "Order not found." });
  res.json({ ok: true });
});

app.get("/api/config/public", (req, res) => {
  const catalog = loadCatalog();
  res.json({
    currency: catalog.currency || "NGN",
    onlinePaymentEnabled: Boolean(PAYSTACK_SECRET_KEY),
    paystackPublicKey: PAYSTACK_PUBLIC_KEY || null,
    products: catalog.products,
    deliveryZones: catalog.deliveryZones,
  });
});

app.get("/healthz", (req, res) => res.json({ ok: true }));

app.listen(PORT, () => {
  console.log(`[wixwood-reviews] listening on port ${PORT}`);
});
