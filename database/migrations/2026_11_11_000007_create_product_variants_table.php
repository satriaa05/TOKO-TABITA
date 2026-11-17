<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Varian produk (ukuran & warna) dengan stok masing-masing.
 *
 * Catatan: products.stock tetap dipakai sebagai TOTAL stok (dijumlah dari
 * varian bila produk punya varian) agar StockService & peringatan stok
 * menipis tetap bekerja tanpa perubahan. Decrement dilakukan keduanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('size', 20)->nullable();
            $table->string('color', 50)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'size', 'color']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
