<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemisahan STATUS PEMBAYARAN (uang) dan STATUS PEMUHAN (barang).
 *
 * Kolom `status` lama sengaja tidak dihapus: laporan Owner memakainya
 * sebagai pendapatan (completed). Kolom baru bersifat tambahan dan aman
 * untuk data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->after('status')->index();
            $table->string('fulfillment_status', 25)->default('pending')->after('payment_status')->index();
            $table->timestamp('paid_at')->nullable()->after('fulfillment_status');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            $table->timestamp('completed_at')->nullable()->after('cancelled_at');

            $table->index(['payment_status', 'fulfillment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'fulfillment_status']);
            $table->dropColumn([
                'payment_status',
                'fulfillment_status',
                'paid_at',
                'cancelled_at',
                'completed_at',
            ]);
        });
    }
};
