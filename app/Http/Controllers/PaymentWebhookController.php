<?php

namespace App\Http\Controllers;

use App\Models\FamilySubmission;
use App\Support\PayHook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Terima notifikasi webhook dari PayHook (cekbayar.com).
     *
     * Payload: { event, invoice: { invoice_number, status, unique_amount, paid_at }, ... }
     * Header:  X-Webhook-Signature = HMAC-SHA256(raw_body, webhook_secret)
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Webhook-Signature');

        $payhook = new PayHook();

        if (! $payhook->verifySignature($rawBody, $signature)) {
            Log::warning('PayHook webhook signature invalid', ['ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawBody, true) ?: [];
        $invoiceNumber = data_get($payload, 'invoice.invoice_number');
        $invoiceStatus = data_get($payload, 'invoice.status');

        if (! $invoiceNumber) {
            return response()->json(['success' => false, 'message' => 'Missing invoice_number'], 422);
        }

        $submission = FamilySubmission::where('payment_invoice_number', $invoiceNumber)->first();

        if (! $submission) {
            Log::info('PayHook webhook: invoice tidak dikenal', ['invoice_number' => $invoiceNumber]);

            // 200 supaya PayHook tidak retry terus untuk invoice yang bukan milik kita.
            return response()->json(['success' => true, 'message' => 'Ignored']);
        }

        if ($invoiceStatus === 'paid' && $submission->payment_status !== 'paid') {
            $paidAt = data_get($payload, 'invoice.paid_at') ?: data_get($payload, 'transaction.paid_at');

            $submission->update([
                'payment_status' => 'paid',
                'payment_paid_at' => $paidAt ? \Illuminate\Support\Carbon::parse($paidAt) : now(),
            ]);

            // Catat transaksi income + tandai verified (idempotent).
            $submission->approveAndRecord();

            Log::info('PayHook webhook: pembayaran lunas', [
                'invoice_number' => $invoiceNumber,
                'reference_code' => $submission->reference_code,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
