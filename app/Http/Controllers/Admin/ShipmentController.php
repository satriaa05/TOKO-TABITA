<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentStatus;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderStatusHistory;
use App\Models\Sale;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use App\Services\BiteshipService;
use App\Services\ShipmentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly BiteshipService $biteship,
        private readonly ShipmentNotificationService $notifications,
    ) {}

    public function index(): View
    {
        $sales = Sale::query()
            ->with(['user', 'shipments', 'paymentTransactions'])
            ->where('payment_status', 'paid')
            ->latest('id')
            ->paginate(20);

        return view('admin.shipments.index', compact('sales'));
    }

    public function create(Sale $sale): View
    {
        abort_unless($sale->payment_status?->value === 'paid', 400, 'Hanya pesanan LUNAS yang bisa dikirim.');

        return view('admin.shipments.create', [
            'sale' => $sale,
            'couriers' => (array) config('shipping.couriers', []),
            'statuses' => ShipmentStatus::cases(),
        ]);
    }

    public function store(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($sale->payment_status?->value === 'paid', 400, 'Hanya pesanan LUNAS yang bisa dikirim.');

        $validated = $request->validate([
            'courier' => ['required', 'string', 'max:40'],
            'service' => ['nullable', 'string', 'max:60'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $shipment = Shipment::create([
            'sale_id' => $sale->id,
            'courier' => $validated['courier'],
            'service' => $validated['service'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'shipping_cost' => (float) $sale->shipping_cost,
            'shipping_eta' => $sale->shipping_eta,
            'shipment_status' => ShipmentStatus::Pending->value,
            'tracking_mode' => 'scan',
        ]);

        $syncError = null;

        try {
            if ($this->biteship->isConfigured()) {
                $this->biteship->createOrder($shipment);
            }
        } catch (\Throwable $e) {
            $syncError = $e->getMessage();
        }

        $sale->fulfillment_status = FulfillmentStatus::Processing->value;
        $sale->save();

        OrderStatusHistory::create([
            'sale_id' => $sale->id,
            'old_fulfillment_status' => FulfillmentStatus::Pending->value,
            'new_fulfillment_status' => FulfillmentStatus::Processing->value,
            'changed_by' => auth()->id(),
            'source' => 'admin',
            'note' => 'Admin membuat data pengiriman.',
        ]);

        $redirect = redirect()->route('admin.shipments.show', $shipment);

        if ($syncError) {
            return $redirect->with('error', 'Data tersimpan, tetapi order Biteship gagal: '.$syncError);
        }

        return $redirect->with('success', 'Data pengiriman berhasil disimpan & order Biteship dibuat.');
    }

    public function show(Shipment $shipment): View
    {
        $shipment->load(['sale.items', 'events', 'locations']);

        return view('admin.shipments.show', [
            'shipment' => $shipment,
            'statuses' => ShipmentStatus::cases(),
        ]);
    }

    public function updateStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ShipmentStatus::values())],
            'note' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'event_time' => ['nullable', 'date'],
        ]);

        $status = ShipmentStatus::from($validated['status']);
        $eventTime = ! empty($validated['event_time'])
            ? Carbon::parse($validated['event_time'])
            : now();

        $shipment->update(['shipment_status' => $status->value]);

        ShipmentTrackingEvent::firstOrCreate(
            [
                'shipment_id' => $shipment->id,
                'raw_status' => 'admin_'.$status->value,
                'event_time' => $eventTime,
                'source' => 'admin',
            ],
            [
                'normalized_status' => $status->value,
                'description' => $validated['note'] ?? ('Status diperbarui Admin: '.$status->label()),
                'location' => $validated['location'] ?? null,
                'latitude' => isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                'longitude' => isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            ]
        );

        // Lokasi scan terakhir dipakai peta & info "update terakhir".
        if (! empty($validated['location']) || isset($validated['latitude'], $validated['longitude'])) {
            $shipment->update([
                'last_scan_location' => $validated['location'] ?? $shipment->last_scan_location,
                'last_scan_latitude' => isset($validated['latitude']) ? (float) $validated['latitude'] : $shipment->last_scan_latitude,
                'last_scan_longitude' => isset($validated['longitude']) ? (float) $validated['longitude'] : $shipment->last_scan_longitude,
                'last_scan_at' => $eventTime,
            ]);
        }

        $sale = $shipment->sale;
        $oldFulfillment = $sale->fulfillment_status;
        $sale->fulfillment_status = $this->fulfillmentFor($status)->value;
        $sale->save();

        OrderStatusHistory::create([
            'sale_id' => $sale->id,
            'old_fulfillment_status' => $oldFulfillment?->value,
            'new_fulfillment_status' => $sale->fulfillment_status->value,
            'changed_by' => auth()->id(),
            'source' => 'admin',
            'note' => $validated['note'] ?? ('Status pengiriman: '.$status->label()),
        ]);

        $this->notifications->notify($shipment, $status, 'admin');

        return back()->with('success', 'Status pengiriman diperbarui.');
    }

    public function sync(Shipment $shipment): RedirectResponse
    {
        try {
            $result = $this->biteship->sync($shipment);

            return back()->with(
                $result['ok'] ? 'success' : 'error',
                $result['message']
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Sinkronisasi gagal: '.$e->getMessage());
        }
    }

    private function fulfillmentFor(ShipmentStatus $status): FulfillmentStatus
    {
        return match ($status) {
            ShipmentStatus::Picked => FulfillmentStatus::Packed,
            ShipmentStatus::InTransit, ShipmentStatus::DroppingOff => FulfillmentStatus::Shipped,
            ShipmentStatus::Delivered => FulfillmentStatus::Delivered,
            ShipmentStatus::Cancelled, ShipmentStatus::Failed, ShipmentStatus::Returned => FulfillmentStatus::Cancelled,
            default => FulfillmentStatus::Processing,
        };
    }
}
