<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CeramicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_name' => $this->material_name,
            'type' => $this->type,
            'brand' => $this->brand,
            'sub_brand' => $this->sub_brand,
            'code' => $this->code,
            'color' => $this->color,
            'form' => $this->form,
            'full_name' => $this->full_name, // Menggunakan Accessor dari Model

            // Dimensi
            'dimensions' => [
                'length' => $this->dimension_length,
                'width' => $this->dimension_width,
                'thickness' => $this->dimension_thickness,
            ],

            // Kemasan
            'packaging' => [
                'type' => $this->packaging,
                'pieces' => $this->pieces_per_package,
                'coverage' => $this->coverage_per_package,
            ],

            // Toko
            'store' => [
                'name' => $this->store,
                'address' => $this->address,
            ],

            // Harga
            'price' => [
                'per_package' => $this->price_per_package,
                'per_m2' => $this->comparison_price_per_m2,
            ],

            // Foto (Generate Full URL)
            'photo_url' => $this->photo ? Storage::url($this->photo) : null,

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
