<?php

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected from the umat module', function () {
    $this->get(route('umat.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the umat module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('umat.index'))
        ->assertSuccessful()
        ->assertSee('Umat');
});

test('authenticated users can create umat', function () {
    $user = User::factory()->create();
    $area = Area::factory()->create();
    $kemah = Kemah::factory()->create();
    $keluarga = Keluarga::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('openCreateModal')
        ->set('form.nama_lengkap', 'Maria Jakarta')
        ->set('form.nama_panggilan', 'Maria')
        ->set('form.nomor_telepon', '08123456789')
        ->set('form.jenis_kelamin', 'P')
        ->set('form.area_id', $area->id)
        ->set('form.kemah_id', $kemah->id)
        ->set('form.keluarga_id', $keluarga->id)
        ->call('saveUmat')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('umat', [
        'nama_lengkap' => 'Maria Jakarta',
        'nama_panggilan' => 'Maria',
        'nomor_telepon' => '08123456789',
        'jenis_kelamin' => 'P',
        'area_id' => $area->id,
        'kemah_id' => $kemah->id,
        'keluarga_id' => $keluarga->id,
    ]);
});

test('authenticated users can update umat', function () {
    $user = User::factory()->create();
    $umat = Umat::factory()->create(['nama_lengkap' => 'Old Name']);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('openEditModal', $umat->id)
        ->set('form.nama_lengkap', 'New Name')
        ->set('form.domisili', 'Jakarta')
        ->call('saveUmat')
        ->assertHasNoErrors();

    expect($umat->refresh())
        ->nama_lengkap->toBe('New Name')
        ->domisili->toBe('Jakarta');
});

test('authenticated users can delete umat', function () {
    $user = User::factory()->create();
    $umat = Umat::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('deleteUmat', $umat->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($umat);
});

test('authenticated users can search umat', function () {
    $user = User::factory()->create();

    Umat::factory()->create(['nama_lengkap' => 'Maria Jakarta']);
    Umat::factory()->create(['nama_lengkap' => 'Yusuf Bandung']);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->set('search', 'Maria')
        ->assertSee('Maria Jakarta')
        ->assertDontSee('Yusuf Bandung');
});

test('new umat defaults to calon with todays tanggal masuk', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('openCreateModal')
        ->assertSet('form.status', Umat::STATUS_CALON)
        ->assertSet('form.tanggal_masuk', now()->toDateString())
        ->set('form.nama_lengkap', 'Calon Baru')
        ->call('saveUmat')
        ->assertHasNoErrors();

    $umat = Umat::query()->where('nama_lengkap', 'Calon Baru')->sole();

    expect($umat->status)->toBe(Umat::STATUS_CALON)
        ->and($umat->tanggal_masuk->toDateString())->toBe(now()->toDateString());
});

test('status must be one of the allowed values', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('openCreateModal')
        ->set('form.nama_lengkap', 'Invalid Status')
        ->set('form.status', 'tidak-valid')
        ->call('saveUmat')
        ->assertHasErrors(['form.status']);
});

test('tanggal keluar cannot precede tanggal masuk', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('openCreateModal')
        ->set('form.nama_lengkap', 'Bad Dates')
        ->set('form.tanggal_masuk', '2026-01-10')
        ->set('form.tanggal_keluar', '2026-01-01')
        ->call('saveUmat')
        ->assertHasErrors(['form.tanggal_keluar']);
});

test('main list hides archived umat by default and shows them when toggled', function () {
    $user = User::factory()->create();

    Umat::factory()->create(['nama_lengkap' => 'Jemaat Aktif', 'status' => Umat::STATUS_AKTIF]);
    Umat::factory()->create(['nama_lengkap' => 'Jemaat Keluar', 'status' => Umat::STATUS_KELUAR]);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->assertSee('Jemaat Aktif')
        ->assertDontSee('Jemaat Keluar')
        ->set('showArchived', true)
        ->assertSee('Jemaat Keluar')
        ->assertDontSee('Jemaat Aktif');
});

test('calon are visible in the main list', function () {
    $user = User::factory()->create();

    Umat::factory()->create(['nama_lengkap' => 'Calon Pantau', 'status' => Umat::STATUS_CALON]);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->assertSee('Calon Pantau');
});

test('only calon matang filter lists prospects monitored at least six months', function () {
    $user = User::factory()->create();

    Umat::factory()->calonMatang()->create(['nama_lengkap' => 'Calon Matang']);
    Umat::factory()->create([
        'nama_lengkap' => 'Calon Baru',
        'status' => Umat::STATUS_CALON,
        'tanggal_masuk' => now()->subMonth()->toDateString(),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->set('onlyCalonMatang', true)
        ->assertSee('Calon Matang')
        ->assertDontSee('Calon Baru');
});

test('authenticated users can promote a prospect to active', function () {
    $user = User::factory()->create();
    $umat = Umat::factory()->calonMatang()->create();

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('promoteToAktif', $umat->id)
        ->assertHasNoErrors();

    expect($umat->refresh()->status)->toBe(Umat::STATUS_AKTIF);
});

test('umur and kelompok usia are derived from tanggal lahir', function () {
    expect(Umat::factory()->make(['tanggal_lahir' => now()->subYears(10)->toDateString()])->kelompok_usia)->toBe(Umat::KELOMPOK_ANAK)
        ->and(Umat::factory()->make(['tanggal_lahir' => now()->subYears(15)->toDateString()])->kelompok_usia)->toBe(Umat::KELOMPOK_REMAJA)
        ->and(Umat::factory()->make(['tanggal_lahir' => now()->subYears(25)->toDateString()])->kelompok_usia)->toBe(Umat::KELOMPOK_PEMUDA)
        ->and(Umat::factory()->make(['tanggal_lahir' => now()->subYears(50)->toDateString()])->kelompok_usia)->toBe(Umat::KELOMPOK_DEWASA)
        ->and(Umat::factory()->make(['tanggal_lahir' => now()->subYears(25)->toDateString()])->umur)->toBe(25)
        ->and(Umat::factory()->make(['tanggal_lahir' => null])->kelompok_usia)->toBeNull();
});

test('pemanggilan follows age, gender, and marital status', function () {
    expect(Umat::factory()->make(['jenis_kelamin' => 'L', 'status_perkawinan' => 'Kawin', 'tanggal_lahir' => now()->subYears(13)->toDateString()])->pemanggilan)->toBe('Anak')
        ->and(Umat::factory()->make(['jenis_kelamin' => 'L', 'status_perkawinan' => 'Belum Kawin', 'tanggal_lahir' => now()->subYears(20)->toDateString()])->pemanggilan)->toBe('Sdr')
        ->and(Umat::factory()->make(['jenis_kelamin' => 'L', 'status_perkawinan' => '', 'tanggal_lahir' => now()->subYears(21)->toDateString()])->pemanggilan)->toBe('Sdr')
        ->and(Umat::factory()->make(['jenis_kelamin' => 'P', 'status_perkawinan' => 'Belum Kawin', 'tanggal_lahir' => now()->subYears(16)->toDateString()])->pemanggilan)->toBe('Sdri')
        ->and(Umat::factory()->make(['jenis_kelamin' => 'L', 'status_perkawinan' => 'Kawin', 'tanggal_lahir' => now()->subYears(25)->toDateString()])->pemanggilan)->toBe('Bapak')
        ->and(Umat::factory()->make(['jenis_kelamin' => 'P', 'status_perkawinan' => 'Kawin', 'tanggal_lahir' => now()->subYears(35)->toDateString()])->pemanggilan)->toBe('Ibu')
        ->and(Umat::factory()->make(['jenis_kelamin' => 'L', 'status_perkawinan' => 'Kawin', 'tanggal_lahir' => now()->subYears(40)->toDateString()])->pemanggilan)->toBe('Bapak')
        ->and(Umat::factory()->make(['jenis_kelamin' => 'P', 'status_perkawinan' => 'Kawin', 'tanggal_lahir' => now()->subYears(40)->toDateString()])->pemanggilan)->toBe('Ibu')
        ->and(Umat::factory()->make(['hub_kk' => 'Anak', 'jenis_kelamin' => 'L', 'status_perkawinan' => 'Belum Kawin', 'tanggal_lahir' => now()->subYears(40)->toDateString()])->pemanggilan)->toBe('Sdr');
});

test('users can filter umat by age group', function () {
    $user = User::factory()->create();

    Umat::factory()->create(['nama_lengkap' => 'Bocah Kecil', 'tanggal_lahir' => now()->subYears(10)->toDateString()]);
    Umat::factory()->create(['nama_lengkap' => 'Orang Dewasa', 'tanggal_lahir' => now()->subYears(45)->toDateString()]);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->set('filterKelompokUsia', Umat::KELOMPOK_ANAK)
        ->assertSee('Bocah Kecil')
        ->assertDontSee('Orang Dewasa');
});

test('users can filter umat by birthday month', function () {
    $user = User::factory()->create();

    Umat::factory()->create(['nama_lengkap' => 'Lahir Maret', 'tanggal_lahir' => '1990-03-15']);
    Umat::factory()->create(['nama_lengkap' => 'Lahir Agustus', 'tanggal_lahir' => '1990-08-15']);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->set('filterBulanUlangTahun', 3)
        ->assertSee('Lahir Maret')
        ->assertDontSee('Lahir Agustus');
});

test('birthdays this month shortcut sets current month filter', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->call('filterUlangTahunBulanIni')
        ->assertSet('filterBulanUlangTahun', (int) now()->month);
});

test('users can filter umat by area', function () {
    $user = User::factory()->create();
    $area = Area::factory()->create(['name' => 'Area Filter']);

    Umat::factory()->create(['nama_lengkap' => 'Warga Area', 'area_id' => $area->id]);
    Umat::factory()->create(['nama_lengkap' => 'Warga Lain', 'area_id' => Area::factory()->create()->id]);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->set('filterAreaId', $area->id)
        ->assertSee('Warga Area')
        ->assertDontSee('Warga Lain');
});

test('authenticated users can see umat relationship data', function () {
    $user = User::factory()->create();
    $area = Area::factory()->create(['name' => 'Jakarta Barat']);
    $kemah = Kemah::factory()->create(['name' => 'Kemah Barat']);
    $keluarga = Keluarga::factory()->create(['no_keluarga' => 'KK-00001']);

    Umat::factory()->create([
        'nama_lengkap' => 'Maria Jakarta',
        'area_id' => $area->id,
        'kemah_id' => $kemah->id,
        'keluarga_id' => $keluarga->id,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::umat.index')
        ->assertSee('Maria Jakarta')
        ->assertSee('Jakarta Barat')
        ->assertSee('Kemah Barat')
        ->assertSee('KK-00001');
});
