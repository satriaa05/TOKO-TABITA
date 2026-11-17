<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Http\Requests\SaleRefundRequest;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Sale;
use App\Http\Controllers\Controller;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

/**
 * Pesanan online (guest checkout) untuk Admin Toko Baju.
 *
 * Pembeli memesan lewat halaman toko; pesanan masuk dengan status
 * "Menunggu Konfirmasi". Admin memeriksa pembayaran (transfer via
 * WhatsApp) lalu menandai pesanan selesai. Pembayaran dicatat di
 * tabel payments saat konfirmasi.
 */
class OrderController extends Controller
{

    public function __construct(private readonly RefundService $refundService) {}

    /**
     * Daftar pesanan; "Menunggu Konfirmasi" selalu di atas.
     */
    public function index(): View
    {
        $sales = Sale::query()
            ->withCount('items')
            ->orderByRaw("CASE WHEN status = 'pending_payment' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(20);

        return view('admin.orders.index', compact('sales'));
    }

    /**
     * Detail satu pesanan online.
     */
    public function show(Sale $sale): View
    {
        $sale->load('items.product', 'payments', 'paymentProofs');

        return view('admin.orders.show', compact('sale'));
    }

    /**
     * Tandai pesanan online selesai; pembayaran dicatat saat itu.
     *
     * Verifikasi: pesanan harus punya minimal satu bukti pembayaran
     * yang diunggah pembeli sebelum admin bisa menandai selesai.
     */
    public function complete(Sale $sale): RedirectResponse
    {
        abort_unless($sale->status === SaleStatus::PendingPayment, 400, 'Hanya pesanan "Menunggu Konfirmasi" yang bisa ditandai selesai.');

        if ($sale->paymentProofs()->count() === 0) {
            return back()->with('error', 'Tidak bisa menandai selesai: pembeli belum mengunggah bukti pembayaran.');
        }

        $sale->update([
            'status' => SaleStatus::Completed->value,
            'paid_amount' => (float) $sale->total,
        ]);

        Payment::create([
            'sale_id' => $sale->id,
            'method' => PaymentMethod::Transfer,
            'amount' => (float) $sale->total,
            'reference' => 'WA',
            'paid_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'sale.complete',
            'model_type' => Sale::class,
            'model_id' => $sale->id,
            'description' => 'Pesanan '.$sale->invoice_number.' dikonfirmasi selesai.',
        ]);

        return back()->with('success', 'Pesanan '.$sale->invoice_number.' ditandai selesai.');
    }

    /**
     * Batalkan (refund) transaksi yang sudah selesai.
     *
     * Stok produk dikembalikan otomatis via RefundService.
     * Hanya transaksi berstatus "Completed" yang bisa dibatalkan.
     */
    public function refund(SaleRefundRequest $request, Sale $sale): RedirectResponse
    {
        try {
            $this->refundService->refund(
                sale: $sale,
                user: $request->user(),
                reason: $request->validated('reason'),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('admin.orders.show', $sale)
            ->with('success', 'Pesanan '.$sale->invoice_number.' berhasil dibatalkan. Stok dikembalikan.');
    }
}
