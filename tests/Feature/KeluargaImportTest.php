<?php

use App\Models\Keluarga;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('authenticated users can import keluarga and umat from the KKJ Excel layout', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->createWithContent('KKJ.xlsx', file_get_contents(public_path('KKJ.xlsx')));

    $this->actingAs($user);

    Livewire::test('pages::keluarga.index')
        ->call('openImportModal')
        ->assertSet('showImportModal', true)
        ->set('importFile', $file)
        ->call('importKeluarga')
        ->assertSet('showImportModal', false)
        ->assertHasNoErrors();

    $keluarga = Keluarga::query()->where('no_keluarga', '501')->firstOrFail();

    expect($keluarga->umat()->count())->toBe(4)
        ->and(Umat::query()->where('nama_lengkap', 'LIEM TJOEN PENG / PENGKY')->first())
        ->not->toBeNull();

    $this->assertDatabaseHas('umat', [
        'keluarga_id' => $keluarga->id,
        'nama_lengkap' => 'LIEM TJOEN PENG / PENGKY',
        'nama_panggilan' => 'TJOE PENG',
        'nomor_telepon' => '0815-7582-5284',
        'jenis_kelamin' => 'L',
        'status_perkawinan' => 'NIKAH',
        'hub_kk' => 'Kepala Keluarga',
        'golongan_darah' => 'A',
        'tempat_lahir' => 'TEGAL',
        'alamat' => 'JL. RAYA SELATAN DS. TEMBOK LUWUNG NO. 48',
        'pekerjaan' => 'SERABUTAN',
        'domisili' => 'BANJARAN',
    ]);

    expect(Umat::query()->where('nama_lengkap', 'LIEM TJOEN PENG / PENGKY')->firstOrFail()->tanggal_lahir->toDateString())
        ->toBe('1972-12-31');

    $this->assertDatabaseHas('area', ['name' => '9 BJR']);
    $this->assertDatabaseHas('kemah', ['name' => 'BZ']);
});
