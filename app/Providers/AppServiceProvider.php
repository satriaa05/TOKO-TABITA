<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Services\StoreProfile;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu instance per request: seluruh halaman membaca profil toko
        // (settings + config) lewat satu query yang sama.
        $this->app->scoped(StoreProfile::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aplikasi memakai Bootstrap 5, jadi paginasi memakai view Bootstrap 5
        // (bukan Tailwind bawaan Laravel).
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');

        /*
         * Alias relasi polymorphic untuk tabel media. Nilai yang tersimpan di
         * kolom mediable_type jadi stabil ("product", "category", "site")
         * sehingga data gambar tetap valid meski namespace model berubah.
         */
        Relation::morphMap([
            'product' => Product::class,
            'category' => Category::class,
        ]);
    }
}
