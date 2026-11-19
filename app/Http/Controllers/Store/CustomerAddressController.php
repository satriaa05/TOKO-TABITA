<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Alamat pengiriman tersimpan milik pembeli.
 * Setiap aksi selalu dibatasi pada user yang sedang login.
 */
class CustomerAddressController extends Controller
{
    public function index(): View
    {
        $addresses = auth()->user()->addresses()->latest('is_default')->latest('id')->paginate(10);

        return view('store.addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('store.addresses.form', [
            'address' => new CustomerAddress,
            'action' => route('store.addresses.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAddress($request);

        DB::transaction(function () use ($data) {
            if ($data['is_default'] ?? false) {
                auth()->user()->addresses()->update(['is_default' => false]);
            }

            auth()->user()->addresses()->create($data);
        });

        return redirect()->route('store.addresses.index')
            ->with('success', 'Alamat berhasil disimpan.');
    }

    public function edit(CustomerAddress $address): View
    {
        $this->authorizeOwner($address);

        return view('store.addresses.form', [
            'address' => $address,
            'action' => route('store.addresses.update', $address),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($address);
        $data = $this->validateAddress($request);

        DB::transaction(function () use ($address, $data) {
            if ($data['is_default'] ?? false) {
                auth()->user()->addresses()->update(['is_default' => false]);
            }

            $address->update($data);
        });

        return redirect()->route('store.addresses.index')
            ->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($address);
        $address->delete();

        return redirect()->route('store.addresses.index')
            ->with('success', 'Alamat berhasil dihapus.');
    }

    public function setDefault(CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($address);

        DB::transaction(function () use ($address) {
            auth()->user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return redirect()->route('store.addresses.index')
            ->with('success', 'Alamat utama berhasil diubah.');
    }

    /** @return array<string, mixed> */
    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{8,30}$/'],
            'address_line' => ['required', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:15'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeOwner(CustomerAddress $address): void
    {
        abort_unless((int) $address->user_id === (int) auth()->id(), 403);
    }
}
