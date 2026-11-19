<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Services\BiteshipService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BiteshipWebhookController extends Controller
{
    public function __invoke(Request $request, BiteshipService $biteship): Response
    {
        if (! $biteship->verifyWebhook($request)) {
            Log::warning('Webhook Biteship ditolak: signature tidak valid.');

            return response('OK', 200);
        }

        try {
            $biteship->handleWebhook((array) $request->json()->all());
        } catch (\Throwable $e) {
            Log::error('Gagal memproses webhook Biteship: '.$e->getMessage());
        }

        return response('OK', 200);
    }
}
