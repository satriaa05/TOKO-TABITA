<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu percobaan pembayaran untuk satu pesanan.
 * Bisa lebih dari satu baris jika pelanggan mengulang checkout setelah
 * transaksi sebelumnya expired/gagal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();

            $table->string('transaction_reference', 100)->unique();
            $table->string('provider_reference', 150)->nullable()->unique();
            $table->string('idempotency_key', 80)->unique();

            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('unpaid')->index();
            $table->string('payment_url', 500)->nullable();
            $table->string('qr_code_url', 500)->nullable();
            $table->string('deeplink_url', 500)->nullable();
            $table->string('acquirer', 30)->nullable();
            $table->timestamp('expired_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('failure_detail')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['sale_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
