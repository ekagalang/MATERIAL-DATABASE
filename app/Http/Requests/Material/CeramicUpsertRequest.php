<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class CeramicUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'type' => 'nullable|string',
            'code' => 'nullable|string',
            'color' => 'nullable|string',
            'form' => 'nullable|string',
            'surface' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric',
            'dimension_width' => 'nullable|numeric',
            'dimension_thickness' => 'nullable|numeric',
            'pieces_per_package' => 'nullable|integer',
            'coverage_per_package' => 'nullable|numeric',
            'price_per_package' => 'nullable|numeric',
            'comparison_price_per_m2' => 'nullable|numeric',
            'packaging' => 'nullable|string|max:255',
            'store' => 'nullable|string',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ];
    }
}
