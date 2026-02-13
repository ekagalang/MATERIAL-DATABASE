<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class ApiCeramicStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => 'required|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'pieces_per_package' => 'required|integer|min:1',
            'price_per_package' => 'required|numeric|min:0',
        ];
    }
}

