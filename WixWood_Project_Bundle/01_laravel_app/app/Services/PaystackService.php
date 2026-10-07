<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    public function enabled(): bool
    {
        return (bool) config('services.paystack.secret_key');
    }

    protected function secretKey(): string
    {
        return config('services.paystack.secret_key');
    }

    /**
     * Start a Paystack transaction for an order. Amount is taken from the
     * order record (already computed server-side from config/catalog.php)
     * — never recalculated from anything the browser sends here.
     *
     * @return array{authorization_url:string, reference:string}|null
     */
    public function initialize(string $email, int $amountNaira, string $currency, string $orderReferencePrefix, ?string $callbackUrl, array $metadata): ?array
    {
        $reference = $orderReferencePrefix.'-'.now()->timestamp;

        $response = Http::withToken($this->secretKey())
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email,
                'amount' => $amountNaira * 100, // kobo
                'currency' => $currency,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::error('Paystack initialize failed', ['body' => $response->body()]);

            return null;
        }

        return [
            'authorization_url' => $response->json('data.authorization_url'),
            'reference' => $response->json('data.reference'),
        ];
    }

    /**
     * Verify a transaction directly with Paystack — never trust a webhook
     * payload or a browser redirect on its own. Returns the verification
     * data array, or null if Paystack didn't confirm success.
     */
    public function verify(string $reference): ?array
    {
        $response = Http::withToken($this->secretKey())
            ->get("https://api.paystack.co/transaction/verify/".urlencode($reference));

        if (! $response->successful() || ! $response->json('status')) {
            return null;
        }

        $data = $response->json('data');

        return ($data['status'] ?? null) === 'success' ? $data : null;
    }

    /**
     * Validate the x-paystack-signature header against the raw request
     * body. This is what proves a webhook call actually came from
     * Paystack and wasn't forged.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (! $signatureHeader) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $this->secretKey());

        return hash_equals($expected, $signatureHeader);
    }
}
