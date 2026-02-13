<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class CatStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cat_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:100',
            'color_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'volume_unit' => 'nullable|string|max:20',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ];
    }
}

