<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TabitaProductsSeeder extends Seeder
{
    /**
     * Data produk pakaian wanita (Tabita Modiste) beserta barcode.
     *
     * Foto produk disisakan sebagai placeholder (tidak diunduh dari
     * Pinterest/Google tanpa lisensi). Admin dapat mengunggah foto asli
     * lewat halaman "Tampilan Toko" dan form produk di dashboard.
     */
    public function run(): void
    {
        $kategori = [
            'Casual Wear' => 'Pakaian kasual nyaman untuk aktivitas sehari-hari.',
            'Elegant Dresses' => 'Gamis elegan untuk acara spesial dan formal.',
            'Party Dresses' => 'Gamis pesta dengan desain menarik dan elegan.',
            'Workwear' => 'Pakaian kerja yang profesional dan nyaman.',
            'Tops & Tees' => 'Atasan kaos dan blouse untuk gaya casual.',
            'Pants' => 'Celana panjang berbagai model dan bahan.',
            'Skirts' => 'Rok berbagai panjang dan gaya.',
            'Outerwear' => 'Jaket dan outfit luar untuk pelengkap penampilan.',
            'Hijab' => 'Keranji dan hijab untuk gaya yang syar\'i dan elegan.',
        ];

        $categoryIds = [];
        foreach ($kategori as $nama => $deskripsi) {
            $categoryIds[$nama] = Category::query()->updateOrCreate(
                ['name' => $nama],
                ['description' => $deskripsi, 'is_active' => true]
            )->id;
        }

        $produk = [
            // Casual Wear
            [
                'barcode' => 'TB-CW0001', 'name' => 'Dress Casual Kotak-Kotak Hitam',
                'category' => 'Casual Wear', 'unit' => 'pcs',
                'cost_price' => 95000, 'sale_price' => 145000, 'stock' => 20, 'min_stock' => 5,
                'description' => 'Dress casual motif kotak-kotak, bahan katun nyaman untuk aktivitas sehari-hari.',
                'material' => 'Katun', 'color_variants' => ['Hitam', 'Navy'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'barcode' => 'TB-CW0002', 'name' => 'Kaos Oversized Motif Bunga',
                'category' => 'Casual Wear', 'unit' => 'pcs',
                'cost_price' => 65000, 'sale_price' => 115000, 'stock' => 15, 'min_stock' => 5,
                'description' => 'Kaos oversized dengan motif bunga, cocok untuk gaya casual remaja.',
                'material' => 'Katun', 'color_variants' => ['Putih', 'Hitam', 'Merah'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Elegant Dresses
            [
                'barcode' => 'TB-ED0001', 'name' => 'Gamis Elegan Motif Bunga Midi',
                'category' => 'Elegant Dresses', 'unit' => 'pcs',
                'cost_price' => 185000, 'sale_price' => 275000, 'stock' => 12, 'min_stock' => 4,
                'description' => 'Gamis elegan motif bunga panjang midi, cocok untuk acara spesial.',
                'material' => 'Chiffon', 'color_variants' => ['Merah Marun', 'Hijau Tua', 'Navy'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'barcode' => 'TB-ED0002', 'name' => 'Gamis Katun Polis Wanita',
                'category' => 'Elegant Dresses', 'unit' => 'pcs',
                'cost_price' => 150000, 'sale_price' => 225000, 'stock' => 18, 'min_stock' => 5,
                'description' => 'Gamis katun polos, simpel namun elegan untuk sehari-hari dan acara formal.',
                'material' => 'Katun', 'color_variants' => ['Abu-abu', 'Hitam', 'Navy', 'Cream'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Party Dresses
            [
                'barcode' => 'TB-PD0001', 'name' => 'Gamis Pesta Satin Merah',
                'category' => 'Party Dresses', 'unit' => 'pcs',
                'cost_price' => 220000, 'sale_price' => 350000, 'stock' => 8, 'min_stock' => 3,
                'description' => 'Gamis pesta bahan satin warna merah, elegan untuk acara malam dan pernikahan.',
                'material' => 'Satin', 'color_variants' => ['Merah', 'Emas', 'Biru Tua'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Workwear
            [
                'barcode' => 'TB-WW0001', 'name' => 'Blouse Kerja Lengan Panjang',
                'category' => 'Workwear', 'unit' => 'pcs',
                'cost_price' => 110000, 'sale_price' => 175000, 'stock' => 14, 'min_stock' => 4,
                'description' => 'Blouse kerja lengan panjang, bahan lembut dan profesional untuk kantor.',
                'material' => 'Katun Campur', 'color_variants' => ['Putih', 'Biru', 'Hitam'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Tops & Tees
            [
                'barcode' => 'TB-TT0001', 'name' => 'Kaos Polos Wanita Motif Logo',
                'category' => 'Tops & Tees', 'unit' => 'pcs',
                'cost_price' => 55000, 'sale_price' => 95000, 'stock' => 25, 'min_stock' => 8,
                'description' => 'Kaos polos wanita dengan motif logo kecil di dada, bahan nyaman.',
                'material' => 'Katun', 'color_variants' => ['Putih', 'Hitam', 'Abu-abu', 'Navy'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Pants
            [
                'barcode' => 'TB-PN0001', 'name' => 'Celana Jeans Wanita Slim Fit',
                'category' => 'Pants', 'unit' => 'pcs',
                'cost_price' => 140000, 'sale_price' => 215000, 'stock' => 16, 'min_stock' => 5,
                'description' => 'Celana jeans wanita model slim fit, bahan denim tebal dan nyaman.',
                'material' => 'Denim', 'color_variants' => ['Biru Tua', 'Hitam'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Skirts
            [
                'barcode' => 'TB-SK0001', 'name' => 'Rok Midi Pliskat Cokelat',
                'category' => 'Skirts', 'unit' => 'pcs',
                'cost_price' => 85000, 'sale_price' => 135000, 'stock' => 10, 'min_stock' => 3,
                'description' => 'Rok midi pliskat warna cokelat, cocok untuk gaya casual dan semi formal.',
                'material' => 'Katun', 'color_variants' => ['Cokelat', 'Hitam', 'Abu-abu'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Outerwear
            [
                'barcode' => 'TB-OW0001', 'name' => 'Jaket Denim Wanita Classic',
                'category' => 'Outerwear', 'unit' => 'pcs',
                'cost_price' => 175000, 'sale_price' => 265000, 'stock' => 11, 'min_stock' => 4,
                'description' => 'Jaket denim wanita model classic, tahan lama dan gaya kasual.',
                'material' => 'Denim', 'color_variants' => ['Biru Tua', 'Hitam'],
                'size_variants' => ['S', 'M', 'L', 'XL'],
            ],
            // Hijab
            [
                'barcode' => 'TB-HJ0001', 'name' => 'Keranji Hijab Segi Empat',
                'category' => 'Hijab', 'unit' => 'pcs',
                'cost_price' => 35000, 'sale_price' => 65000, 'stock' => 30, 'min_stock' => 10,
                'description' => 'Keranji hijab segi empat bahan jersey, lembut dan tidak kusut.',
                'material' => 'Jersey', 'color_variants' => ['Hitam', 'Navy', 'Merah', 'Nude'],
                'size_variants' => ['Free Size'],
            ],
        ];

        foreach ($produk as $data) {
            $product = Product::query()->updateOrCreate(
                ['barcode' => $data['barcode']],
                [
                    'name' => $data['name'],
                    'category_id' => $categoryIds[$data['category']],
                    'unit' => $data['unit'],
                    'cost_price' => $data['cost_price'],
                    'sale_price' => $data['sale_price'],
                    'stock' => $data['stock'],
                    'min_stock' => $data['min_stock'],
                    'is_active' => true,
                ]
            );

            // Buat varian ukuran & warna (stok per varian)
            foreach ($data['size_variants'] as $size) {
                foreach ($data['color_variants'] as $color) {
                    $variantStock = (int) ($data['stock'] / (count($data['size_variants']) * count($data['color_variants']))) ?: 1;
                    \App\Models\ProductVariant::query()->updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'size' => $size,
                            'color' => $color,
                        ],
                        ['stock' => $variantStock]
                    );
                }
            }

            // Sinkronkan total stok produk dari varian
            $product->update(['stock' => $product->variants()->sum('stock')]);
        }
    }
}
