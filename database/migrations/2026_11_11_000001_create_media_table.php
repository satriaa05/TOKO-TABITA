<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel media menyimpan LOKASI berkas (bukan isi berkas) di database,
     * sehingga gambar dapat diganti/hapus/diatur urutannya oleh admin tanpa
     * menyentuh source code. Berkas aslinya disimpan lewat Laravel Storage
     * pada disk "public" (storage/app/public/media/...).
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            /*
             * Pemilik gambar, polymorphic:
             *  - "product"  + mediable_id  -> galeri foto produk
             *  - "category" + mediable_id  -> foto kategori
             *  - "site"     + mediable_id NULL -> aset toko (logo, banner, koleksi)
             */
            $table->string('mediable_type', 64)->index();
            $table->unsignedBigInteger('mediable_id')->nullable()->index();

            /*
             * Fungsi gambar:
             *  - product  : "gallery"
             *  - category : "photo"
             *  - site     : "logo", "banner_home", "banner_promo", "collection"
             */
            $table->string('role', 32)->default('gallery')->index();

            // Label variasi warna (mis. "Hitam", "Putih"). Boleh kosong.
            $table->string('color', 50)->nullable();

            // Disk Laravel Storage + lokasi relatif berkas pada disk itu.
            $table->string('disk', 32)->default('public');
            $table->string('path');

            // Metadata untuk tampilan & pengelolaan.
            $table->string('original_name')->nullable();
            $table->string('mime', 64)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Foto utama produk/galeri + urutan tampil di galeri.
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->timestamps();

            // Indeks gabungan untuk ambil galeri satu target dengan cepat.
            $table->index(['mediable_type', 'mediable_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
