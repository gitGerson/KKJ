<?php

namespace Database\Seeders;

use App\Models\Kemah;
use Illuminate\Database\Seeder;

class KemahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kemah::factory()->count(5)->create();
    }
}
