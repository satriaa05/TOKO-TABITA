<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda apakah stok dari satu pesanan sudah dikembalikan.
 *
 * Stok dipotong sekali saat checkout. Bila pembayaran gagal/expired/refund,
 * stok harus dikembalikan tepat satu kali. Kolom ini dipakai sebagai
 * idempotency guard supaya webhook gagal + admin membatalkan tidak mengembalikan
 * stok dua kali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('stock_released_at')->nullable()->after('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('stock_released_at');
        });
    }
};
