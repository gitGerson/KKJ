<?php

namespace Tests\Feature;

use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_exposes_growth_and_demographics_data(): void
    {
        $user = User::factory()->create();

        // Masuk bulan ini.
        Umat::factory()->create([
            'status' => Umat::STATUS_AKTIF,
            'tanggal_masuk' => now()->startOfMonth()->toDateString(),
            'tanggal_lahir' => now()->subYears(10)->toDateString(),
        ]);
        // Keluar bulan ini.
        Umat::factory()->create([
            'status' => Umat::STATUS_KELUAR,
            'tanggal_keluar' => now()->startOfMonth()->toDateString(),
            'tanggal_lahir' => now()->subYears(40)->toDateString(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('growth', fn (array $growth): bool => $growth['masuk_bulan'] === 1 && $growth['keluar_bulan'] === 1)
            ->assertViewHas('demografi', fn (array $demografi): bool => $demografi[Umat::KELOMPOK_ANAK] === 1 && $demografi[Umat::KELOMPOK_DEWASA] === 0);
    }
}
