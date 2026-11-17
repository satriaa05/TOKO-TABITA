<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Http\Controllers\Owner\OwnerReportController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Tamu (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('login/google', [GoogleAuthController::class, 'redirect'])->name('login.google');
    Route::get('login/google/callback', [GoogleAuthController::class, 'callback'])->name('login.google.callback');
});

/*
|--------------------------------------------------------------------------
| Landing / Beranda Toko
|--------------------------------------------------------------------------
*/
Route::get('/', [StoreController::class, 'home'])->name('home');

/*
|--------------------------------------------------------------------------
| Katalog & Detail Produk (tamu boleh baca)
|--------------------------------------------------------------------------
*/
Route::get('katalog', [StoreController::class, 'catalog'])->name('store.catalog');
Route::get('produk/{product}', [StoreController::class, 'productShow'])->name('store.product');
Route::post('newsletter', [StoreController::class, 'subscribe'])
    ->middleware('throttle:3,1')
    ->name('store.subscribe');

/*
|--------------------------------------------------------------------------
| Halaman informasi statis (tentang, panduan ukuran, pengiriman)
|--------------------------------------------------------------------------
*/
Route::view('tentang', 'store.pages.about')->name('page.about');
Route::view('panduan-ukuran', 'store.pages.size-guide')->name('page.size-guide');
Route::view('pengiriman-pengembalian', 'store.pages.shipping')->name('page.shipping');

/*
|--------------------------------------------------------------------------
| Keranjang & Checkout — WAJIB LOGIN (login gate pembelian)
|--------------------------------------------------------------------------
|
| Pengunjung boleh melihat homepage, katalog & detail produk.
| Add to Cart / Buy Now / Checkout wajib login atau register;
| intended URL tersimpan; setelah login kembali ke aksi semula.
|
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('keranjang', [StoreController::class, 'cart'])->name('store.cart');
    Route::post('keranjang/tambah', [StoreController::class, 'addToCart'])->name('store.cart.add');
    Route::patch('keranjang/{key}', [StoreController::class, 'updateCart'])->name('store.cart.update')->where('key', '[A-Za-z0-9\-\~]+');
    Route::delete('keranjang/{key}', [StoreController::class, 'removeCartItem'])->name('store.cart.remove')->where('key', '[A-Za-z0-9\-\~]+');
    Route::post('keranjang/kosongkan', [StoreController::class, 'clearCart'])->name('store.cart.clear');
    Route::get('checkout', [StoreController::class, 'checkout'])->name('store.checkout');
    Route::post('checkout', [StoreController::class, 'placeOrder'])
        ->middleware('throttle:5,1')
        ->name('store.placeOrder');
    Route::get('pesanan/selesai/{sale}', [StoreController::class, 'orderSuccess'])->name('store.orderSuccess');
    Route::get('pesanan-saya', [StoreController::class, 'buyerOrders'])->name('store.orders.index');
    Route::get('pesanan-saya/{sale}', [StoreController::class, 'buyerOrder'])->name('store.orders.show');
    Route::post('pesanan-saya/{sale}/bukti', [StoreController::class, 'uploadProof'])->name('store.orders.proof');
    Route::get('wishlist', [StoreController::class, 'wishlist'])->name('store.wishlist');
    Route::post('wishlist/{product}/toggle', [StoreController::class, 'toggleWishlist'])->name('store.wishlist.toggle');
});

/*
|--------------------------------------------------------------------------
| Rute Terproteksi Login
|--------------------------------------------------------------------------
|
| Seluruh rute di bawah ini membutuhkan session login yang valid DAN
| akun berstatus aktif.
|
*/
Route::middleware(['auth', 'active'])->group(function () {

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Pintu masuk tunggal ke dashboard sesuai peran pengguna.
    Route::get('dashboard', function () {
        return redirect()->route(Auth::user()->role->dashboardRoute());
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | ADMIN - Konfigurasi & operasional toko baju
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            /*------------------------------------------------------------------
            | Produk
            |------------------------------------------------------------------*/
            Route::get('produk', [ProductController::class, 'index'])->name('products.index');
            Route::get('produk/tambah', [ProductController::class, 'create'])->name('products.create');
            Route::post('produk', [ProductController::class, 'store'])->name('products.store');
            Route::get('produk/{product}', [ProductController::class, 'show'])->name('products.show');
            Route::get('produk/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('produk/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::post('produk/{product}/varian', [ProductController::class, 'storeVariant'])->name('variants.store');
            Route::delete('produk/varian/{variant}', [ProductController::class, 'destroyVariant'])->name('variants.destroy');
            Route::patch('produk/{product}/status', [ProductController::class, 'toggleStatus'])
                ->name('products.toggle');

            // Generate barcode otomatis (AJAX) + print label
            Route::get('produk/barcode/generate', [ProductController::class, 'generateBarcode'])
                ->name('products.barcode.generate');
            Route::get('produk/{product}/label', [ProductController::class, 'printLabel'])
                ->name('products.label');
            Route::get('produk/label/cetak', [ProductController::class, 'printLabels'])
                ->name('products.labels.print');

            /*------------------------------------------------------------------
            | Pesanan Online (guest checkout) - daftar & konfirmasi status
            |------------------------------------------------------------------*/
            Route::get('pesanan', [OrderController::class, 'index'])->name('orders.index');
            Route::get('pesanan/{sale}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('pesanan/{sale}/selesai', [OrderController::class, 'complete'])
                ->name('orders.complete');
            Route::post('pesanan/{sale}/batalkan', [OrderController::class, 'refund'])
                ->name('orders.refund');

            /*------------------------------------------------------------------
            | Kategori
            |------------------------------------------------------------------*/
            Route::get('kategori', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('kategori', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('kategori/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('kategori/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            /*------------------------------------------------------------------
            | Gambar (media) - produk, kategori & aset tampilan toko
            |------------------------------------------------------------------*/
            Route::post('produk/{product}/foto', [MediaController::class, 'storeProductPhotos'])
                ->name('products.photos.store');
            Route::post('kategori/{category}/foto', [MediaController::class, 'storeCategoryPhoto'])
                ->name('categories.photo.store');

            /*------------------------------------------------------------------
            | Promo
            |------------------------------------------------------------------*/
            Route::get('promo', [PromotionController::class, 'index'])->name('promotions.index');
            Route::get('promo/tambah', [PromotionController::class, 'create'])->name('promotions.create');
            Route::post('promo', [PromotionController::class, 'store'])->name('promotions.store');
            Route::get('promo/{promotion}/edit', [PromotionController::class, 'edit'])->name('promotions.edit');
            Route::put('promo/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
            Route::patch('promo/{promotion}/status', [PromotionController::class, 'toggleStatus'])
                ->name('promotions.toggle');
        });

    /*
    |----------------------------------------------------------------------
    | ASET MEDIA & TAMPILAN TOKO - Admin & Owner
    |----------------------------------------------------------------------
    |
    | Logo, banner homepage, banner promosi, foto koleksi terbaru dan
    | foto kategori/produk dikelola dari sini. Admin & Owner sesuai
    | izin; operasi galeri generik dicek per-baris di MediaController:
    | admin boleh semua, owner hanya aset site (tampilan toko).
    |
    */
    Route::middleware('role:admin,owner')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('tampilan', [MediaController::class, 'siteIndex'])->name('media.site');

            Route::post('tampilan/{role}', [MediaController::class, 'storeSite'])
                ->whereIn('role', ['logo', 'banner_home', 'banner_promo', 'collection'])
                ->name('media.site.store');

            Route::patch('foto/{media}/utama', [MediaController::class, 'setPrimary'])
                ->name('media.primary');
            Route::patch('foto/{media}/warna', [MediaController::class, 'updateColor'])
                ->name('media.color');
            Route::patch('foto/{media}', [MediaController::class, 'replace'])
                ->name('media.replace');
            Route::post('foto/urutkan', [MediaController::class, 'reorder'])
                ->name('media.reorder');
            Route::delete('foto/{media}', [MediaController::class, 'destroy'])
                ->name('media.destroy');
        });

    /*
    |----------------------------------------------------------------------
    | OWNER - Pemantauan kinerja bisnis (hanya baca)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:owner')
        ->prefix('owner')
        ->name('owner.')
        ->group(function () {
            Route::get('dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');

            /*------------------------------------------------------------------
            | Laporan (hanya baca) - data nyata dari SalesReportService
            |------------------------------------------------------------------*/
            Route::get('laporan/penjualan', [OwnerReportController::class, 'sales'])->name('reports.sales');
            Route::get('laporan/produk', [OwnerReportController::class, 'products'])->name('reports.products');
            Route::get('laporan/laba-kotor', [OwnerReportController::class, 'profit'])->name('reports.profit');
            Route::get('laporan/inventori', [OwnerReportController::class, 'inventory'])->name('reports.inventory');
        });
});
