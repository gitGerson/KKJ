<?php

use App\Models\Keluarga;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected from the keluarga module', function () {
    $this->get(route('keluarga.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the keluarga module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('keluarga.index'))
        ->assertSuccessful()
        ->assertSee('Keluarga');
});

test('authenticated users can create keluarga with existing umat', function () {
    $user = User::factory()->create();
    $umat = Umat::factory()->create(['keluarga_id' => null]);

    $this->actingAs($user);

    Livewire::test('pages::keluarga.index')
        ->call('openCreateModal')
        ->set('form.no_keluarga', 'KK-00001')
        ->set('memberRows.0.mode', 'existing')
        ->set('memberRows.0.umat_id', $umat->id)
        ->call('saveKeluarga')
        ->assertHasNoErrors();

    $keluarga = Keluarga::query()->where('no_keluarga', 'KK-00001')->firstOrFail();

    expect($umat->refresh()->keluarga_id)->toBe($keluarga->id);
});

test('authenticated users can create keluarga with new umat rows', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::keluarga.index')
        ->call('openCreateModal')
        ->set('form.no_keluarga', 'KK-00002')
        ->set('memberRows.0.mode', 'create')
        ->set('memberRows.0.nama_lengkap', 'Maria Jakarta')
        ->set('memberRows.0.nama_panggilan', 'Maria')
        ->set('memberRows.0.nomor_telepon', '08123456789')
        ->set('memberRows.0.hub_kk', 'Anak')
        ->call('saveKeluarga')
        ->assertHasNoErrors();

    $keluarga = Keluarga::query()->where('no_keluarga', 'KK-00002')->firstOrFail();

    $this->assertDatabaseHas('umat', [
        'keluarga_id' => $keluarga->id,
        'nama_lengkap' => 'Maria Jakarta',
        'nama_panggilan' => 'Maria',
        'nomor_telepon' => '08123456789',
        'hub_kk' => 'Anak',
    ]);
});

test('authenticated users can update keluarga membership', function () {
    $user = User::factory()->create();
    $keluarga = Keluarga::factory()->create(['no_keluarga' => 'KK-OLD']);
    $removedUmat = Umat::factory()->create(['keluarga_id' => $keluarga->id]);
    $addedUmat = Umat::factory()->create(['keluarga_id' => null]);

    $this->actingAs($user);

    Livewire::test('pages::keluarga.index')
        ->call('openEditModal', $keluarga->id)
        ->set('form.no_keluarga', 'KK-NEW')
        ->set('memberRows.0.mode', 'existing')
        ->set('memberRows.0.umat_id', $addedUmat->id)
        ->call('saveKeluarga')
        ->assertHasNoErrors();

    expect($keluarga->refresh()->no_keluarga)->toBe('KK-NEW')
        ->and($addedUmat->refresh()->keluarga_id)->toBe($keluarga->id)
        ->and($removedUmat->refresh()->keluarga_id)->toBeNull();
});

test('authenticated users can delete keluarga without deleting umat', function () {
    $user = User::factory()->create();
    $keluarga = Keluarga::factory()->create();
    $umat = Umat::factory()->create(['keluarga_id' => $keluarga->id]);

    $this->actingAs($user);

    Livewire::test('pages::keluarga.index')
        ->call('deleteKeluarga', $keluarga->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($keluarga);
    $this->assertModelExists($umat->refresh());
    expect($umat->keluarga_id)->toBeNull();
});

test('authenticated users can search keluarga by number or umat name', function () {
    $user = User::factory()->create();
    $matchedKeluarga = Keluarga::factory()->create(['no_keluarga' => 'KK-MARIA']);
    $otherKeluarga = Keluarga::factory()->create(['no_keluarga' => 'KK-YUSUF']);

    Umat::factory()->create([
        'keluarga_id' => $matchedKeluarga->id,
        'nama_lengkap' => 'Maria Jakarta',
    ]);

    Umat::factory()->create([
        'keluarga_id' => $otherKeluarga->id,
        'nama_lengkap' => 'Yusuf Bandung',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::keluarga.index')
        ->set('search', 'Maria')
        ->assertSee('KK-MARIA')
        ->assertDontSee('KK-YUSUF');
});
