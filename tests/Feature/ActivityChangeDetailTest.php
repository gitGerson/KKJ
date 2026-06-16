<?php

use App\Models\Area;
use App\Models\Umat;
use App\Models\User;
use App\Support\UmatActivityPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('presenter formats updated changes as old to new with readable values', function () {
    $areaLama = Area::factory()->create(['name' => 'Area Lama']);
    $areaBaru = Area::factory()->create(['name' => 'Area Baru']);

    $umat = Umat::factory()->create([
        'alamat' => 'Alamat Lama',
        'area_id' => $areaLama->id,
        'status' => Umat::STATUS_AKTIF,
    ]);

    Activity::query()->delete();

    $umat->update([
        'alamat' => 'Alamat Baru',
        'area_id' => $areaBaru->id,
        'status' => Umat::STATUS_KELUAR,
    ]);

    $activity = Activity::query()->latest()->firstOrFail();
    $changes = collect(UmatActivityPresenter::changes($activity))->keyBy('label');

    expect($changes['Alamat'])->toMatchArray(['from' => 'Alamat Lama', 'to' => 'Alamat Baru'])
        ->and($changes['Area'])->toMatchArray(['from' => 'Area Lama', 'to' => 'Area Baru'])
        ->and($changes['Status keanggotaan'])->toMatchArray(['from' => 'Aktif', 'to' => 'Keluar']);
});

test('presenter returns no change lines for created events', function () {
    $umat = Umat::factory()->create();

    $activity = Activity::query()->where('event', 'created')->latest()->firstOrFail();

    expect(UmatActivityPresenter::changes($activity))->toBe([]);
});

test('dashboard shows detailed change values', function () {
    $user = User::factory()->create();

    $umat = Umat::factory()->create(['alamat' => 'Jalan Awal', 'status' => Umat::STATUS_AKTIF]);
    $umat->update(['alamat' => 'Jalan Pindah']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Jalan Awal')
        ->assertSee('Jalan Pindah');
});
