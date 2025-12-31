<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cat_name' => $this->cat_name,
            'type' => $this->type,
            'brand' => $this->brand,
            'sub_brand' => $this->sub_brand,
            'color_code' => $this->color_code,
            'color_name' => $this->color_name,
            'form' => $this->form,
            'package_unit' => $this->package_unit,
            'package_weight_gross' => $this->package_weight_gross,
            'package_weight_net' => $this->package_weight_net,
            'volume' => $this->volume,
            'volume_unit' => $this->volume_unit,
            'store' => $this->store,
            'address' => $this->address,
            'short_address' => $this->short_address,
            'purchase_price' => $this->purchase_price,
            'price_unit' => $this->price_unit,
            'comparison_price_per_kg' => $this->comparison_price_per_kg,
            'photo' => $this->photo,
            'photo_url' => $this->photo_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
