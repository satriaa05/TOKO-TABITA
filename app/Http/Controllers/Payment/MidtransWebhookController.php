<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $service): Response
    {
        try {
            $service->handleWebhook((array) $request->json()->all());
        } catch (\Throwable $e) {
            // Tetap balas 200 agar Midtrans tidak retry terus-menerus;
            // kegagalan tercatat di log untuk ditindaklanjuti Admin.
            Log::error('Gagal memproses webhook Midtrans: '.$e->getMessage());
        }

        return response('OK', 200);
    }
}
