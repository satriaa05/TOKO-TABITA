<?php

/*
| Konfigurasi tabel ukuran (cm) berdasarkan kategori produk.
|
| Key digunakan untuk mencocokkan nama kategori (case-insensitive contains).
| Jika tidak ada yang cocok, digunakan set "default".
|
| Setiap baris: header kolom + baris data (string, termasuk satuan).
| Ubah sesuai data asli dari client; file ini boleh diedit tanpa deploy ulang
| (php artisan config:clear di lingkungan produksi).
*/

return [
    'sets' => [
        'dress' => [
            'headers' => ['Ukuran', 'Lebar Dada', 'Panjang Baju', 'Lebar Bahu', 'Panjang Lengan'],
            'rows' => [
                ['S', '44 cm', '58 cm', '36 cm', '55 cm'],
                ['M', '46 cm', '60 cm', '38 cm', '56 cm'],
                ['L', '48 cm', '62 cm', '40 cm', '57 cm'],
                ['XL', '51 cm', '64 cm', '42 cm', '58 cm'],
            ],
        ],
        'top' => [
            'headers' => ['Ukuran', 'Lebar Dada', 'Panjang Baju', 'Lebar Bahu', 'Panjang Lengan'],
            'rows' => [
                ['S', '44 cm', '58 cm', '36 cm', '55 cm'],
                ['M', '46 cm', '60 cm', '38 cm', '56 cm'],
                ['L', '48 cm', '62 cm', '40 cm', '57 cm'],
                ['XL', '51 cm', '64 cm', '42 cm', '58 cm'],
            ],
        ],
        'bottom' => [
            'headers' => ['Ukuran', 'Lingkar Pinggang', 'Lingkar Pinggul', 'Panjang Celana/Rok'],
            'rows' => [
                ['S', '66 cm', '92 cm', '94 cm'],
                ['M', '70 cm', '96 cm', '96 cm'],
                ['L', '74 cm', '100 cm', '98 cm'],
                ['XL', '80 cm', '106 cm', '100 cm'],
            ],
        ],
        'hijab' => [
            'headers' => ['Ukuran', 'Panjang', 'Lebar'],
            'rows' => [
                ['All Size', '175 cm', '75 cm'],
            ],
        ],
        'default' => [
            'headers' => ['Ukuran', 'Lebar Dada', 'Panjang Baju', 'Lebar Bahu', 'Panjang Lengan'],
            'rows' => [
                ['S', '44 cm', '58 cm', '36 cm', '55 cm'],
                ['M', '46 cm', '60 cm', '38 cm', '56 cm'],
                ['L', '48 cm', '62 cm', '40 cm', '57 cm'],
                ['XL', '51 cm', '64 cm', '42 cm', '58 cm'],
            ],
        ],
    ],

    'match' => [
        'dress' => ['dress', 'gaun'],
        'top' => ['blouse', 'kemeja', 'kaos', 'atasan', 'shirt', 'top'],
        'bottom' => ['celana', 'pants', 'rok', 'skirt', 'kulot'],
        'hijab' => ['hijab', 'khimar', 'kerudung'],
    ],
];
