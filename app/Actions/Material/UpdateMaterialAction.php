<?php

namespace App\Actions\Material;

use Illuminate\Database\Eloquent\Model;

class UpdateMaterialAction
{
    public function execute(Model $material, array $attributes): bool
    {
        return $material->update($attributes);
    }
}
