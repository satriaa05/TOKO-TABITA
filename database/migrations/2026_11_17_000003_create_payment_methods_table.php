<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog metode pembayaran CHECKOUT (bukan POS).
 *
 * Beda dengan config('pos.payments') yang hanya dipakai footer: tabel ini
 * menentukan metode mana yang benar-benar bisa dipilih pelanggan, apakah
 * diverifikasi otomatis (Midtrans) atau manual (transfer/rekening toko).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 80);
            $table->string('provider', 30)->nullable();
            $table->string('mode', 20)->default('manual'); // automatic | manual
            $table->string('logo_path', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
