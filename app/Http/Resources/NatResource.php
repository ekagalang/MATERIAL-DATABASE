<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nat_name' => $this->nat_name,

            // Keep compatibility with existing Cement-like UI
            'type' => $this->type ?? $this->nat_name,

            'brand' => $this->brand,
            'sub_brand' => $this->sub_brand,
            'code' => $this->code,
            'color' => $this->color,

            // Package
            'package_unit' => $this->package_unit,
            'package_weight_gross' => $this->package_weight_gross,
            'package_weight_net' => $this->package_weight_net,
            'package_volume' => $this->package_volume,

            // Keep compatibility with existing Cement-like UI
            'package_weight' => $this->package_weight_net,

            // Location
            'store' => $this->store,
            'address' => $this->address,
            'store_location_id' => $this->store_location_id,

            // Pricing
            'package_price' => $this->package_price,
            'price_unit' => $this->price_unit,
            'comparison_price_per_kg' => $this->comparison_price_per_kg,

            // Keep compatibility with existing Cement-like UI
            'price_per_bag' => $this->package_price,

            // Photo
            'photo' => $this->photo,
            'photo_url' => $this->photo_url,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
