<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Varian warna sebagai entitas + galeri foto per warna.
 *
 * Kolom lama (product_variants.color, media.color, sale_items.color) sengaja
 * DIPERTAHANKAN agar transaksi & media lama tetap terbaca. Kolom baru bersifat
 * nullable dan di-backfill dari data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 60);
            $table->string('hex', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('thumbnail_media_id')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'name']);
            $table->index(['product_id', 'is_active', 'sort_order']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->after('color')->constrained('product_colors')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('stock');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            $table->index(['product_id', 'is_active', 'sort_order']);
        });

        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->after('color')->constrained('product_colors')->nullOnDelete();
            $table->boolean('is_color_primary')->default(false)->after('is_primary');
            $table->index(['mediable_type', 'mediable_id', 'role', 'color_id']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('color')->constrained('product_variants')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->after('product_variant_id')->constrained('product_colors')->nullOnDelete();
            $table->unsignedBigInteger('media_id')->nullable()->after('color_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->string('color', 50)->nullable()->after('variant_id');
            $table->string('size', 20)->nullable()->after('color');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'color', 'size']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['product_variant_id', 'color_id', 'media_id']);
        });
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['color_id', 'is_color_primary']);
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['color_id', 'is_active', 'sort_order']);
        });
        Schema::dropIfExists('product_colors');
    }

    /**
     * Isi data warna dari string lama. Dijalankan per baris via Query Builder
     * agar kompatibel dengan SQLite (digunakan test) maupun MySQL (hosting).
     */
    private function backfill(): void
    {
        $pairs = collect();

        foreach (DB::table('product_variants')->whereNotNull('color')->get(['product_id', 'color']) as $row) {
            $name = trim((string) $row->color);
            if ($name !== '') {
                $pairs->push(['product_id' => (int) $row->product_id, 'name' => $name]);
            }
        }

        foreach (DB::table('media')->where('mediable_type', 'product')->whereNotNull('color')->get(['mediable_id', 'color']) as $row) {
            $name = trim((string) $row->color);
            if ($name !== '') {
                $pairs->push(['product_id' => (int) $row->mediable_id, 'name' => $name]);
            }
        }

        $colorIds = [];

        foreach ($pairs->unique(fn ($pair) => $pair['product_id'].'|'.$pair['name'])->values() as $index => $pair) {
            $existing = DB::table('product_colors')
                ->where('product_id', $pair['product_id'])
                ->where('name', $pair['name'])
                ->first();

            if ($existing) {
                $colorIds[$pair['product_id'].'|'.$pair['name']] = (int) $existing->id;

                continue;
            }

            $id = DB::table('product_colors')->insertGetId([
                'product_id' => $pair['product_id'],
                'name' => $pair['name'],
                'slug' => Str::slug($pair['name']),
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $colorIds[$pair['product_id'].'|'.$pair['name']] = (int) $id;
        }

        foreach (DB::table('product_variants')->whereNotNull('color')->get(['id', 'product_id', 'color']) as $row) {
            $key = (int) $row->product_id.'|'.trim((string) $row->color);
            if (isset($colorIds[$key])) {
                DB::table('product_variants')->where('id', $row->id)->update(['color_id' => $colorIds[$key]]);
            }
        }

        foreach (DB::table('media')->where('mediable_type', 'product')->whereNotNull('color')->get(['id', 'mediable_id', 'color']) as $row) {
            $key = (int) $row->mediable_id.'|'.trim((string) $row->color);
            if (isset($colorIds[$key])) {
                DB::table('media')->where('id', $row->id)->update(['color_id' => $colorIds[$key]]);
            }
        }

        foreach ($colorIds as $colorId) {
            $first = DB::table('media')
                ->where('color_id', $colorId)
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($first) {
                DB::table('media')->where('id', $first->id)->update(['is_color_primary' => true]);
                DB::table('product_colors')->where('id', $colorId)->update(['thumbnail_media_id' => $first->id]);
            }
        }
    }
};
