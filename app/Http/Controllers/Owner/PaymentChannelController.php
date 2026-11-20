<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Owner mengelola seluruh metode pembayaran checkout: tambah, edit, hapus,
 * aktif/nonaktif, status ketersediaan, dan rekening bank.
 */
class PaymentChannelController extends Controller
{
    public function index(): View
    {
        $channels = PaymentChannel::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('owner.payment-channels.index', compact('channels'));
    }

    public function create(): View
    {
        return view('owner.payment-channels.form', [
            'channel' => new PaymentChannel([
                'mode' => 'manual',
                'status' => 'available',
                'is_active' => true,
                'is_visible' => true,
                'sort_order' => 0,
            ]),
            'editing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRequest($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_visible'] = $request->boolean('is_visible');

        $channel = PaymentChannel::query()->create($data);
        $this->audit('payment_channel.create', $channel, 'Metode pembayaran "'.$channel->name.'" ditambahkan.');

        return redirect()->route('owner.payment-channels.index')
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function edit(PaymentChannel $pembayaran_metode): View
    {
        $channel = $pembayaran_metode;

        return view('owner.payment-channels.form', [
            'channel' => $channel,
            'editing' => true,
        ]);
    }

    public function update(Request $request, PaymentChannel $pembayaran_metode): RedirectResponse
    {
        $channel = $pembayaran_metode;
        $data = $this->validateRequest($request, $channel->id);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_visible'] = $request->boolean('is_visible');

        $channel->update($data);
        $this->audit('payment_channel.update', $channel, 'Metode pembayaran "'.$channel->name.'" diperbarui.');

        return redirect()->route('owner.payment-channels.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentChannel $pembayaran_metode): RedirectResponse
    {
        $channel = $pembayaran_metode;

        if ($channel->transactions()->exists()) {
            return back()->with('error', 'Metode ini sudah dipakai transaksi. Nonaktifkan saja agar riwayat tetap utuh.');
        }

        $name = $channel->name;
        $this->audit('payment_channel.delete', $channel, 'Metode pembayaran "'.$name.'" dihapus.');
        $channel->delete();

        return redirect()->route('owner.payment-channels.index')
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function validateRequest(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('payment_methods', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:80'],
            'provider' => ['nullable', 'string', 'max:30', Rule::in(['midtrans'])],
            'mode' => ['required', Rule::in(['manual', 'automatic'])],
            'status' => ['required', Rule::in(['available', 'coming_soon'])],
            'description' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    private function audit(string $action, PaymentChannel $channel, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => PaymentChannel::class,
            'model_id' => $channel->id,
            'description' => $description,
        ]);
    }
}
