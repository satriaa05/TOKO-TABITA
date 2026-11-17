<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Media;
use App\Models\PaymentProof;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Subscriber;
use App\Models\Wishlist;
use App\Services\OnlineOrderService;
use App\Services\PosCartService;
use App\Services\PromotionService;
use App\Services\ShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('store.checkout', $data);
    }

    /**
     * Simpan pesanan online (status "Menunggu Konfirmasi").
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:150'],
            'buyer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{8,30}$/'],
            'buyer_address' => ['required', 'string', 'max:1000'],
            'buyer_note' => ['nullable', 'string', 'max:255'],
            'shipping_zone' => ['nullable', 'string', 'max:32'],
            'shipping_provider' => ['nullable', 'string', 'max:64'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ], [
            'buyer_phone.regex' => 'Nomor WhatsApp harus berisi angka (opsional angka 0/+).',
        ]);

        $buyer = [
            'name' => $validated['buyer_name'],
            'phone' => $validated['buyer_phone'],
            'address' => $validated['buyer_address'],
            'note' => $validated['buyer_note'] ?? null,
            'shipping_zone' => $validated['shipping_zone'] ?? null,
            'shipping_provider' => $validated['shipping_provider'] ?? null,
        ];

        try {
            $sale = $this->orders->place($buyer, $validated['idempotency_key']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('store.orderSuccess', $sale);
    }

    /**
     * Halaman sukses + tombol konfirmasi via WhatsApp.
     */
    public function orderSuccess(Sale $sale): View
    {
        abort_unless($sale->status === \App\Enums\SaleStatus::PendingPayment, 404);

        $sale->load('items');

        $shippingInfo = $this->shipping->calculate(
            $sale->shipping_zone,
            (float) $sale->subtotal - (float) $sale->discount_total,
        );

        $rawWa = (string) config('pos.store.phone');
        $waLink = null;

        if ($rawWa !== '') {
            $number = preg_replace('/\D+/', '', $rawWa);
            $number = preg_replace('/^0/', '62', $number);

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

            $waLink = 'https://wa.me/'.$number.'?text='.rawurlencode($text);
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

        $sale->load('items.product', 'payments', 'paymentProofs');

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

        $product->load(['photos', 'category', 'variants']);

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

        $rawWa = (string) config('pos.store.phone');
        $waNumber = $rawWa !== ''
            ? preg_replace('/^0/', '62', preg_replace('/\D+/', '', $rawWa))
            : null;

        return view('store.product', compact('product', 'chart', 'waNumber'));
    }

    /**
     * Validasi input varian: bila produk memakai varian, size & color
     * wajib cocok dengan varian yang ada. Tidak melempar exception —
     * pemanggil menampilkan pesan lewat flash session.
     *
     * @return array{size: ?string, color: ?string, error: ?string}
     */
    private function validVariant(Product $product, ?string $size, ?string $color): array
    {
        $product->loadMissing('variants');

        if ($product->variants->isEmpty()) {
            return ['size' => null, 'color' => null, 'error' => null];
        }

        $size = $size !== null ? trim($size) : '';
        $color = $color !== null ? trim($color) : '';

        if ($size === '' || $color === '') {
            return ['size' => null, 'color' => null, 'error' => 'Pilih ukuran dan warna terlebih dahulu.'];
        }

        $variant = $product->variants->first(fn ($v) => (string) $v->size === $size
            && (string) $v->color === $color);

        if (! $variant) {
            return ['size' => null, 'color' => null, 'error' => 'Varian ini tidak tersedia. Pilih varian lain.'];
        }

        if ((int) $variant->stock < 1) {
            return ['size' => null, 'color' => null, 'error' => 'Stok untuk varian ini habis. Pilih varian lain.'];
        }

        return ['size' => $size, 'color' => $color, 'error' => null];
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
