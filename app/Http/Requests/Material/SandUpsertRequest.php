<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class SandUpsertRequest extends FormRequest
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
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'package_price' => 'nullable|numeric|min:0',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ];
    }
}

