<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom ongkir ke tabel sales agar biaya pengiriman
     * tersimpan bersama detail pesanan online.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('total');
            $table->string('shipping_zone', 32)->nullable()->after('shipping_cost');
            $table->string('shipping_provider', 64)->nullable()->after('shipping_zone');
        });
    }

    /**
     * Kembalikan struktur tabel sales (untuk rollback development).
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['shipping_cost', 'shipping_zone', 'shipping_provider']);
        });
    }
};
