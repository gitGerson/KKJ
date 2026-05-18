<?php

namespace Database\Factories;

use App\Models\Kemah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kemah>
 */
class KemahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
