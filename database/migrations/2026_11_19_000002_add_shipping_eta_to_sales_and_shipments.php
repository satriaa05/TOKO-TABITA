<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('shipping_eta', 60)->nullable()->after('shipping_cost');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('shipping_eta', 60)->nullable()->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('shipping_eta');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('shipping_eta');
        });
    }
};
