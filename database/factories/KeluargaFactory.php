<?php

namespace Database\Factories;

use App\Models\Keluarga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keluarga>
 */
class KeluargaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_keluarga' => fake()->unique()->numerify('KK-#####'),
        ];
    }
}
