<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('raw_status', 60);
            $table->string('normalized_status', 30)->index();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('event_time');
            $table->string('source', 20)->default('provider'); // provider | webhook | admin
            $table->timestamps();

            // Dedupe: webhook yang dikirim ulang tidak membuat event ganda.
            $table->unique(['shipment_id', 'raw_status', 'event_time', 'source'], 'ship_tracking_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_tracking_events');
    }
};
