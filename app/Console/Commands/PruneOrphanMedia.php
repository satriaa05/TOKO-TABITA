<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Membersihkan berkas gambar yang tidak lagi dipakai.
 *
 * Berkas gambar disimpan di disk "public" pada folder media/. Bila tabel
 * media di-reset (mis. migrate:fresh saat pengembangan) atau unggahan
 * dibatalkan di tengah jalan, berkas di disk bisa tertinggal tanpa baris
 * media yang menunjukinya. Perintah ini mencari berkas seperti itu.
 *
 * Tanpa --delete perintah ini hanya melaporkan. Gunakan --delete untuk
 * benar-benar menghapus berkas yatim.
 */
class PruneOrphanMedia extends Command
{
    protected $signature = 'media:prune
                            {--delete : Hapus berkas yatim, bukan hanya melaporkan}';

    protected $description = 'Cek (dan bersihkan) berkas gambar yang tidak lagi dipakai';

    public function handle(): int
    {
        $disk = 'public';
        $folder = 'media';

        // Lokasi berkas yang masih dipakai baris media (format / sama).
        $dipakai = Media::query()
            ->pluck('path')
            ->filter()
            ->map(fn (string $path): string => str_replace('\\', '/', $path))
            ->all();

        $semua = Storage::disk($disk)->allFiles($folder);

        $yatim = array_values(array_filter(
            $semua,
            fn (string $path): bool => ! in_array(str_replace('\\', '/', $path), $dipakai, true)
        ));

        if ($yatim === []) {
            $this->info('Tidak ada berkas gambar yatim. Penyimpanan bersih.');

            return self::SUCCESS;
        }

        $this->warn(count($yatim).' berkas gambar tidak dipakai baris media mana pun:');

        foreach ($yatim as $path) {
            $this->line('  - '.$path);
        }

        if ($this->option('delete')) {
            Storage::disk($disk)->delete($yatim);
            $this->info(count($yatim).' berkas yatim dihapus dari penyimpanan.');
        } else {
            $this->comment('Mode laporan saja. Jalankan ulang dengan --delete untuk menghapusnya.');
        }

        return self::SUCCESS;
    }
}
