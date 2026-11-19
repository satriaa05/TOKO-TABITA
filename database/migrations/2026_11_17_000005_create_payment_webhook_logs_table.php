<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat webhook. Payload disimpan sudah di-sanitize: tidak ada
 * server key, client key, atau secret lain yang boleh masuk ke sini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->index();
            $table->string('event_id', 150)->unique();
            $table->string('transaction_reference', 100)->nullable()->index();
            $table->json('sanitized_payload');
            $table->boolean('signature_valid')->default(false);
            $table->string('processing_status', 30)->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'processing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
