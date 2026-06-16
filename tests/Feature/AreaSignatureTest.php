<?php

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can store a gembala name and signature image for an area', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test('pages::areas.index')
        ->set('name', 'Area Gembala')
        ->set('gembala', 'Pdt. Budi')
        ->set('ttd', UploadedFile::fake()->image('ttd.png'))
        ->call('createArea')
        ->assertHasNoErrors();

    $area = Area::query()->where('name', 'Area Gembala')->sole();

    expect($area->gembala)->toBe('Pdt. Budi')
        ->and($area->ttd_path)->not->toBeNull();

    Storage::disk('public')->assertExists($area->ttd_path);
});

test('pendeta cannot create an area', function () {
    $pendeta = User::factory()->pendeta()->create();
    $this->actingAs($pendeta);

    Livewire::test('pages::areas.index')
        ->set('name', 'Tidak Boleh')
        ->call('createArea')
        ->assertForbidden();

    $this->assertDatabaseMissing('area', ['name' => 'Tidak Boleh']);
});

test('family card pdf renders with the area gembala signature', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $ttdPath = UploadedFile::fake()->image('ttd.png')->store('signatures', 'public');
    $area = Area::factory()->create(['name' => 'Area TTD', 'gembala' => 'Pdt. Sasmita', 'ttd_path' => $ttdPath]);
    $keluarga = Keluarga::factory()->create(['no_keluarga' => '777']);

    Umat::factory()->create([
        'keluarga_id' => $keluarga->id,
        'area_id' => $area->id,
        'hub_kk' => 'Kepala Keluarga',
        'nama_lengkap' => 'Kepala TTD',
    ]);

    $this->actingAs($user)
        ->get(route('keluarga.pdf', $keluarga))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertSee('%PDF-', false);
});
