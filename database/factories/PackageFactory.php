<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Essential', 'Basic', 'Premium', 'Pro', 'Ultimate']),
            'price' => 0,
            'billing_period' => $this->faker->randomElement(['Monthly', 'Annual', 'Quarterly', 'Biannual', 'Lifelong']),
            'cv' => 0,
        ];
    }
}
