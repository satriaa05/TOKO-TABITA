<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan identitas pembeli untuk transaksi online (guest checkout).
 * Semua kolom nullable; transaksi lama tidak terpengaruh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('buyer_name', 150)->nullable()->after('user_id');
            $table->string('buyer_phone', 30)->nullable()->after('buyer_name');
            $table->text('buyer_address')->nullable()->after('buyer_phone');
            $table->string('buyer_note', 255)->nullable()->after('buyer_address');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['buyer_name', 'buyer_phone', 'buyer_address', 'buyer_note']);
        });
    }
};
