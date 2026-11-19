<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            $table->string('courier', 40);
            $table->string('service', 60)->nullable();
            $table->string('tracking_number', 100)->nullable();

            // Referensi dari Biteship bila order dibuat lewat API mereka.
            $table->string('biteship_order_id', 100)->nullable()->unique();
            $table->string('biteship_tracking_id', 100)->nullable()->unique();

            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->string('shipment_status', 30)->default('pending')->index();

            // scan = JNE/J&T/reguler; live = kurir instant (GoSend/GrabExpress).
            $table->string('tracking_mode', 10)->default('scan');
            $table->string('live_tracking_url', 500)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_plate', 30)->nullable();

            $table->dateTime('estimated_delivery_start')->nullable();
            $table->dateTime('estimated_delivery_end')->nullable();

            $table->decimal('last_scan_latitude', 10, 7)->nullable();
            $table->decimal('last_scan_longitude', 10, 7)->nullable();
            $table->text('last_scan_location')->nullable();
            $table->timestamp('last_scan_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index(['sale_id', 'shipment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
