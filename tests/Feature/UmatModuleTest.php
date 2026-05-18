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
