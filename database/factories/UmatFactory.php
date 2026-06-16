<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Umat>
 */
class UmatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lengkap' => fake()->name(),
            'nama_panggilan' => fake()->firstName(),
            'nomor_telepon' => fake()->phoneNumber(),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'status_perkawinan' => fake()->randomElement(['Belum Kawin', 'Kawin']),
            'hub_kk' => fake()->randomElement(['Kepala Keluarga', 'Istri', 'Anak']),
            'golongan_darah' => fake()->randomElement(['A', 'B', 'AB', 'O']),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->date(),
            'alamat' => fake()->address(),
            'kemah_id' => Kemah::factory(),
            'area_id' => Area::factory(),
            'pendidikan' => fake()->randomElement(['SD', 'SMP', 'SMA', 'S1']),
            'pekerjaan' => fake()->jobTitle(),
            'domisili' => fake()->city(),
            'keluarga_id' => Keluarga::factory(),
            'status' => Umat::STATUS_AKTIF,
            'tanggal_masuk' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'tanggal_keluar' => null,
            'keterangan' => null,
        ];
    }

    /**
     * Calon jemaat yang sudah dipantau lebih dari 6 bulan.
     */
    public function calonMatang(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Umat::STATUS_CALON,
            'tanggal_masuk' => now()->subMonths(7)->toDateString(),
        ]);
    }
}
