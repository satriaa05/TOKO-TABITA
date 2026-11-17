<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);

            // percent | fixed
            $table->string('type', 20);
            $table->decimal('value', 15, 2);

            $table->date('start_date');
            $table->date('end_date');

            // Minimum belanja agar promo berlaku.
            $table->decimal('min_purchase', 15, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
