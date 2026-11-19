<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('old_payment_status', 20)->nullable();
            $table->string('new_payment_status', 20)->nullable();
            $table->string('old_fulfillment_status', 25)->nullable();
            $table->string('new_fulfillment_status', 25)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('system'); // webhook | admin | system | gateway
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
