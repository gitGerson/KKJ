<?php

use App\Models\Area;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected from the area module', function () {
    $this->get(route('areas.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the area module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('areas.index'))
        ->assertSuccessful()
        ->assertSee('Areas');
});

test('authenticated users can create areas', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::areas.index')
        ->set('name', 'Jakarta Barat')
        ->call('createArea')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('area', [
        'name' => 'Jakarta Barat',
    ]);
});

test('authenticated users can update areas', function () {
    $user = User::factory()->create();
    $area = Area::factory()->create(['name' => 'Old Area']);

    $this->actingAs($user);

    Livewire::test('pages::areas.index')
        ->call('editArea', $area->id)
        ->set('name', 'New Area')
        ->call('updateArea')
        ->assertHasNoErrors();

    expect($area->refresh()->name)->toBe('New Area');
});

test('authenticated users can delete areas', function () {
    $user = User::factory()->create();
    $area = Area::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::areas.index')
        ->call('deleteArea', $area->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($area);
});

test('authenticated users can search areas', function () {
    $user = User::factory()->create();

    Area::factory()->create(['name' => 'Jakarta Barat']);
    Area::factory()->create(['name' => 'Bandung']);

    $this->actingAs($user);

    Livewire::test('pages::areas.index')
        ->set('search', 'Jakarta')
        ->assertSee('Jakarta Barat')
        ->assertDontSee('Bandung');
});

test('authenticated users can view umat for a selected area', function () {
    $user = User::factory()->create();
    $selectedArea = Area::factory()->create(['name' => 'Jakarta Barat']);
    $otherArea = Area::factory()->create(['name' => 'Bandung']);

    Umat::factory()->create([
        'area_id' => $selectedArea->id,
        'nama_lengkap' => 'Maria Jakarta',
    ]);

    Umat::factory()->create([
        'area_id' => $otherArea->id,
        'nama_lengkap' => 'Yusuf Bandung',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::areas.index')
        ->call('selectArea', $selectedArea->id)
        ->assertSee('Maria Jakarta')
        ->assertDontSee('Yusuf Bandung');
});
