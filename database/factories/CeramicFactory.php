<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ceramic>
 */
class CeramicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dimensionLength = $this->faker->randomElement([20, 25, 30, 40, 50, 60, 80]);
        $dimensionWidth = $this->faker->randomElement([20, 25, 30, 40, 50, 60]);
        $dimensionThickness = $this->faker->randomFloat(2, 0.6, 1.2);
        $piecesPerPackage = $this->faker->randomElement([6, 8, 10, 12, 15, 20]);
        $areaPerPieceM2 = ($dimensionLength * $dimensionWidth) / 10000;
        $coveragePerPackage = $areaPerPieceM2 * $piecesPerPackage;
        $pricePerPackage = $this->faker->numberBetween(80000, 300000);
        $comparisonPricePerM2 = $coveragePerPackage > 0 ? $pricePerPackage / $coveragePerPackage : 0;

        return [
            'material_name' => 'Keramik',
            'type' => $this->faker->randomElement(['Lantai', 'Dinding', 'Granit', 'Homogeneous']),
            'brand' => $this->faker->randomElement(['Roman', 'Asia Tile', 'Platinum', 'Mulia', 'Arwana']),
            'sub_brand' => $this->faker->optional()->word(),
            'code' => strtoupper($this->faker->bothify('CER-###??')),
            'color' => $this->faker->randomElement(['Putih', 'Cream', 'Abu-abu', 'Cokelat', 'Hitam']),
            'form' => $this->faker->randomElement(['Persegi', 'Persegi Panjang']),
            'surface' => $this->faker->randomElement(['Glossy', 'Matte', 'Textured']),
            'dimension_length' => $dimensionLength,
            'dimension_width' => $dimensionWidth,
            'dimension_thickness' => $dimensionThickness,
            'packaging' => 'Dus',
            'pieces_per_package' => $piecesPerPackage,
            'coverage_per_package' => $coveragePerPackage,
            'store' => $this->faker->company(),
            'address' => $this->faker->address(),
            'price_per_package' => $pricePerPackage,
            'comparison_price_per_m2' => $comparisonPricePerM2,
        ];
    }
}
