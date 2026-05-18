<?php

namespace Database\Seeders;

use App\Models\Keluarga;
use Illuminate\Database\Seeder;

class KeluargaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Keluarga::factory()->count(10)->create();
    }
}
