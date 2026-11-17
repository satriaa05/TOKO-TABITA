<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            // Nullify bila produk dihapus, agar riwayat penjualan tetap utuh.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Salinan data produk saat transaksi terjadi (snapshot historis).
            $table->string('product_name', 150);
            $table->string('barcode', 50)->nullable();

            $table->integer('qty');
            $table->decimal('price', 15, 2);       // harga jual saat transaksi
            $table->decimal('cost_price', 15, 2);  // harga pokok saat transaksi
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
