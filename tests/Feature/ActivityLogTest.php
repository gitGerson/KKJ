<?php

use App\Models\Area;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('creating umat records an activity', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test('pages::umat.index')
        ->call('openCreateModal')
        ->set('form.nama_lengkap', 'Jemaat Tercatat')
        ->call('saveUmat')
        ->assertHasNoErrors();

    $umat = Umat::query()->where('nama_lengkap', 'Jemaat Tercatat')->sole();

    $activity = Activity::query()->where('subject_type', $umat->getMorphClass())->where('subject_id', $umat->id)->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Jemaat baru ditambahkan')
        ->and($activity->causer_id)->toBe($admin->id);
});

test('changing address, area, or status is logged', function () {
    $umat = Umat::factory()->create([
        'alamat' => 'Alamat Lama',
        'status' => Umat::STATUS_AKTIF,
    ]);
    $area = Area::factory()->create();

    // Reset agar hanya menghitung log perubahan berikut.
    Activity::query()->delete();

    $umat->update([
        'alamat' => 'Alamat Baru',
        'area_id' => $area->id,
        'status' => Umat::STATUS_KELUAR,
    ]);

    $activity = Activity::query()->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Data jemaat diperbarui');

    $changes = $activity->attribute_changes->get('attributes');

    expect($changes)->toHaveKey('alamat', 'Alamat Baru')
        ->and($changes)->toHaveKey('area_id', $area->id)
        ->and($changes)->toHaveKey('status', Umat::STATUS_KELUAR);
});

test('unchanged save does not create an empty log', function () {
    $umat = Umat::factory()->create(['nama_lengkap' => 'Tetap Sama']);

    Activity::query()->delete();

    $umat->update(['nama_lengkap' => 'Tetap Sama']);

    expect(Activity::query()->count())->toBe(0);
});
