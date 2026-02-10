<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cement>
 */
class CementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $packageWeightNet = $this->faker->randomElement([40, 50]);
        $packagePrice = $this->faker->numberBetween(50000, 80000);
        $comparisonPricePerKg = $packageWeightNet > 0 ? $packagePrice / $packageWeightNet : 0;

        return [
            'cement_name' => 'Semen Portland',
            'type' => $this->faker->randomElement(['I', 'II', 'III', 'V']),
            'brand' => $this->faker->randomElement(['Tiga Roda', 'Holcim', 'Semen Indonesia', 'Conch']),
            'sub_brand' => $this->faker->optional()->word(),
            'code' => strtoupper($this->faker->bothify('CEM-###??')),
            'color' => 'Abu-abu',
            'package_unit' => 'Sak',
            'package_weight_gross' => $packageWeightNet + 0.5,
            'package_weight_net' => $packageWeightNet,
            'package_volume' => $packageWeightNet / 1440, // density of cement is 1440 kg/m3
            'store' => $this->faker->company(),
            'address' => $this->faker->address(),
            'package_price' => $packagePrice,
            'price_unit' => 'Rp',
            'comparison_price_per_kg' => $comparisonPricePerKg,
        ];
    }
}
