<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'barcode' => [
                'required', 'string', 'max:50',
                // Barcode harus unik (tabel produk) - dicek juga di level DB.
                Rule::unique('products', 'barcode')->ignore($productId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => [
                'required',
                Rule::exists(Category::class, 'id')->where('is_active', true),
            ],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'barcode.unique' => 'Barcode ini sudah dipakai produk lain.',
            'category_id.exists' => 'Kategori tidak ditemukan atau sedang nonaktif.',
            'sale_price.min' => 'Harga jual tidak boleh negatif.',
            'cost_price.min' => 'Harga pokok tidak boleh negatif.',
            'stock.min' => 'Stok tidak boleh negatif.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'barcode' => 'barcode',
            'name' => 'nama produk',
            'category_id' => 'kategori',
            'unit' => 'satuan',
            'cost_price' => 'harga pokok',
            'sale_price' => 'harga jual',
            'stock' => 'stok',
            'min_stock' => 'stok minimum',
        ];
    }

    /**
     * Validasi tambahan: harga jual tidak boleh lebih murah dari harga pokok.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $cost = (float) $this->input('cost_price');
            $price = (float) $this->input('sale_price');

            if ($price > 0 && $price < $cost) {
                $validator->errors()->add(
                    'sale_price',
                    'Harga jual tidak boleh lebih kecil dari harga pokok (Rp '.number_format($cost, 0, ',', '.').').'
                );
            }
        });
    }
}
