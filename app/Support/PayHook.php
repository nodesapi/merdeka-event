<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client tipis untuk PayHook Merchant API (cekbayar.com).
 *
 * Konfigurasi (base_url, api_key, webhook_secret, channel_type) dibaca dari
 * SiteSetting. Dipakai untuk membuat invoice QRIS dinamis dan memverifikasi
 * signature webhook pembayaran yang masuk.
 */
class PayHook
{
    protected SiteSetting $settings;

    public function __construct(?SiteSetting $settings = null)
    {
        $this->settings = $settings ?? SiteSetting::current();
    }

    /**
     * Apakah integrasi PayHook aktif & terkonfigurasi minimal (base_url + api_key).
     */
    public function enabled(): bool
    {
        return (bool) $this->settings->payhook_enabled
            && filled($this->settings->payhook_base_url)
            && filled($this->settings->payhook_api_key);
    }

    /**
     * Base URL API tanpa trailing slash, mis. https://cekbayar.com/api/v1
     */
    protected function baseUrl(): string
    {
        return rtrim((string) $this->settings->payhook_base_url, '/');
    }

    protected function channelType(): string
    {
        return $this->settings->payhook_channel_type ?: 'qris';
    }

    /**
     * Buat invoice QRIS dinamis di PayHook.
     *
     * @return array{invoice_number:string, pay_amount:float, qris_string:?string, qris_svg:?string, expires_at:?string, status:string}|null
     *         Null bila gagal / tidak aktif.
     */
    public function createQrisInvoice(
        float $amount,
        string $customerName,
        string $externalId,
        ?string $phone = null,
        string $description = 'Iuran warga',
        int $expiresInMinutes = 60
    ): ?array {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withToken((string) $this->settings->payhook_api_key)
                ->withHeaders(['Idempotency-Key' => $externalId])
                ->timeout(20)
                ->post($this->baseUrl() . '/invoices', array_filter([
                    'amount' => $amount,
                    'customer_name' => $customerName,
                    'customer_phone' => $phone,
                    'external_id' => $externalId,
                    'description' => $description,
                    'channel_type' => $this->channelType(),
                    'expires_in_minutes' => $expiresInMinutes,
                ], fn ($v) => $v !== null && $v !== ''));

            if (! $response->successful()) {
                Log::warning('PayHook createInvoice gagal', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'external_id' => $externalId,
                ]);

                return null;
            }

            $data = $response->json('data');

            if (! is_array($data) || empty($data['invoice_number'])) {
                Log::warning('PayHook createInvoice respons tidak valid', [
                    'body' => $response->body(),
                    'external_id' => $externalId,
                ]);

                return null;
            }

            $instruction = $data['payment_instruction'] ?? [];

            return [
                'invoice_number' => (string) $data['invoice_number'],
                'pay_amount' => (float) ($data['pay_amount'] ?? $amount),
                'qris_string' => $instruction['qris_string'] ?? null,
                'qris_svg' => $instruction['qris_svg'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'status' => (string) ($data['status'] ?? 'pending'),
            ];
        } catch (Throwable $e) {
            Log::error('PayHook createInvoice exception', [
                'message' => $e->getMessage(),
                'external_id' => $externalId,
            ]);

            return null;
        }
    }

    /**
     * Verifikasi signature webhook (HMAC-SHA256 dari raw body memakai webhook_secret).
     */
    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) $this->settings->payhook_webhook_secret;

        if ($secret === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
