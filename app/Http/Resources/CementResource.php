<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cement_name' => $this->cement_name,
            'type' => $this->type,
            'brand' => $this->brand,
            'sub_brand' => $this->sub_brand,
            'code' => $this->code,
            'color' => $this->color,

            // Package
            'package_unit' => $this->package_unit,
            'package_weight_gross' => $this->package_weight_gross,
            'package_weight_net' => $this->package_weight_net,

            // Dimensions
            'dimension_length' => $this->dimension_length,
            'dimension_width' => $this->dimension_width,
            'dimension_height' => $this->dimension_height,
            'package_volume' => $this->package_volume,

            // Location
            'store' => $this->store,
            'address' => $this->address,
            'short_address' => $this->short_address,

            // Pricing
            'package_price' => $this->package_price,
            'price_unit' => $this->price_unit,
            'comparison_price_per_kg' => $this->comparison_price_per_kg,

            // Photo
            'photo' => $this->photo,
            'photo_url' => $this->photo_url,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
