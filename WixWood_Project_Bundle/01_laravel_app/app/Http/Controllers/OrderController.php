<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    // ---- Public: create an order. The server computes the total from
    // config/catalog.php — the browser only ever sends product ids,
    // quantities, and a delivery zone id. ----
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:50'],
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.phone' => ['required', 'string', 'max:40'],
            'customer.email' => ['nullable', 'email', 'max:160'],
            'delivery.address' => ['required', 'string', 'max:255'],
            'delivery.zone' => ['required', 'string'],
            'delivery.city' => ['nullable', 'string', 'max:80'],
            'delivery.state' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $catalog = config('catalog');
        $zoneId = $validated['delivery']['zone'];
        $zone = $catalog['delivery_zones'][$zoneId] ?? null;

        if (! $zone || $zone['fee'] === null) {
            return response()->json([
                'error' => "That delivery zone isn't configured for online orders yet. Please order via WhatsApp instead.",
            ], 400);
        }

        $subtotal = 0;
        $lineItems = [];

        foreach ($validated['items'] as $raw) {
            $product = $catalog['products'][$raw['id']] ?? null;

            if (! $product) {
                return response()->json([
                    'error' => "\"{$raw['id']}\" isn't an item this site sells online. Please request a quote for it instead.",
                ], 400);
            }
            if ($product['price'] === null) {
                return response()->json([
                    'error' => "\"{$product['name']}\" doesn't have a price configured yet — please request a quote instead.",
                ], 400);
            }

            $lineTotal = $product['price'] * $raw['qty'];
            $subtotal += $lineTotal;
            $lineItems[] = [
                'id' => $raw['id'],
                'name' => $product['name'],
                'qty' => $raw['qty'],
                'unitPrice' => $product['price'],
                'lineTotal' => $lineTotal,
            ];
        }

        $deliveryFee = $zone['fee'];
        $total = $subtotal + $deliveryFee;

        $order = Order::create([
            'id' => 'WW-'.strtoupper(Str::random(10)),
            'customer_name' => $validated['customer']['name'],
            'phone' => $validated['customer']['phone'],
            'email' => $validated['customer']['email'] ?? null,
            'address' => $validated['delivery']['address'],
            'city' => $validated['delivery']['city'] ?? null,
            'state' => $validated['delivery']['state'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'items' => $lineItems,
            'currency' => $catalog['currency'],
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'delivery_zone' => $zoneId,
            'payment_status' => 'unpaid',
            'order_status' => 'pending_payment',
        ]);

        return response()->json([
            'ok' => true,
            'id' => $order->id,
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'total' => $total,
            'currency' => $catalog['currency'],
        ], 201);
    }

    // ---- Public: start Paystack payment for an order ----
    public function payInit(Order $order, PaystackService $paystack)
    {
        if (! $paystack->enabled()) {
            return response()->json([
                'error' => "Online payment isn't configured yet. Please complete this order via WhatsApp.",
            ], 503);
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'This order is already paid.'], 409);
        }

        $email = $order->email ?: "order-{$order->id}@no-email.wixwood";

        $result = $paystack->initialize(
            email: $email,
            amountNaira: $order->total,
            currency: $order->currency,
            orderReferencePrefix: $order->id,
            callbackUrl: config('services.paystack.callback_url'),
            metadata: ['order_id' => $order->id],
        );

        if (! $result) {
            return response()->json(['error' => 'Could not start payment. Please try again or use WhatsApp.'], 502);
        }

        $order->update([
            'payment_provider' => 'paystack',
            'payment_reference' => $result['reference'],
        ]);

        return response()->json([
            'ok' => true,
            'authorization_url' => $result['authorization_url'],
            'reference' => $result['reference'],
        ]);
    }

    // ---- Public: minimal status for the confirmation page to poll ----
    public function status(Order $order)
    {
        return response()->json($order->only(['id', 'order_status', 'payment_status', 'total', 'currency']));
    }

    // ---- Paystack webhook: the only thing allowed to mark an order paid.
    // Verifies the signature, then re-verifies the transaction directly
    // with Paystack — never trusts the webhook payload's amount alone. ----
    public function webhook(Request $request, PaystackService $paystack)
    {
        // Ack immediately; Paystack retries on non-2xx responses.
        $response = response()->json(['ok' => true]);

        if (! $paystack->enabled()) {
            return $response;
        }

        $signature = $request->header('x-paystack-signature');
        if (! $paystack->verifyWebhookSignature($request->getContent(), $signature)) {
            \Log::warning('Paystack webhook: bad signature, ignoring.');

            return $response;
        }

        $event = $request->json()->all();
        if (($event['event'] ?? null) !== 'charge.success') {
            return $response;
        }

        $reference = $event['data']['reference'] ?? null;
        if (! $reference) {
            return $response;
        }

        $verified = $paystack->verify($reference);
        if (! $verified) {
            \Log::warning('Paystack verify did not confirm success', ['reference' => $reference]);

            return $response;
        }

        $order = Order::where('payment_reference', $reference)->first();
        if (! $order) {
            \Log::warning('No order matches Paystack reference', ['reference' => $reference]);

            return $response;
        }
        if ($order->payment_status === 'paid') {
            return $response; // already processed, avoid double-handling
        }

        if ($verified['amount'] !== $order->total * 100 || $verified['currency'] !== $order->currency) {
            \Log::error('Amount/currency mismatch — NOT marking paid.', [
                'order_id' => $order->id, 'got' => $verified['amount'], 'expected' => $order->total * 100,
            ]);

            return $response;
        }

        $order->update(['payment_status' => 'paid', 'order_status' => 'paid']);

        return $response;
    }

    // ---- Admin ----
    public function adminIndex(Request $request)
    {
        $query = Order::query()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('order_status', $request->query('status'));
        }

        return response()->json(['orders' => $query->get()]);
    }

    public function adminShow(Order $order)
    {
        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::NEXT_STATUSES)],
        ]);

        $order->update(['order_status' => $validated['status']]);

        return response()->json(['ok' => true]);
    }

    // Manual "mark paid" for an offline/bank-transfer payment WixWood
    // confirmed themselves — kept separate from a Paystack-verified
    // payment so the two are never confused in the data.
    public function markPaidManually(Order $order)
    {
        $order->update([
            'payment_status' => 'paid',
            'order_status' => 'paid',
            'payment_provider' => 'manual',
        ]);

        return response()->json(['ok' => true]);
    }

    // ---- Public: prices/zones the frontend needs to render accurate
    // Add to Cart buttons and delivery options ----
    public function publicConfig(PaystackService $paystack)
    {
        $catalog = config('catalog');

        return response()->json([
            'currency' => $catalog['currency'],
            'onlinePaymentEnabled' => $paystack->enabled(),
            'paystackPublicKey' => config('services.paystack.public_key'),
            'products' => $catalog['products'],
            'deliveryZones' => $catalog['delivery_zones'],
        ]);
    }
}
