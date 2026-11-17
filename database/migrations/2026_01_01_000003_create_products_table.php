<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 50)->unique();
            $table->string('name', 150)->index();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('unit', 20)->default('pcs');

            // Nilai uang memakai DECIMAL, bukan floating point.
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);

            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indeks gabungan untuk filter "produk aktif + stok menipis".
            $table->index(['is_active', 'stock']);
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
