<?php

namespace App\Actions\Material;

use Illuminate\Database\Eloquent\Model;

class DeleteMaterialAction
{
    public function execute(Model $material): ?bool
    {
        return $material->delete();
    }
}
