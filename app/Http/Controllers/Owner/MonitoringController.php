<?php

namespace App\Http\Controllers\Owner;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookLog;
use App\Models\Sale;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitoring transaksi & integrasi untuk Owner.
 * Semua angka berasal dari tabel transaksi nyata, bukan data contoh.
 */
class MonitoringController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $period, $periodOptions] = $this->periodRange($request);

        $sales = Sale::query()
            ->with('items')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $paid = $sales->where('payment_status', PaymentStatus::Paid);

        $revenue = (float) $paid->sum(fn (Sale $sale) => (float) $sale->total);
        $shippingTotal = (float) $paid->sum(fn (Sale $sale) => (float) $sale->shipping_cost);
        $profit = (float) $paid->sum(fn (Sale $sale) => $sale->grossProfit() - (float) $sale->shipping_cost);

        $paymentCounts = [
            'paid' => $sales->where('payment_status', PaymentStatus::Paid)->count(),
            'pending' => $sales->where('payment_status', PaymentStatus::Pending)->count(),
            'unpaid' => $sales->where('payment_status', PaymentStatus::Unpaid)->count(),
            'failed' => $sales->where('payment_status', PaymentStatus::Failed)->count(),
            'expired' => $sales->where('payment_status', PaymentStatus::Expired)->count(),
            'refunded' => $sales->where('payment_status', PaymentStatus::Refunded)->count(),
        ];

        $fulfillmentCounts = [
            'processing' => $sales->where('fulfillment_status', FulfillmentStatus::Processing)->count(),
            'packed' => $sales->where('fulfillment_status', FulfillmentStatus::Packed)->count(),
            'shipped' => $sales->where('fulfillment_status', FulfillmentStatus::Shipped)->count(),
            'delivered' => $sales->where('fulfillment_status', FulfillmentStatus::Delivered)->count(),
            'cancelled' => $sales->where('fulfillment_status', FulfillmentStatus::Cancelled)->count(),
        ];

        $byMethod = PaymentTransaction::query()
            ->join('payment_methods', 'payment_methods.id', '=', 'payment_transactions.payment_method_id')
            ->whereBetween('payment_transactions.created_at', [$from, $to])
            ->selectRaw('payment_methods.name as method, COUNT(*) as total, SUM(payment_transactions.amount) as nominal')
            ->groupBy('payment_methods.name')
            ->orderByDesc('total')
            ->get();

        $shipmentQuery = Shipment::query()->whereHas(
            'sale',
            fn ($query) => $query->whereBetween('created_at', [$from, $to])
        );

        $issues = [
            'webhook_gagal' => PaymentWebhookLog::query()
                ->where('created_at', '>=', $from)
                ->where(function ($query) {
                    $query->where('signature_valid', false)
                        ->orWhere('processing_status', 'failed');
                })->count(),
            'transaksi_gagal' => (int) ($paymentCounts['failed'] + $paymentCounts['expired']),
            'tracking_tidak_sinkron' => (int) (clone $shipmentQuery)
                ->whereNotIn('shipment_status', ['delivered', 'returned', 'cancelled', 'failed'])
                ->where(function ($query) {
                    $query->whereNull('last_synced_at')
                        ->orWhere('last_synced_at', '<', now()->subDays(2));
                })->count(),
            'resi_belum_diinput' => (int) (clone $shipmentQuery)
                ->whereNull('tracking_number')->count(),
        ];

        $webhookLogs = PaymentWebhookLog::query()
            ->latest('id')
            ->limit(8)
            ->get();

        return view('owner.monitoring.index', compact(
            'from', 'to', 'period', 'periodOptions', 'revenue', 'shippingTotal', 'profit',
            'paymentCounts', 'fulfillmentCounts', 'byMethod', 'issues', 'webhookLogs', 'sales'
        ));
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string, 3: array<string, string>} */
    private function periodRange(Request $request): array
    {
        $options = [
            'hari_ini' => 'Hari ini',
            '7' => '7 hari terakhir',
            '30' => '30 hari terakhir',
            '90' => '90 hari terakhir',
            'custom' => 'Rentang khusus',
        ];

        $period = (string) $request->query('periode', '30');
        $now = now();

        if (! array_key_exists($period, $options)) {
            $period = '30';
        }

        [$from, $to] = match ($period) {
            'hari_ini' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '90' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                Carbon::parse($request->query('dari', $now->copy()->subDays(29)->toDateString()))->startOfDay(),
                Carbon::parse($request->query('sampai', $now->toDateString()))->endOfDay(),
            ],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };

        return [$from, $to, $period, $options];
    }
}
