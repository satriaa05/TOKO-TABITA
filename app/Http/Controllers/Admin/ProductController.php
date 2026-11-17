<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Manajemen produk (CRUD + status aktif + filter).
 *
 * Foto produk dikelola lewat MediaController (route .../foto); halaman
 * form menampilkan panel galeri yang memakai route tersebut.
 */
class ProductController extends Controller
{
    /**
     * Daftar produk dengan pencarian, filter, sorting, dan pagination.
     */
    public function index(Request $request): View
    {
        $keyword = $request->query('q');
        $categoryId = $request->query('kategori');
        $status = $request->query('status');
        $sort = $request->query('sort', 'nama');
        $direction = $request->query('dir', 'asc');

        $allowedSorts = ['nama', 'barcode', 'harga_jual', 'harga_pokok', 'stok', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'nama';
        }

        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $columnMap = [
            'nama' => 'name',
            'barcode' => 'barcode',
            'harga_jual' => 'sale_price',
            'harga_pokok' => 'cost_price',
            'stok' => 'stock',
            'created_at' => 'created_at',
        ];

        $products = Product::query()
            ->with(['category', 'primaryPhoto'])
            ->search($keyword)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($status === 'low', fn ($q) => $q->lowStock())
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy($columnMap[$sort], $direction)
            ->paginate(config('pos.per_page'))
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'keyword' => $keyword,
            'categoryId' => $categoryId,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Formulir tambah produk.
     */
    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['unit' => 'pcs', 'stock' => 0, 'min_stock' => 0, 'is_active' => true]),
            'categories' => Category::query()->active()->orderBy('name')->get(),
            'mode' => 'create',
        ]);
    }

    /**
     * Simpan produk baru.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'product.create',
                'model_type' => Product::class,
                'model_id' => $product->id,
                'description' => 'Produk "'.$product->name.'" ditambahkan.',
                'new_values' => $data,
            ]);

            return $product;
        });

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk "'.$product->name.'" berhasil ditambahkan. Silakan unggah fotonya dari halaman ubah produk.');
    }

    /**
     * Detail produk (termasuk galeri foto).
     */
    public function show(Product $product): View
    {
        $product->load(['category', 'photos']);

        /*
         * View "admin.products.show" menampilkan 20 mutasi stok terakhir,
         * jadi variabel $movements harus dikirim. Tanpa ini halaman 500
         * dengan pesan "Undefined variable $movements".
         */
        $movements = StockMovement::query()
            ->where('product_id', $product->id)
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.products.show', [
            'product' => $product,
            'movements' => $movements,
        ]);
    }

    /**
     * Formulir edit produk (termasuk panel galeri foto).
     */
    public function edit(Product $product): View
    {
        $product->load('photos');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->active()->orderBy('name')->get(),
            'mode' => 'edit',
        ]);
    }

    /**
     * Simpan perubahan produk.
     */
    public function update(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->only(['barcode', 'name', 'category_id', 'unit', 'cost_price', 'sale_price', 'stock', 'min_stock', 'is_active']);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        // Stok awal produk TIDAK boleh diubah lewat form edit.
        unset($data['stock']);

        DB::transaction(function () use ($product, $data, $old, $request) {
            $product->update($data);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'product.update',
                'model_type' => Product::class,
                'model_id' => $product->id,
                'description' => 'Produk "'.$product->name.'" diperbarui.',
                'old_values' => $old,
                'new_values' => $data,
            ]);
        });

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk "'.$product->name.'" berhasil diperbarui.');
    }

    /**
     * Tambah varian baru untuk produk.
     */
    public function storeVariant(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'size' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        $size = $data['size'] !== null ? trim((string) $data['size']) : null;
        $color = $data['color'] !== null ? trim((string) $data['color']) : null;

        if (! $size && ! $color) {
            return back()->with('error', 'Isi minimal ukuran atau warna untuk varian.');
        }

        $exists = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('size', $size)
            ->where('color', $color)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Varian tersebut sudah ada di produk ini.');
        }

        ProductVariant::create([
            'product_id' => $product->id,
            'size' => $size,
            'color' => $color,
            'stock' => (int) $data['stock'],
        ]);

        $this->syncProductStock($product);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'product.variant.create',
            'model_type' => Product::class,
            'model_id' => $product->id,
            'description' => 'Varian ('.($color ?: '-').' / '.($size ?: '-').') ditambahkan untuk produk "'.$product->name.'".',
        ]);

        return back()->with('success', 'Varian berhasil ditambahkan.');
    }

    /**
     * Hapus varian produk.
     */
    public function destroyVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $product = $variant->product;

        $variant->delete();

        $this->syncProductStock($product);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'product.variant.delete',
            'model_type' => Product::class,
            'model_id' => $product->id,
            'description' => 'Varian ('.($variant->color ?: '-').' / '.($variant->size ?: '-').') dihapus dari produk "'.$product->name.'".',
        ]);

        return back()->with('success', 'Varian berhasil dihapus.');
    }

    /**
     * Sinkronkan products.stock dengan akumulasi varian.
     * Produk non-varian dibiarkan apa adanya.
     */
    private function syncProductStock(Product $product): void
    {
        $variantCount = $product->variants()->count();

        if ($variantCount > 0) {
            $product->update(['stock' => (int) $product->variants()->sum('stock')]);
        }
    }

    /**
     * Generate barcode unik otomatis (format: TB-XXXXXX).
     *
     * Format: TB- diikuti 6 digit angka zero-padded (TB-000001 s/d TB-999999).
     * Jika tabrak dengan produk yang sudah ada, increment sampai ketemu
     * yang kosong. Mengembalikan JSON agar bisa dipakai AJAX di form.
     */
    public function generateBarcode(): JsonResponse
    {
        $prefix = 'TB-';

        /*
         * Tipe CAST untuk "urutkan angka" berbeda tiap database:
         * MySQL memakai UNSIGNED, SQLite memakai INTEGER. Tanpa ini, query
         * gagal dengan "You have an error in your SQL syntax" di MySQL.
         */
        $castType = DB::connection()->getDriverName() === 'mysql' ? 'UNSIGNED' : 'INTEGER';

        // Cari nomor urut tertinggi yang sudah dipakai.
        $last = Product::query()
            ->where('barcode', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTR(barcode, ' . (strlen($prefix) + 1) . ') AS ' . $castType . ') DESC')
            ->value('barcode');

        $next = 1;

        if ($last) {
            $number = (int) substr($last, strlen($prefix));
            $next = $number + 1;
        }

        // Cari slot kosong jika ada tabrakan (edge case).
        for ($i = 0; $i < 100; $i++) {
            $code = $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);

            if (! Product::query()->where('barcode', $code)->exists()) {
                return response()->json(['barcode' => $code]);
            }

            $next++;
        }

        // Fallback: generate random jika sequential penuh.
        do {
            $code = $prefix . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Product::query()->where('barcode', $code)->exists());

        return response()->json(['barcode' => $code]);
    }

    /**
     * Halaman print label barcode untuk satu produk.
     *
     * Menampilkan barcode dalam format CODE128 yang bisa di-print
     * ke label sticker. Menggunakan JsBarcode (rendering di sisi client).
     */
    public function printLabel(Product $product): View
    {
        return view('admin.products.barcode-label', [
            'product' => $product,
        ]);
    }

    /**
     * Halaman print label barcode untuk multiple produk (batch).
     */
    public function printLabels(Request $request): View
    {
        $ids = $request->query('ids', '');

        $products = Product::query()
            ->whereIn('id', array_filter(explode(',', $ids), fn ($id) => is_numeric($id)))
            ->orderBy('name')
            ->get();

        return view('admin.products.barcode-label', [
            'product' => null,
            'products' => $products,
        ]);
    }

    /**
     * Aktifkan / nonaktifkan produk.
     */
    public function toggleStatus(Request $request, Product $product): RedirectResponse
    {
        $newStatus = ! $product->is_active;

        $product->update(['is_active' => $newStatus]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'product.toggle_status',
            'model_type' => Product::class,
            'model_id' => $product->id,
            'description' => 'Produk "'.$product->name.'" '.($newStatus ? 'diaktifkan' : 'dinonaktifkan').'.',
            'old_values' => ['is_active' => ! $newStatus],
            'new_values' => ['is_active' => $newStatus],
        ]);

        return back()->with(
            'success',
            'Produk "'.$product->name.'" berhasil '.($newStatus ? 'diaktifkan' : 'dinonaktifkan').'.'
        );
    }
}
