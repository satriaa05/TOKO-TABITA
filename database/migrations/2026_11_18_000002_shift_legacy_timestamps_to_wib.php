<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi sekali jalan: data lama disimpan dalam UTC (jam 19.00 WIB → 12.00).
 * Sesudah APP_TIMEZONE diubah ke Asia/Jakarta, timestamp lama harus
 * digeser +7 jam agar tampil sesuai waktu setempat.
 *
 * Hanya jalan di MySQL dan hanya sekali (flag di tabel settings).
 */
return new class extends Migration
{
    private const FLAG = 'timezone_shifted_to_wib_v1';

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('settings')->where('key', self::FLAG)->exists()) {
            return;
        }

        $this->shift(7);

        DB::table('settings')->insert([
            'key' => self::FLAG,
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! DB::table('settings')->where('key', self::FLAG)->exists()) {
            return;
        }

        $this->shift(-7);

        DB::table('settings')->where('key', self::FLAG)->delete();
    }

    private function shift(int $hours): void
    {
        $excluded = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens'];
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => (string) array_values((array) $row)[0])
            ->reject(fn ($table) => in_array($table, $excluded, true));

        foreach ($tables as $table) {
            $columns = collect(DB::select('SHOW COLUMNS FROM `'.$table.'`'))
                ->filter(fn ($col) => str_contains(strtolower((string) $col->Type), 'datetime')
                    || str_contains(strtolower((string) $col->Type), 'timestamp'))
                ->map(fn ($col) => (string) $col->Field);

            if ($columns->isEmpty()) {
                continue;
            }

            $sets = $columns
                ->map(fn ($col) => '`'.$col.'` = DATE_ADD(`'.$col.'`, INTERVAL '.$hours.' HOUR)')
                ->implode(', ');

            DB::statement('UPDATE `'.$table.'` SET '.$sets);
        }
    }
};
