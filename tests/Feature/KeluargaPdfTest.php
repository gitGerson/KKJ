<?php

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from keluarga pdf', function () {
    $keluarga = Keluarga::factory()->create();

    $this->get(route('keluarga.pdf', $keluarga))->assertRedirect(route('login'));
});

test('authenticated users can download a kartu keluarga style pdf', function () {
    $user = User::factory()->create();
    $keluarga = Keluarga::factory()->create(['no_keluarga' => '501']);
    $area = Area::factory()->create(['name' => '9 BJR']);
    $kemah = Kemah::factory()->create(['name' => 'BZ']);

    Umat::factory()->create([
        'keluarga_id' => $keluarga->id,
        'area_id' => $area->id,
        'kemah_id' => $kemah->id,
        'nama_lengkap' => 'LIEM TJOEN PENG / PENGKY',
        'nama_panggilan' => 'TJOE PENG',
        'nomor_telepon' => '0815-7582-5284',
        'jenis_kelamin' => 'L',
        'status_perkawinan' => 'NIKAH',
        'hub_kk' => 'Kepala Keluarga',
        'golongan_darah' => 'A',
        'tempat_lahir' => 'TEGAL',
        'tanggal_lahir' => '1972-12-31',
        'alamat' => 'JL. RAYA SELATAN DS. TEMBOK LUWUNG NO. 48',
        'pendidikan' => 'SMA',
        'pekerjaan' => 'SERABUTAN',
        'domisili' => 'BANJARAN',
    ]);

    $response = $this->actingAs($user)->get(route('keluarga.pdf', $keluarga));

    $response
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertSee('%PDF-', false);

    expect($response->headers->get('Content-Disposition'))->toContain('kartu-keluarga-501.pdf');
});

test('keluarga index displays a pdf button per row', function () {
    $user = User::factory()->create();
    Keluarga::factory()->create(['no_keluarga' => '501']);

    $this->actingAs($user)
        ->get(route('keluarga.index'))
        ->assertSuccessful()
        ->assertSee('PDF');
});
