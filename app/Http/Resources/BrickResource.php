<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Brick Resource
 *
 * Transform Brick model untuk API response
 */
class BrickResource extends JsonResource
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
            'form' => $this->form,

            // Dimensions
            'dimension_length' => $this->dimension_length,
            'dimension_width' => $this->dimension_width,
            'dimension_height' => $this->dimension_height,
            'package_volume' => $this->package_volume,

            // Location
            'store' => $this->store,
            'address' => $this->address,

            // Pricing
            'price_per_piece' => $this->price_per_piece,
            'comparison_price_per_m3' => $this->comparison_price_per_m3,

            // Photo
            'photo' => $this->photo,
            'photo_url' => $this->photo_url, // Accessor dari model

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
