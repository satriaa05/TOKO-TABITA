<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentProofStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Models\PaymentWebhookLog;
use App\Models\Sale;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitoring pembayaran untuk Admin.
 *
 * Admin boleh menyetujui pembayaran MANUAL setelah memeriksa bukti.
 * Transaksi otomatis (Midtrans) TIDAK boleh di-PAID-kan manual dari sini.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(): View
    {
        $sales = Sale::query()
            ->with(['user', 'paymentTransactions.channel', 'paymentProofs'])
            ->orderByRaw("CASE WHEN payment_status = 'unpaid' THEN 0 WHEN payment_status = 'pending' THEN 1 ELSE 2 END")
            ->latest('id')
            ->paginate(20);

        return view('admin.payments.index', compact('sales'));
    }

    public function show(Sale $sale): View
    {
        $sale->load(['items', 'user', 'paymentTransactions.channel', 'paymentProofs.uploader']);

        $references = $sale->paymentTransactions->pluck('transaction_reference');
        $webhooks = PaymentWebhookLog::query()
            ->whereIn('transaction_reference', $references)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.payments.show', [
            'sale' => $sale,
            'webhooks' => $webhooks,
            'gatewayConfigured' => filled(config('payments.midtrans.server_key')),
        ]);
    }

    public function approveProof(PaymentProof $proof): RedirectResponse
    {
        abort_unless($proof->status === PaymentProofStatus::Pending, 400, 'Bukti ini sudah diproses.');

        $sale = $proof->sale;
        $transaction = $sale->paymentTransactions->first();

        abort_unless($transaction !== null, 400, 'Transaksi pembayaran tidak ditemukan.');
        abort_unless(
            ! $transaction->channel->isAutomatic(),
            400,
            'Transaksi otomatis tidak boleh disetujui manual. Gunakan sinkronisasi gateway.'
        );

        $changed = $this->payments->markPaid(
            $transaction,
            ['verified_proof_id' => $proof->id],
            'admin_manual',
            (string) $proof->sale->invoice_number
        );

        $proof->update([
            'status' => PaymentProofStatus::Approved->value,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return back()->with(
            'success',
            $changed
                ? 'Pembayaran '.$sale->invoice_number.' disetujui dan pesanan berstatus LUNAS.'
                : 'Pembayaran '.$sale->invoice_number.' sudah lunas sebelumnya.'
        );
    }

    public function rejectProof(Request $request, PaymentProof $proof): RedirectResponse
    {
        abort_unless($proof->status === PaymentProofStatus::Pending, 400, 'Bukti ini sudah diproses.');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $proof->update([
            'status' => PaymentProofStatus::Rejected->value,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Bukti pembayaran ditolak. Pembeli dapat mengunggah bukti baru.');
    }

    public function syncGateway(Sale $sale): RedirectResponse
    {
        $transaction = $sale->paymentTransactions->first();

        if (! $transaction || ! $transaction->channel->isAutomatic()) {
            return back()->with('error', 'Transaksi ini bukan transaksi gateway otomatis.');
        }

        try {
            $this->payments->syncFromGateway($transaction);

            return back()->with('success', 'Status transaksi berhasil disinkronisasi dari Midtrans.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Sinkronisasi gagal: '.$e->getMessage());
        }
    }
}
