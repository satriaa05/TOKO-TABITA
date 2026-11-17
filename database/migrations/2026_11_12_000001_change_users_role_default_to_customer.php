<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peran bawaan pengguna diubah dari "admin" menjadi "customer".
     *
     * Sebelumnya kolom role default-nya "admin", sehingga akun yang dibuat
     * tanpa peran eksplisit (lewat jalur lain, misalnya import atau
     * pendaftaran yang lupa set role) otomatis menjadi admin.
     * Akun yang memang admin/owner tidak berubah.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'customer'");
        }

        // SQLite tidak mendukung ubah default kolom lewat ALTER, jadi
        // di lingkungan uji nilainya dibiarkan. Yang penting adalah server
        // MySQL (produksi & pengembangan).
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'admin'");
        }
    }
};
