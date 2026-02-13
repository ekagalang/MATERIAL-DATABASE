<?php

namespace App\Http\Requests\Material;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'place_id' => 'nullable|string|max:255',
            'formatted_address' => 'nullable|string',
            'service_radius_km' => 'nullable|numeric|min:0',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
        ];
    }
}
