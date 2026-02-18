<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class ApiBrickUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'package_type' => 'nullable|in:eceran,kubik',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'price_per_piece' => 'nullable|numeric|min:0',
            'comparison_price_per_m3' => 'nullable|numeric|min:0',
        ];
    }
}
