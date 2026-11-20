<?php

namespace App\Http\Requests\Admin;

use App\Enums\PromotionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true || $this->user()?->isOwner() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_column(PromotionType::cases(), 'value'))],
            'value' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'min_purchase' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'products' => ['required', 'array', 'min:1'],
            'products.*' => ['integer', 'exists:products,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama promo wajib diisi.',
            'type.required' => 'Jenis diskon wajib dipilih.',
            'value.required' => 'Nilai diskon wajib diisi.',
            'value.min' => 'Nilai diskon tidak boleh negatif.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal berakhir wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'min_purchase.required' => 'Minimum belanja wajib diisi.',
            'products.required' => 'Minimal satu produk harus dipilih.',
            'products.min' => 'Minimal satu produk harus dipilih.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama promo',
            'type' => 'jenis diskon',
            'value' => 'nilai diskon',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal berakhir',
            'min_purchase' => 'minimum belanja',
            'products' => 'produk',
        ];
    }

    /**
     * Validasi tambahan: diskon persen tidak boleh melebihi 100%.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $maxPercent = (float) config('pos.max_discount_percent', 100);

            if ($this->input('type') === PromotionType::Percent->value && (float) $this->input('value') > $maxPercent) {
                $validator->errors()->add(
                    'value',
                    'Diskon persentase tidak boleh melebihi '.$maxPercent.'%.'
                );
            }
        });
    }
}
