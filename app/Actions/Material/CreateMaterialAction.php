<?php

namespace App\Actions\Material;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateMaterialAction
{
    /**
     * @var array<string, class-string<Model>>
     */
    private const MODEL_MAP = [
        'brick' => Brick::class,
        'cement' => Cement::class,
        'sand' => Sand::class,
        'cat' => Cat::class,
        'nat' => Nat::class,
        'ceramic' => Ceramic::class,
    ];

    public function execute(string $materialType, array $attributes): Model
    {
        $modelClass = self::MODEL_MAP[$materialType] ?? null;
        if (!$modelClass) {
            throw new InvalidArgumentException("Unsupported material type: {$materialType}");
        }

        return $modelClass::create($attributes);
    }
}
