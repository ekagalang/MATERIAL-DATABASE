<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sand>
 */
class SandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dimensionLength = $this->faker->numberBetween(100, 200); // cm
        $dimensionWidth = $this->faker->numberBetween(100, 200); // cm
        $dimensionHeight = $this->faker->numberBetween(100, 200); // cm
        $volumeM3 = ($dimensionLength * $dimensionWidth * $dimensionHeight) / 1000000;
        $packagePrice = $this->faker->numberBetween(300000, 500000);
        $comparisonPricePerM3 = $volumeM3 > 0 ? $packagePrice / $volumeM3 : 0;

        return [
            'sand_name' => 'Pasir',
            'type' => $this->faker->randomElement(['Pasang', 'Beton', 'Cor', 'Urug']),
            'brand' => $this->faker->randomElement(['Pasir Lumajang', 'Pasir Bangka', 'Pasir Mojokerto']),
            'package_unit' => 'Truk',
            'package_weight_gross' => $this->faker->numberBetween(8000, 10000),
            'package_weight_net' => $this->faker->numberBetween(7500, 9500),
            'dimension_length' => $dimensionLength,
            'dimension_width' => $dimensionWidth,
            'dimension_height' => $dimensionHeight,
            'package_volume' => $volumeM3,
            'store' => $this->faker->company(),
            'address' => $this->faker->address(),
            'package_price' => $packagePrice,
            'comparison_price_per_m3' => $comparisonPricePerM3,
        ];
    }
}
