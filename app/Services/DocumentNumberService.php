<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pembuat nomor dokumen (invoice penjualan & nomor pembelian).
 *
 * Format:
 *   INV-20261020-0001
 *   PO-20261020-0001
 *
 * Nomor dijamin unik karena kolomnya memakai constraint UNIQUE. Bila
 * terjadi bentrok (dua admin membuat dokumen bersamaan), nomor dibuat ulang.
 */
class DocumentNumberService
{
    /**
     * Nomor invoice penjualan berikutnya.
     */
    public function nextInvoiceNumber(): string
    {
        return $this->next('INV', Sale::class, 'invoice_number');
    }


    /**
     * @param  class-string  $model
     */
    private function next(string $prefix, string $model, string $column): string
    {
        $date = now()->format('Ymd');
        $base = $prefix.'-'.$date;

        // Lock tabel supaya dua request bersamaan tidak menghasilkan nomor sama.
        return DB::transaction(function () use ($model, $column, $base) {
            $last = $model::query()
                ->where($column, 'like', $base.'-%')
                ->orderByDesc($column)
                ->lockForUpdate()
                ->first();

            $sequence = $last
                ? ((int) Str::afterLast($last->{$column}, '-')) + 1
                : 1;

            return $base.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
