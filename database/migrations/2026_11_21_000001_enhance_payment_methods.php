<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Metode pembayaran bisa ditampilkan sebelum gateway aktif, menyimpan rekening
 * bank, dan dikelola penuh oleh Owner lewat UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('is_visible')->default(false)->after('is_active');
            $table->string('status', 20)->default('available')->after('is_visible');
            $table->string('status_note', 255)->nullable()->after('status');
            $table->string('bank_name', 50)->nullable()->after('provider');
            $table->string('account_holder', 100)->nullable()->after('bank_name');
            $table->string('account_number', 30)->nullable()->after('account_holder');
        });

        $gatewayReady = filled(config('payments.midtrans.server_key'));

        DB::table('payment_methods')->where('code', 'transfer')->update([
            'bank_name' => 'BCA',
            'account_holder' => 'RETNO ADWIANTI',
            'account_number' => '7963069288',
            'is_visible' => true,
            'status' => 'available',
        ]);

        DB::table('payment_methods')
            ->whereIn('code', ['qris', 'gopay', 'ovo', 'dana', 'shopeepay', 'va'])
            ->update([
                'is_visible' => true,
                'status' => $gatewayReady ? 'available' : 'coming_soon',
                'status_note' => $gatewayReady
                    ? null
                    : 'Menunggu kredensial Midtrans dari Admin.',
            ]);
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn([
                'is_visible',
                'status',
                'status_note',
                'bank_name',
                'account_holder',
                'account_number',
            ]);
        });
    }
};
