<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\Media;
use App\Models\PaymentChannel;
use App\Models\PaymentProof;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Subscriber;
use App\Models\Voucher;
use App\Models\Wishlist;
use App\Services\BiteshipService;
use App\Services\MapService;
use App\Services\MidtransService;
use App\Services\OnlineOrderService;
use App\Services\PaymentService;
use App\Services\PosCartService;
use App\Services\PromotionService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Halaman toko publik (tamu).
 *
 * Menampilkan aset gambar yang dikelola admin/owner lewat tabel media,
 * katalog produk, keranjang (session), dan checkout tamu. Pesanan online
 * tersimpan dengan status "Menunggu Konfirmasi"; pembayaran dilakukan di
 * luar sistem (transfer via WhatsApp ke admin).
 */
class StoreController extends Controller
{
    public function __construct(
        private readonly PosCartService $cart,
        private readonly PromotionService $promotions,
        private readonly OnlineOrderService $orders,
        private readonly ShippingService $shipping,
        private readonly MidtransService $midtrans,
        private readonly PaymentService $payments,
        private readonly MapService $maps,
        private readonly BiteshipService $biteship,
    ) {}

    /**
     * Beranda: banner, kategori, produk terbaru, koleksi.
     */
    public function home(): View|RedirectResponse
    {
        // Staff (admin/owner) diarahkan ke dashboard; pelanggan boleh membuka beranda toko.
        if (auth()->check() && auth()->user()->role !== \App\Enums\UserRole::Customer) {
            return redirect()->route(auth()->user()->role->dashboardRoute());
        }

        $logo = Media::siteAsset('logo');
        $banners = Media::siteRole('banner_home')->get();
        $promos = Media::siteRole('banner_promo')->get();
        $collections = Media::siteRole('collection')->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->with('photo')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->active()
            ->with('primaryPhoto', 'variants')
            ->latest()
            ->limit(8)
            ->get();

        return view('store.home', compact(
            'logo',
            'banners',
            'promos',
            'collections',
            'categories',
            'products',
        ));
    }

    /**
     * Katalog semua produk aktif untuk pembeli tamu.
     */
    public function catalog(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('kategori', 0);
        $sort = (string) $request->query('urut', 'terbaru');

        if (! in_array($sort, ['terbaru', 'nama', 'harga_asc', 'harga_desc'], true)) {
            $sort = 'terbaru';
        }

        $products = Product::query()
            ->active()
            ->with('primaryPhoto', 'category', 'variants')
            ->when($keyword !== '', fn ($query) => $query->where('name', 'like', '%'.$keyword.'%'))
            ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
            ->when($sort === 'nama', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'harga_asc', fn ($query) => $query->orderBy('sale_price'))
            ->when($sort === 'harga_desc', fn ($query) => $query->orderByDesc('sale_price'))
            ->when($sort === 'terbaru', fn ($query) => $query->latest())
            ->paginate(24)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('store.catalog', compact('products', 'categories', 'keyword', 'categoryId', 'sort'));
    }

    /**
     * Keranjang (session server; harga selalu dihitung ulang di server).
     */
    public function cart(): View
    {
        $data = $this->cartData();

        return view('store.cart', $data);
    }

    /**
     * Tambah produk ke keranjang (opsional dengan varian).
     */
    public function addToCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
            'size' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        $product = Product::query()->active()->findOrFail($data['product_id']);
        $variantCheck = $this->validVariant($product, $data['size'] ?? null, $data['color'] ?? null);

        if ($variantCheck['error'] !== null) {
            return back()->withInput()->with('error', $variantCheck['error']);
        }

        $size = $variantCheck['size'];
        $color = $variantCheck['color'];
        $qty = (int) ($data['qty'] ?? 1);
        $already = $this->cart->qtyFor($product->id, $size, $color);

        if ($variantCheck['variant'] !== null && $already + $qty > (int) $variantCheck['variant']->stock) {
            return back()->withInput()->with(
                'error',
                'Stok varian ini tersisa '.(int) $variantCheck['variant']->stock.'; keranjang Anda sudah berisi '.$already.'.'
            );
        }

        $this->cart->add($product, $qty, $size, $color);

        if ($request->boolean('buy_now')) {
            return redirect()->route('store.checkout')
                ->with('success', '"'.$product->name.'" masuk keranjang. Lengkapi pemesanan.');
        }

        return back()->with('success', '"'.$product->name.'" masuk keranjang.');
    }

    /**
     * Ubah jumlah pada baris keranjang.
     */
    public function updateCart(Request $request, string $key): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cart->updateQty($key, (int) $data['qty']);

        return redirect()->route('store.cart');
    }

    /**
     * Hapus satu baris dari keranjang.
     */
    public function removeCartItem(string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return redirect()->route('store.cart');
    }

    /**
     * Kosongkan keranjang.
     */
    public function clearCart(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('store.cart');
    }

    /**
     * Form checkout: identitas pembeli + ringkasan pesanan.
     */
    public function checkout(): View|RedirectResponse
    {
        $data = $this->cartData();

        if ($data['items']->isEmpty()) {
            return redirect()->route('store.cart')
                ->with('error', 'Keranjang masih kosong.');
        }

        $data['idempotencyKey'] = Str::random(40);
        $data['shippingZones'] = $this->shipping->zones();
        $data['shippingFreeThreshold'] = (float) config('pos.shipping.free_threshold', 0);
        $data['addresses'] = auth()->user()->addresses()
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();
        $data['paymentChannels'] = PaymentChannel::activeChannels();
        $data['couriers'] = (array) config('shipping.couriers', []);

        $voucher = $this->sessionVoucher();
        $voucherDiscount = $voucher
            ? $voucher->discountFor(round((float) $data['subtotal'] - (float) $data['discount'], 2))
            : 0.0;

        $data['voucher'] = $voucher;
        $data['voucherDiscount'] = $voucherDiscount;
        $data['displayTotal'] = round(
            max(0, (float) $data['subtotal'] - (float) $data['discount'] - $voucherDiscount)
            + (float) $data['shipping']['cost'],
            2
        );

        return view('store.checkout', $data);
    }

    /**
     * Validasi kode voucher via AJAX. Diskon dihitung di server.
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        $code = strtoupper(trim((string) $request->input('code', '')));

        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Kode voucher wajib diisi.'], 422);
        }

        $voucher = Voucher::query()->where('code', $code)->first();

        if (! $voucher || ! $voucher->isValidNow()) {
            return response()->json(['ok' => false, 'message' => 'Kode voucher tidak valid atau sudah berakhir.'], 422);
        }

        $data = $this->cartData();
        $discount = $voucher->discountFor(round((float) $data['subtotal'] - (float) $data['discount'], 2));

        if ($discount <= 0) {
            return response()->json(['ok' => false, 'message' => 'Minimum belanja voucher ini belum terpenuhi.'], 422);
        }

        session(['checkout_voucher' => $voucher->code]);

        return response()->json([
            'ok' => true,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'discount' => $discount,
            'discount_label' => rupiah($discount),
        ]);
    }

    /**
     * Ambil tarif ongkir real-time dari Biteship untuk ditampilkan di checkout.
     * Harga akhir tetap dihitung ulang server saat pesanan dibuat.
     */
    public function checkRates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'postal_code' => ['required', 'string', 'max:15'],
        ]);

        $data = $this->cartData();
        $weight = max(1000, (int) $data['count'] * 500);

        $result = $this->biteship->rates(
            $validated['postal_code'],
            round((float) $data['subtotal'] - (float) $data['discount'], 2),
            $weight
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function removeVoucher(): RedirectResponse
    {
        session()->forget('checkout_voucher');

        return redirect()->route('store.checkout')->with('success', 'Voucher dihapus.');
    }

    /**
     * Simpan pesanan online (status "Menunggu Konfirmasi", pembayaran UNPAID).
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'buyer_name' => ['nullable', 'required_without:address_id', 'string', 'max:150'],
            'buyer_phone' => ['nullable', 'required_without:address_id', 'string', 'max:30', 'regex:/^[0-9+\-\s]{8,30}$/'],
            'buyer_address' => ['nullable', 'required_without:address_id', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:15'],
            'buyer_note' => ['nullable', 'string', 'max:255'],
            'shipping_zone' => ['nullable', 'string', 'max:32'],
            'courier' => ['nullable', 'string', 'max:40'],
            'service' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'voucher_code' => ['nullable', 'string', 'max:40'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ], [
            'buyer_phone.regex' => 'Nomor WhatsApp harus berisi angka (opsional angka 0/+).',
        ]);

        $address = null;

        if (! empty($validated['address_id'])) {
            $address = CustomerAddress::query()
                ->where('user_id', auth()->id())
                ->find($validated['address_id']);

            abort_unless($address !== null, 403, 'Alamat ini bukan milik Anda.');
        }

        $courierLabel = null;

        if (! empty($validated['courier'])) {
            $catalog = (array) config('shipping.couriers', []);
            $courierName = $catalog[$validated['courier']]['name'] ?? $validated['courier'];
            $serviceName = $catalog[$validated['courier']]['services'][$validated['service'] ?? ''] ?? null;
            $courierLabel = $serviceName
                ? $courierName.' ('.$serviceName.')'
                : $courierName;
        }

        $buyer = [
            'name' => $address?->recipient_name ?: $validated['buyer_name'],
            'phone' => $address?->phone ?: $validated['buyer_phone'],
            'address' => $address ? $address->fullAddress() : $validated['buyer_address'],
            'note' => $validated['buyer_note'] ?? null,
            'shipping_zone' => $validated['shipping_zone'] ?? null,
            'shipping_provider' => $courierLabel,
            'postal_code' => $address?->postal_code ?: ($validated['postal_code'] ?? null),
        ];

        $options = [
            'payment_method' => $validated['payment_method'] ?? 'transfer',
            'voucher_code' => $validated['voucher_code'] ?? session('checkout_voucher'),
            'address_id' => $address?->id,
            'courier' => $validated['courier'] ?? null,
            'service' => $validated['service'] ?? null,
        ];

        try {
            $sale = $this->orders->place($buyer, $validated['idempotency_key'], $options);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        session()->forget('checkout_voucher');

        return redirect()->route('store.payment.show', $sale);
    }

    /**
     * Halaman pembayaran: instruksi transfer, nominal, nomor pesanan,
     * batas waktu (bila disediakan gateway), dan unggah bukti untuk manual.
     */
    public function payment(Sale $sale): View
    {
        abort_unless((int) $sale->user_id === (int) auth()->id(), 403);

        $sale->load(['items', 'paymentTransactions.channel', 'paymentProofs', 'shipments']);
        $transaction = $sale->paymentTransactions->first();
        $gatewayError = null;

        if ($transaction && $transaction->channel->isAutomatic()) {
            if (! $this->midtrans->isConfigured()) {
                $gatewayError = 'Metode otomatis belum tersedia karena kredensial payment gateway belum diisi oleh Admin.';
            } elseif (! $transaction->provider_reference) {
                try {
                    $this->midtrans->charge($transaction);
                    $transaction->refresh();
                } catch (PaymentGatewayException $e) {
                    $gatewayError = $e->getMessage();
                }
            }
        }

        return view('store.payment', compact('sale', 'transaction', 'gatewayError'));
    }

    /**
     * Pengecekan status hanya membaca data server / DB.
     * Untuk pembayaran manual, status berubah hanya setelah verifikasi Admin.
     */
    public function checkPaymentStatus(Sale $sale): RedirectResponse
    {
        abort_unless((int) $sale->user_id === (int) auth()->id(), 403);

        $transaction = $sale->paymentTransactions->first();

        if ($transaction && $transaction->channel->isAutomatic()
            && $this->midtrans->isConfigured() && $transaction->provider_reference) {
            try {
                $this->payments->syncFromGateway($transaction);
            } catch (\Throwable) {
                // Fallback gagal: status tetap dibaca dari database.
            }
        }

        $status = $sale->fresh()->payment_status;
        $note = $status->value === 'paid'
            ? 'Pembayaran Anda sudah dikonfirmasi.'
            : 'Status akan berubah otomatis bila gateway mengirim notifikasi, atau setelah Admin memverifikasi bukti transfer Anda.';

        return back()->with('success', 'Status pembayaran: '.$status->label().'. '.$note);
    }

    /**
     * Halaman lacak pengiriman untuk pesanan milik pelanggan.
     */
    public function tracking(Sale $sale): View
    {
        abort_unless((int) $sale->user_id === (int) auth()->id(), 403);

        $sale->load(['items', 'paymentTransactions.channel', 'shipments.events', 'shipments.locations']);
        $shipment = $sale->shipments->first();

        $map = [
            'configured' => $this->maps->isConfigured(),
            'origin' => null,
            'destination' => null,
            'last_scan' => null,
            'scans' => [],
            'polyline' => null,
            'attribution' => $this->maps->attribution(),
            'tile_url' => (string) config('shipping.maps.tile_url'),
            'note' => null,
        ];

        if ($shipment && $map['configured']) {
            $store = store_profile();
            $originText = trim($store->location().' '.$store->address().' '.config('pos.shipping.origin_postal_code'));
            $destinationText = trim($sale->buyer_address.' '.$sale->buyer_postal_code);

            $map['origin'] = Cache::remember('osm.geocode.store.'.md5($originText), 86400,
                fn () => $this->maps->geocode($originText));
            $map['destination'] = Cache::remember('osm.geocode.buyer.'.$sale->id, 86400,
                fn () => $this->maps->geocode($destinationText));

            if ($shipment->hasScanCoordinate()) {
                $map['last_scan'] = [
                    'lat' => $shipment->last_scan_latitude,
                    'lng' => $shipment->last_scan_longitude,
                ];
            }

            $map['scans'] = $shipment->events
                ->filter(fn ($event) => $event->hasCoordinate())
                ->map(fn ($event) => [
                    'lat' => $event->latitude,
                    'lng' => $event->longitude,
                    'label' => $event->normalized_status->label().' · '.$event->location,
                ])
                ->values()
                ->all();

            if ($map['origin'] && $map['destination']) {
                $map['polyline'] = Cache::remember('osm.route.'.$sale->id, 21600,
                    fn () => $this->maps->route($map['origin'], $map['destination']));
            }

            if (! $shipment->isLive()) {
                $map['note'] = 'Peta OpenStreetMap menampilkan lokasi asal, tujuan, dan scan terakhir dari data pengiriman. GPS kurir secara langsung tidak tersedia untuk ekspedisi reguler.';
            } else {
                $map['note'] = 'Peta OpenStreetMap menampilkan titik scan. Posisi kurir real-time tersedia lewat tautan resmi Biteship di atas.';
            }
        }

        return view('store.tracking', compact('sale', 'shipment', 'map'));
    }

    /**
     * Partial tracking yang di-refresh berkala via polling (real-time dari DB server).
     */
    public function trackingPartial(Sale $sale): Response
    {
        abort_unless((int) $sale->user_id === (int) auth()->id(), 403);

        $sale->load(['shipments.events', 'shipments.locations']);
        $shipment = $sale->shipments->first();

        return response(
            view('store.partials.tracking-live', compact('sale', 'shipment'))->render()
        )->header('Cache-Control', 'no-store');
    }

    private function sessionVoucher(): ?Voucher
    {
        $code = session('checkout_voucher');

        if (! $code) {
            return null;
        }

        $voucher = Voucher::query()->where('code', $code)->first();

        return $voucher && $voucher->isValidNow() ? $voucher : null;
    }

    /**
     * Halaman sukses + tombol konfirmasi via WhatsApp.
     */
    public function orderSuccess(Sale $sale): View
    {
        abort_unless((int) $sale->user_id === (int) auth()->id(), 403, 'Pesanan ini bukan milik Anda.');
        abort_unless($sale->status === \App\Enums\SaleStatus::PendingPayment, 404);

        $sale->load('items');

        $shippingInfo = $this->shipping->calculate(
            $sale->shipping_zone,
            (float) $sale->subtotal - (float) $sale->discount_total,
        );

        $rawWa = store_profile()->whatsappNumber();
        $waLink = null;

        if ($rawWa !== null) {
            $items = $sale->items
                ->map(fn ($item) => '- '.$item->product_name.' x'.$item->qty.' ('.rupiah($item->total).')')
                ->implode("\n");

            $text = "Halo, saya ingin konfirmasi pesanan:\n\n"
                ."No. Pesanan: {$sale->invoice_number}\n"
                ."Nama: {$sale->buyer_name}\n"
                ."WhatsApp: {$sale->buyer_phone}\n"
                ."Alamat: {$sale->buyer_address}\n"
                ."Item:\n{$items}\n"
                ."Ongkir: ".rupiah($sale->shipping_cost)."\n"
                .'Total: '.rupiah($sale->total);

            $waLink = store_profile()->whatsappUrl($text);
        }

        return view('store.order-success', compact('sale', 'waLink', 'shippingInfo'));
    }

    /**
     * Daftar wishlist pembeli (hanya milik sendiri).
     */
    public function wishlist(): View
    {
        $items = Wishlist::query()
            ->where('user_id', auth()->id())
            ->with(['product.primaryPhoto', 'product.category'])
            ->latest('id')
            ->paginate(12);

        return view('store.wishlist', compact('items'));
    }

    /**
     * Toggle wishlist — tambah/hapus produk dari wishlist.
     */
    public function toggleWishlist(Product $product): RedirectResponse
    {
        $exists = Wishlist::query()
            ->where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($exists) {
            $exists->delete();
            return back()->with('success', '"'.$product->name.'" dihapus dari wishlist.');
        }

        Wishlist::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
        ]);

        return back()->with('success', '"'.$product->name.'" ditambahkan ke wishlist.');
    }

    /**
     * Daftar email untuk newsletter (homepage).
     */
    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150', 'unique:subscribers,email'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar di newsletter kami.',
        ]);

        Subscriber::create([
            'email' => $validated['email'],
            'source' => 'homepage',
        ]);

        return back()->with('newsletter_success', 'Terima kasih! Anda terdaftar di newsletter Tabita Modiste.');
    }

    /**
     * Riwayat pesanan pembeli (hanya milik sendiri).
     */
    public function buyerOrders(): View
    {
        $sales = Sale::query()
            ->with('items', 'paymentProofs')
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(15);

        return view('store.orders', compact('sales'));
    }

    /**
     * Detail satu pesanan milik pembeli + bukti pembayaran.
     */
    public function buyerOrder(Sale $sale): View
    {
        abort_unless((int) $sale->user_id === (int) auth()->id(), 403);

        $sale->load('items.product', 'payments', 'paymentProofs', 'paymentTransactions.channel');

        return view('store.order-detail', compact('sale'));
    }

    /**
     * Unggah bukti pembayaran (hanya pemilik pesanan, hanya selama
     * pesanan menunggu konfirmasi pembayaran).
     */
    public function uploadProof(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless((int) $sale->user_id === (int) auth()->id(), 403);
        abort_unless($sale->status === \App\Enums\SaleStatus::PendingPayment, 400, 'Pesanan ini sudah tidak menunggu bukti pembayaran.');

        $request->validate([
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'proof.required' => 'File bukti pembayaran wajib diunggah.',
            'proof.image' => 'File harus berupa gambar.',
            'proof.mimes' => 'Format bukti pembayaran: JPG, JPEG, PNG, atau WebP.',
            'proof.max' => 'Ukuran bukti maksimal 2 MB.',
        ]);

        $file = $request->file('proof');
        $extension = $this->extensionFromMime($file);
        $path = $file->storeAs(
            'payment-proofs',
            Str::uuid().'.'.$extension,
            'public'
        );

        PaymentProof::create([
            'sale_id' => $sale->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => substr($file->getClientOriginalName(), 0, 255),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Admin akan memverifikasi pesanan Anda.');
    }

    /**
     * Tentukan ekstensi file dari MIME content (magic bytes), bukan dari
     * nama file yang dikirim browser. Mencegah upload file berbahaya
     * dengan ekstensi gambar palsu.
     */
    private function extensionFromMime(\Illuminate\Http\UploadedFile $file): string
    {
        $path = $file->getRealPath();

        $mime = '';

        if ($path !== false && is_file($path) && function_exists('finfo_open')) {
            try {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if ($finfo !== false) {
                    $mime = (string) finfo_file($finfo, $path);
                    finfo_close($finfo);
                }
            } catch (\Throwable) {
                // Fallback ke getMimeType().
            }
        }

        if ($mime === '') {
            $mime = (string) $file->getMimeType();
        }

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    /**
     * Halaman detail produk: galeri foto, harga, size chart (cm)
     * dan tombol Add to Cart / Buy Now / WhatsApp.
     */
    public function productShow(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['photos', 'category', 'variants', 'colors']);

        // Data galeri per warna disiapkan di server supaya JS hanya berganti
        // sumber gambar; tidak ada URL/foto yang dipercaya dari browser.
        $gallery = [];

        foreach ($product->colors as $color) {
            $gallery[$color->name] = $product->photosForColor($color->name, (int) $color->id)
                ->map(fn ($photo) => ['src' => $photo->url(), 'alt' => 'Foto '.$product->name.' '.$color->name])
                ->values()
                ->all();
        }

        if ($gallery === []) {
            $gallery[''] = $product->photosForColor(null)
                ->map(fn ($photo) => ['src' => $photo->url(), 'alt' => 'Foto '.$product->name])
                ->values()
                ->all();
        }

        $categoryName = strtolower((string) $product->category?->name);
        $set = 'default';

        foreach (config('size_charts.match', []) as $key => $keywords) {
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($categoryName, strtolower($keyword))) {
                    $set = $key;
                    break 2;
                }
            }
        }

        $chart = config('size_charts.sets.'.$set, config('size_charts.sets.default'));

        $waNumber = store_profile()->whatsappNumber();

        return view('store.product', compact('product', 'chart', 'waNumber', 'gallery'));
    }

    /**
     * Validasi input varian: bila produk memakai varian, size & color
     * wajib cocok dengan varian yang ada. Tidak melempar exception —
     * pemanggil menampilkan pesan lewat flash session.
     *
     * @return array{size: ?string, color: ?string, error: ?string, variant: ?\App\Models\ProductVariant}
     */
    private function validVariant(Product $product, ?string $size, ?string $color): array
    {
        $product->loadMissing('variants');

        if ($product->variants->isEmpty()) {
            return ['size' => null, 'color' => null, 'error' => null, 'variant' => null];
        }

        $size = $size !== null ? trim($size) : '';
        $color = $color !== null ? trim($color) : '';

        if ($size === '' || $color === '') {
            return ['size' => null, 'color' => null, 'error' => 'Pilih ukuran dan warna terlebih dahulu.', 'variant' => null];
        }

        $variant = $product->variants->first(fn ($v) => (string) $v->size === $size
            && (string) $v->color === $color
            && ($v->is_active ?? true));

        if (! $variant) {
            return ['size' => null, 'color' => null, 'error' => 'Varian ini tidak tersedia. Pilih varian lain.', 'variant' => null];
        }

        if ((int) $variant->stock < 1) {
            return ['size' => null, 'color' => null, 'error' => 'Stok untuk varian ini habis. Pilih varian lain.', 'variant' => null];
        }

        return ['size' => $size, 'color' => $color, 'error' => null, 'variant' => $variant];
    }

    /**
     * Ringkasan keranjang + promo; dipakai halaman keranjang & checkout.
     */
    private function cartData(): array
    {
        $items = $this->cart->buildItems();
        $promo = $items->isNotEmpty()
            ? $this->promotions->apply($items)
            : ['items' => [], 'total_discount' => 0];

        $subtotal = $items->isNotEmpty() ? round($items->sum('subtotal'), 2) : 0.0;
        $discount = (float) ($promo['total_discount'] ?? 0);
        $orderTotal = round($subtotal - $discount, 2);
        $shipping = $this->shipping->calculate(null, $orderTotal);
        $total = round($orderTotal + $shipping['cost'], 2);
        $count = $items->sum('qty');

        return compact(
            'items',
            'subtotal',
            'discount',
            'orderTotal',
            'shipping',
            'total',
            'count',
        );
    }
}
