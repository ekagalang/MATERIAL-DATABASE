<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class CementUpsertRequest extends FormRequest
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
            'sub_brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'package_volume' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ];
    }
}

