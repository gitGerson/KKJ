<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use Illuminate\Database\Seeder;

class UmatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Umat::factory()
            ->count(30)
            ->recycle(Area::all())
            ->recycle(Kemah::all())
            ->recycle(Keluarga::all())
            ->create();
    }
}
