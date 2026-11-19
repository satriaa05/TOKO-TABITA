<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Koordinat hanya diisi jika provider benar-benar memberikan nilai valid.
 * Tidak boleh diisi dengan koordinat hasil tebakan/geocoding palsu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('location_description')->nullable();
            $table->timestamp('recorded_at');
            $table->string('source', 20)->default('provider');
            $table->timestamps();

            $table->index(['shipment_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_locations');
    }
};
