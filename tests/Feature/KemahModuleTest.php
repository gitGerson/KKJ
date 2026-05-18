<?php

use App\Models\Kemah;
use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected from the kemah module', function () {
    $this->get(route('kemah.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the kemah module', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('kemah.index'))
        ->assertSuccessful()
        ->assertSee('Kemah');
});

test('authenticated users can create kemah', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::kemah.index')
        ->set('name', 'Kemah Barat')
        ->call('createKemah')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('kemah', [
        'name' => 'Kemah Barat',
    ]);
});

test('authenticated users can update kemah', function () {
    $user = User::factory()->create();
    $kemah = Kemah::factory()->create(['name' => 'Old Kemah']);

    $this->actingAs($user);

    Livewire::test('pages::kemah.index')
        ->call('editKemah', $kemah->id)
        ->set('name', 'New Kemah')
        ->call('updateKemah')
        ->assertHasNoErrors();

    expect($kemah->refresh()->name)->toBe('New Kemah');
});

test('authenticated users can delete kemah', function () {
    $user = User::factory()->create();
    $kemah = Kemah::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::kemah.index')
        ->call('deleteKemah', $kemah->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($kemah);
});

test('authenticated users can search kemah', function () {
    $user = User::factory()->create();

    Kemah::factory()->create(['name' => 'Kemah Barat']);
    Kemah::factory()->create(['name' => 'Kemah Timur']);

    $this->actingAs($user);

    Livewire::test('pages::kemah.index')
        ->set('search', 'Barat')
        ->assertSee('Kemah Barat')
        ->assertDontSee('Kemah Timur');
});

test('authenticated users can view umat for a selected kemah', function () {
    $user = User::factory()->create();
    $selectedKemah = Kemah::factory()->create(['name' => 'Kemah Barat']);
    $otherKemah = Kemah::factory()->create(['name' => 'Kemah Timur']);

    Umat::factory()->create([
        'kemah_id' => $selectedKemah->id,
        'nama_lengkap' => 'Maria Kemah',
    ]);

    Umat::factory()->create([
        'kemah_id' => $otherKemah->id,
        'nama_lengkap' => 'Yusuf Kemah',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::kemah.index')
        ->call('selectKemah', $selectedKemah->id)
        ->assertSee('Maria Kemah')
        ->assertDontSee('Yusuf Kemah');
});
