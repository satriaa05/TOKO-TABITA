<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom size & color di sale_items agar detail pesanan menampilkan
 * varian yang dibeli pelanggan. Nullable — produk tanpa varian
 * tetap berjalan seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('size', 20)->nullable()->after('product_name');
            $table->string('color', 50)->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['size', 'color']);
        });
    }
};
