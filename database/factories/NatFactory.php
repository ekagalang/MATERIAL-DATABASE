<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Nat>
 */
class NatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $packageWeightNet = $this->faker->randomElement([1, 2, 3, 5, 10]);
        $packagePrice = $this->faker->numberBetween(15000, 60000);
        $comparisonPricePerKg = $packageWeightNet > 0 ? $packagePrice / $packageWeightNet : 0;
        $packageVolume = $packageWeightNet / 1440; // density of grout powder is approximately 1440 kg/m3

        return [
            'nat_name' => 'Nat Keramik',
            'type' => $this->faker->randomElement(['Regular', 'Epoxy', 'Sanded', 'Non-Sanded']),
            'brand' => $this->faker->randomElement(['Mowilex', 'Sika', 'Avian', 'Dulux', 'Nippon']),
            'sub_brand' => $this->faker->optional()->word(),
            'code' => strtoupper($this->faker->bothify('NAT-###??')),
            'color' => $this->faker->randomElement(['Putih', 'Abu-abu', 'Hitam', 'Cream', 'Cokelat']),
            'package_unit' => 'Kg',
            'package_weight_gross' => $packageWeightNet + 0.1,
            'package_weight_net' => $packageWeightNet,
            'package_volume' => $packageVolume,
            'store' => $this->faker->company(),
            'address' => $this->faker->address(),
            'package_price' => $packagePrice,
            'price_unit' => 'Rp',
            'comparison_price_per_kg' => $comparisonPricePerKg,
        ];
    }
}
