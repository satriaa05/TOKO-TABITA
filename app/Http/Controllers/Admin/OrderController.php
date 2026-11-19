<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRefundRequest;
use App\Models\Sale;
use App\Services\PaymentService;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
    public function __construct(
        private readonly RefundService $refundService,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Daftar pesanan; "Menunggu Konfirmasi" selalu di atas.
     */
    public function index(): View
    {
        $sales = Sale::query()
            ->with(['user', 'paymentTransactions.channel', 'shipments'])
            ->withCount('items')
            ->withSum('items', 'qty')
            ->withSum('items', 'subtotal')
            ->orderByRaw("CASE WHEN status = 'pending_payment' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(20);

        $stats = [
            'total' => Sale::count(),
            'pending' => Sale::query()->where('status', SaleStatus::PendingPayment->value)->count(),
            'processing' => Sale::query()->where('fulfillment_status', 'processing')->count(),
            'shipped' => Sale::query()->where('fulfillment_status', 'shipped')->count(),
            'completed' => Sale::query()->where('status', SaleStatus::Completed->value)->count(),
            'cancelled' => Sale::query()->whereIn('status', [SaleStatus::Refunded->value, SaleStatus::Voided->value])->count(),
        ];

        return view('admin.orders.index', compact('sales', 'stats'));
    }

    /**
     * Detail satu pesanan online — semua data invoice yang sama.
     */
    public function show(Sale $sale): View
    {
        $sale->load([
            'items.product.primaryPhoto',
            'payments',
            'paymentProofs',
            'paymentTransactions.channel',
            'shipments.events',
            'user',
        ]);

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
        abort_unless(auth()->user()->hasPermission('orders.manage'), 403);
        abort_unless($sale->status === SaleStatus::PendingPayment, 400, 'Hanya pesanan "Menunggu Konfirmasi" yang bisa ditandai selesai.');

        if ($sale->paymentProofs()->where('status', 'pending')->count() === 0) {
            return back()->with('error', 'Tidak bisa menyetujui: tidak ada bukti pembayaran yang menunggu verifikasi.');
        }

        $transaction = $sale->paymentTransactions->first();
        abort_unless($transaction !== null, 400, 'Transaksi pembayaran tidak ditemukan.');
        abort_unless(
            ! $transaction->channel->isAutomatic(),
            400,
            'Transaksi otomatis tidak boleh disetujui manual. Gunakan sinkronisasi payment gateway.'
        );

        $changed = $this->payments->markPaid(
            $transaction,
            ['verified_from' => 'admin.orders.complete'],
            'admin_manual',
            'MANUAL-'.$sale->invoice_number
        );

        $proof = $sale->paymentProofs()->where('status', 'pending')->first();
        $proof?->update([
            'status' => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return back()->with(
            'success',
            $changed
                ? 'Pembayaran '.$sale->invoice_number.' terverifikasi. Pesanan siap diproses.'
                : 'Pembayaran '.$sale->invoice_number.' sudah lunas sebelumnya.'
        );
    }

    /**
     * Batalkan (refund) transaksi yang sudah selesai.
     *
     * Stok produk dikembalikan otomatis via RefundService.
     * Hanya transaksi berstatus "Completed" yang bisa dibatalkan.
     */
    public function refund(SaleRefundRequest $request, Sale $sale): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('orders.manage'), 403);

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
