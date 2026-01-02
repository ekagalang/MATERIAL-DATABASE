<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sand_name' => $this->sand_name,
            'type' => $this->type,
            'brand' => $this->brand,
            'package_unit' => $this->package_unit,
            'package_weight_gross' => $this->package_weight_gross,
            'package_weight_net' => $this->package_weight_net,
            'dimension_length' => $this->dimension_length,
            'dimension_width' => $this->dimension_width,
            'dimension_height' => $this->dimension_height,
            'package_volume' => $this->package_volume,
            'store' => $this->store,
            'address' => $this->address,
            'short_address' => $this->short_address,
            'package_price' => $this->package_price,
            'comparison_price_per_m3' => $this->comparison_price_per_m3,
            'photo' => $this->photo,
            'photo_url' => $this->photo_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
