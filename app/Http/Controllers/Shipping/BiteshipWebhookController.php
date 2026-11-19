<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Services\BiteshipService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use App\Models\PaymentWebhookLog;
use Illuminate\Support\Str;

class BiteshipWebhookController extends Controller
{
    public function __invoke(Request $request, BiteshipService $biteship): Response
    {
        $payload = (array) $request->json()->all();

        if (! $biteship->verifyWebhook($request)) {
            $this->log($payload, 'invalid_signature', false, 'Signature tidak valid.');
            Log::warning('Webhook Biteship ditolak: signature tidak valid.');

            return response('OK', 200);
        }

        try {
            $result = $biteship->handleWebhook($payload);

            if (! ($result['ok'] ?? false)) {
                $this->log($payload, 'failed', true, (string) ($result['reason'] ?? 'Webhook Biteship tidak diproses.'));
            }
        } catch (\Throwable $e) {
            $this->log($payload, 'failed', false, $e->getMessage());
            Log::error('Gagal memproses webhook Biteship: '.$e->getMessage());
        }

        return response('OK', 200);
    }

    /** @param  array<string, mixed>  $payload */
    private function log(array $payload, string $status, bool $signatureValid, string $error): void
    {
        try {
            PaymentWebhookLog::create([
                'provider' => 'biteship',
                'event_id' => 'biteship:'.hash('sha256', (string) json_encode($payload)).':'.Str::random(8),
                'transaction_reference' => (string) ($payload['order_id'] ?? ($payload['order']['id'] ?? '')),
                'sanitized_payload' => $payload,
                'signature_valid' => $signatureValid,
                'processing_status' => $status,
                'error_message' => $error,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menulis log webhook Biteship: '.$e->getMessage());
        }
    }
}
