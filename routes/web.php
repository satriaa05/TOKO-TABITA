<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Owner\MonitoringController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Http\Controllers\Owner\OwnerReportController;
use App\Http\Controllers\Owner\PaymentChannelController;
use App\Http\Controllers\Owner\StoreSettingController;
use App\Http\Controllers\Owner\UserController;
use App\Http\Controllers\Payment\MidtransWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shipping\BiteshipWebhookController;
use App\Http\Controllers\Store\CustomerAddressController;
use App\Http\Controllers\Store\NotificationController;
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

Route::post('shipping/biteship/webhook', BiteshipWebhookController::class)
    ->name('shipping.biteship.webhook');

Route::post('payments/midtrans/webhook', MidtransWebhookController::class)
    ->name('payments.midtrans.webhook');

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
    Route::patch('keranjang/{key}', [StoreController::class, 'updateCart'])->name('store.cart.update');
    Route::delete('keranjang/{key}', [StoreController::class, 'removeCartItem'])->name('store.cart.remove');
    Route::post('keranjang/kosongkan', [StoreController::class, 'clearCart'])->name('store.cart.clear');
    Route::get('checkout', [StoreController::class, 'checkout'])->name('store.checkout');
    Route::post('checkout', [StoreController::class, 'placeOrder'])
        ->middleware('throttle:5,1')
        ->name('store.placeOrder');
    Route::post('ongkir/cek', [StoreController::class, 'checkRates'])
        ->middleware('throttle:10,1')
        ->name('store.rates.check');
    Route::post('voucher/validasi', [StoreController::class, 'validateVoucher'])
        ->middleware('throttle:10,1')
        ->name('store.voucher.validate');
    Route::get('voucher/hapus', [StoreController::class, 'removeVoucher'])
        ->name('store.voucher.remove');
    Route::get('pembayaran/{sale}', [StoreController::class, 'payment'])->name('store.payment.show');
    Route::post('pembayaran/{sale}/cek-status', [StoreController::class, 'checkPaymentStatus'])
        ->middleware('throttle:20,1')
        ->name('store.payment.status');
    Route::post('pembayaran/{sale}/qr-baru', [StoreController::class, 'refreshQris'])
        ->middleware('throttle:5,1')
        ->name('store.payment.refresh');
    Route::get('pesanan/selesai/{sale}', [StoreController::class, 'orderSuccess'])->name('store.orderSuccess');
    Route::get('pesanan-saya', [StoreController::class, 'buyerOrders'])->name('store.orders.index');
    Route::get('pesanan-saya/{sale}', [StoreController::class, 'buyerOrder'])->name('store.orders.show');
    Route::get('pesanan-saya/{sale}/lacak', [StoreController::class, 'tracking'])->name('store.orders.tracking');
    Route::get('pesanan-saya/{sale}/lacak/partial', [StoreController::class, 'trackingPartial'])
        ->name('store.orders.tracking.partial');
    Route::post('pesanan-saya/{sale}/bukti', [StoreController::class, 'uploadProof'])->name('store.orders.proof');

    Route::get('alamat', [CustomerAddressController::class, 'index'])->name('store.addresses.index');
    Route::get('alamat/tambah', [CustomerAddressController::class, 'create'])->name('store.addresses.create');
    Route::post('alamat', [CustomerAddressController::class, 'store'])->name('store.addresses.store');
    Route::get('alamat/{address}/edit', [CustomerAddressController::class, 'edit'])->name('store.addresses.edit');
    Route::put('alamat/{address}', [CustomerAddressController::class, 'update'])->name('store.addresses.update');
    Route::delete('alamat/{address}', [CustomerAddressController::class, 'destroy'])->name('store.addresses.destroy');
    Route::post('alamat/{address}/utama', [CustomerAddressController::class, 'setDefault'])->name('store.addresses.default');

    Route::get('notifikasi', [NotificationController::class, 'index'])->name('store.notifications.index');
    Route::post('notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])
        ->name('store.notifications.read');
    Route::post('notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])
        ->name('store.notifications.readAll');

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

    Route::get('profil', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profil/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('profil/foto', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::delete('profil/foto', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');

    // Pintu masuk tunggal ke dashboard sesuai peran pengguna.
    Route::get('dashboard', function () {
        return redirect()->route(Auth::user()->role->dashboardRoute());
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | PRODUK & GALERI - Admin & Owner (Owner selalu diizinkan permission)
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin,owner', 'permission:products.manage'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('produk', [ProductController::class, 'index'])->name('products.index');
            Route::get('produk/tambah', [ProductController::class, 'create'])->name('products.create');
            Route::post('produk', [ProductController::class, 'store'])->name('products.store');
            Route::get('produk/{product}', [ProductController::class, 'show'])->name('products.show');
            Route::get('produk/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('produk/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::patch('produk/{product}/status', [ProductController::class, 'toggleStatus'])->name('products.toggle');

            Route::post('produk/{product}/varian', [ProductController::class, 'storeVariant'])->name('variants.store');
            Route::put('produk/varian/{variant}', [ProductController::class, 'updateVariant'])->name('variants.update');
            Route::patch('produk/varian/{variant}/status', [ProductController::class, 'toggleVariant'])->name('variants.toggle');
            Route::delete('produk/varian/{variant}', [ProductController::class, 'destroyVariant'])->name('variants.destroy');

            Route::post('produk/{product}/warna', [ProductController::class, 'storeColor'])->name('colors.store');
            Route::put('produk/warna/{color}', [ProductController::class, 'updateColor'])->name('colors.update');
            Route::patch('produk/warna/{color}/status', [ProductController::class, 'toggleColor'])->name('colors.toggle');
            Route::delete('produk/warna/{color}', [ProductController::class, 'destroyColor'])->name('colors.destroy');

            Route::get('produk/barcode/generate', [ProductController::class, 'generateBarcode'])->name('products.barcode.generate');
            Route::get('produk/{product}/label', [ProductController::class, 'printLabel'])->name('products.label');
            Route::get('produk/label/cetak', [ProductController::class, 'printLabels'])->name('products.labels.print');

            Route::post('produk/{product}/foto', [MediaController::class, 'storeProductPhotos'])->name('products.photos.store');
        });

    Route::middleware(['role:admin,owner', 'permission:promotions.manage'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('promo', [PromotionController::class, 'index'])->name('promotions.index');
            Route::get('promo/tambah', [PromotionController::class, 'create'])->name('promotions.create');
            Route::post('promo', [PromotionController::class, 'store'])->name('promotions.store');
            Route::get('promo/{promotion}/edit', [PromotionController::class, 'edit'])->name('promotions.edit');
            Route::put('promo/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
            Route::patch('promo/{promotion}/status', [PromotionController::class, 'toggleStatus'])->name('promotions.toggle');
        });

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
             | Pesanan Online (guest checkout) - daftar & konfirmasi status
            |------------------------------------------------------------------*/
            Route::get('pesanan', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
            Route::get('pesanan/{sale}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
            Route::post('pesanan/{sale}/selesai', [OrderController::class, 'complete'])
                ->middleware('permission:orders.manage')->name('orders.complete');
            Route::post('pesanan/{sale}/batalkan', [OrderController::class, 'refund'])
                ->middleware('permission:orders.manage')->name('orders.refund');

            /*------------------------------------------------------------------
            | Pembayaran - monitoring & verifikasi manual
            |------------------------------------------------------------------*/
            Route::get('pembayaran', [PaymentController::class, 'index'])->middleware('permission:payments.verify')->name('payments.index');
            Route::get('pembayaran/{sale}', [PaymentController::class, 'show'])->middleware('permission:payments.verify')->name('payments.show');
            Route::post('pembayaran/bukti/{proof}/setujui', [PaymentController::class, 'approveProof'])
                ->middleware('permission:payments.verify')->name('payments.proof.approve');
            Route::post('pembayaran/bukti/{proof}/tolak', [PaymentController::class, 'rejectProof'])
                ->middleware('permission:payments.verify')->name('payments.proof.reject');
            Route::post('pembayaran/{sale}/sinkron', [PaymentController::class, 'syncGateway'])
                ->middleware('permission:payments.verify')->name('payments.sync');

            /*------------------------------------------------------------------
            | Pengiriman - input resi, status, dan sinkronisasi Biteship
            |------------------------------------------------------------------*/
            Route::get('pengiriman', [ShipmentController::class, 'index'])->middleware('permission:shipments.manage')->name('shipments.index');
            Route::get('pengiriman/{sale}/tambah', [ShipmentController::class, 'create'])->middleware('permission:shipments.manage')->name('shipments.create');
            Route::post('pengiriman/{sale}', [ShipmentController::class, 'store'])->middleware('permission:shipments.manage')->name('shipments.store');
            Route::get('pengiriman/detail/{shipment}', [ShipmentController::class, 'show'])->middleware('permission:shipments.manage')->name('shipments.show');
            Route::post('pengiriman/detail/{shipment}/status', [ShipmentController::class, 'updateStatus'])
                ->middleware('permission:shipments.manage')->name('shipments.status');
            Route::post('pengiriman/detail/{shipment}/sinkron', [ShipmentController::class, 'sync'])
                ->middleware('permission:shipments.manage')->name('shipments.sync');

            /*------------------------------------------------------------------
            | Kategori
            |------------------------------------------------------------------*/
            Route::get('kategori', [CategoryController::class, 'index'])->middleware('permission:categories.manage')->name('categories.index');
            Route::post('kategori', [CategoryController::class, 'store'])->middleware('permission:categories.manage')->name('categories.store');
            Route::put('kategori/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.manage')->name('categories.update');
            Route::delete('kategori/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.manage')->name('categories.destroy');

            /*------------------------------------------------------------------
            | Gambar (media) - produk, kategori & aset tampilan toko
            |------------------------------------------------------------------*/
            Route::post('kategori/{category}/foto', [MediaController::class, 'storeCategoryPhoto'])
                ->middleware('permission:categories.manage')->name('categories.photo.store');
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
            Route::get('tampilan', [MediaController::class, 'siteIndex'])->middleware('permission:media.manage')->name('media.site');

            Route::get('audit-log', [AuditLogController::class, 'index'])
                ->middleware('permission:reports.view')->name('audit-logs.index');

            Route::post('tampilan/{role}', [MediaController::class, 'storeSite'])
                ->whereIn('role', ['logo', 'banner_home', 'banner_promo', 'collection'])
                ->middleware('permission:media.manage')->name('media.site.store');

            Route::patch('foto/{media}/utama', [MediaController::class, 'setPrimary'])
                ->middleware('permission:media.manage')->name('media.primary');
            Route::patch('foto/{media}/utama-warna', [MediaController::class, 'setColorPrimary'])
                ->middleware('permission:media.manage')->name('media.color.primary');
            Route::patch('foto/{media}/warna', [MediaController::class, 'updateColor'])
                ->middleware('permission:media.manage')->name('media.color');
            Route::patch('foto/{media}', [MediaController::class, 'replace'])
                ->middleware('permission:media.manage')->name('media.replace');
            Route::post('foto/urutkan', [MediaController::class, 'reorder'])
                ->middleware('permission:media.manage')->name('media.reorder');
            Route::delete('foto/{media}', [MediaController::class, 'destroy'])
                ->middleware('permission:media.manage')->name('media.destroy');
        });

    /*
    |----------------------------------------------------------------------
    | Invoice / cetak struk — Admin & Owner
    |----------------------------------------------------------------------
    */
    Route::middleware(['role:admin,owner', 'permission:orders.view'])
        ->get('invoice/{sale}', [InvoiceController::class, 'show'])
        ->name('invoice.show');

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
            Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

            /*------------------------------------------------------------------
            | Laporan (hanya baca) - data nyata dari SalesReportService
            |------------------------------------------------------------------*/
            Route::get('laporan/penjualan', [OwnerReportController::class, 'sales'])->middleware('permission:reports.view')->name('reports.sales');
            Route::get('laporan/produk', [OwnerReportController::class, 'products'])->middleware('permission:reports.view')->name('reports.products');
            Route::get('laporan/laba-kotor', [OwnerReportController::class, 'profit'])->middleware('permission:reports.view')->name('reports.profit');
            Route::get('laporan/inventori', [OwnerReportController::class, 'inventory'])->middleware('permission:reports.view')->name('reports.inventory');

            /*------------------------------------------------------------------
            | Pengaturan Bisnis - identitas toko, WhatsApp, Instagram, pembayaran
            |------------------------------------------------------------------*/
            Route::get('pengaturan', [StoreSettingController::class, 'index'])->middleware('permission:settings.manage')->name('settings.index');
            Route::put('pengaturan', [StoreSettingController::class, 'update'])->middleware('permission:settings.manage')->name('settings.update');

            Route::resource('pembayaran-metode', PaymentChannelController::class)
                ->except(['show'])
                ->middleware('permission:settings.manage')
                ->names('payment-channels');

            /*------------------------------------------------------------------
            | Manajemen akun & permission (khusus Owner)
            |------------------------------------------------------------------*/
            Route::get('pengguna', [UserController::class, 'index'])->middleware('permission:users.manage')->name('users.index');
            Route::get('pengguna/tambah', [UserController::class, 'create'])->middleware('permission:users.manage')->name('users.create');
            Route::post('pengguna', [UserController::class, 'store'])->middleware('permission:users.manage')->name('users.store');
            Route::get('pengguna/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.manage')->name('users.edit');
            Route::put('pengguna/{user}', [UserController::class, 'update'])->middleware('permission:users.manage')->name('users.update');
            Route::delete('pengguna/{user}', [UserController::class, 'destroy'])->middleware('permission:users.manage')->name('users.destroy');
            Route::post('pengguna/{user}/status', [UserController::class, 'toggle'])->middleware('permission:users.manage')->name('users.toggle');
        });
});
