<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BrickFactory extends Factory
{
    public function definition(): array
    {
        $length = $this->faker->numberBetween(20, 30);
        $width = $this->faker->numberBetween(10, 15);
        $height = $this->faker->numberBetween(5, 10);

        // Kalkulasi volume
        $volumeCm3 = $length * $width * $height;
        $volumeM3 = $volumeCm3 / 1000000;

        $pricePerPiece = $this->faker->numberBetween(500, 2000);
        $comparisonPrice = $volumeM3 > 0 ? $pricePerPiece / $volumeM3 : 0;

        return [
            'material_name' => 'Bata',
            'type' => $this->faker->randomElement(['Merah', 'Press', 'Ringan', 'Hebel']),
            'brand' => $this->faker->randomElement(['Merah Jaya', 'Tiga Roda', 'Citicon', 'Blesscon']),
            'form' => $this->faker->randomElement(['Persegi', 'Berlubang', 'Solid']),
            'dimension_length' => $length,
            'dimension_width' => $width,
            'dimension_height' => $height,
            'package_volume' => $volumeM3,
            'package_type' => 'eceran',
            'store' => $this->faker->company(),
            'address' => $this->faker->address(),
            'price_per_piece' => $pricePerPiece,
            'comparison_price_per_m3' => $comparisonPrice,
        ];
    }
}
