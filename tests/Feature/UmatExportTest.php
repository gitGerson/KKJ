<?php

use App\Exports\UmatExport;
use App\Models\Area;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('admin can download an umat export', function () {
    Excel::fake();

    $admin = User::factory()->create();
    Umat::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::umat.index')
        ->call('export')
        ->assertFileDownloaded();
});

test('pendeta cannot export umat', function () {
    $pendeta = User::factory()->pendeta()->create();

    $this->actingAs($pendeta);

    Livewire::test('pages::umat.index')
        ->call('export')
        ->assertForbidden();
});

test('umat export respects the provided query and maps derived fields', function () {
    $area = Area::factory()->create(['name' => 'Area Ekspor']);

    $included = Umat::factory()->create([
        'nama_lengkap' => 'Ikut Ekspor',
        'area_id' => $area->id,
        'jenis_kelamin' => 'L',
        'status_perkawinan' => 'Kawin',
        'hub_kk' => 'Kepala Keluarga',
        'tanggal_lahir' => now()->subYears(40)->toDateString(),
    ]);
    Umat::factory()->create(['nama_lengkap' => 'Tidak Ikut', 'area_id' => Area::factory()->create()->id]);

    $export = new UmatExport(Umat::query()->where('area_id', $area->id));

    $rows = $export->query()->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->is($included))->toBeTrue();

    $mapped = $export->map($included);

    // pemanggilan kolom ke-9 (index 8): pria + kawin + dewasa => Bapak.
    expect($mapped[2])->toBe('Ikut Ekspor')
        ->and($mapped[0])->toBe('Area Ekspor')
        ->and($mapped[8])->toBe('Bapak');
});
