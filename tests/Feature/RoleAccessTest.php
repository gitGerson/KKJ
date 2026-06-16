<?php

use App\Models\Umat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('public registration is disabled', function () {
    expect(Route::has('register'))->toBeFalse();

    $this->get('/register')->assertNotFound();
});

test('pendeta can view the umat list read-only', function () {
    $pendeta = User::factory()->pendeta()->create();

    Umat::factory()->create(['nama_lengkap' => 'Jemaat Tampil']);

    $this->actingAs($pendeta)
        ->get(route('umat.index'))
        ->assertSuccessful()
        ->assertSee('Jemaat Tampil')
        ->assertDontSee('Create umat');
});

test('pendeta cannot create or delete umat', function () {
    $pendeta = User::factory()->pendeta()->create();
    $umat = Umat::factory()->create();

    $this->actingAs($pendeta);

    Livewire::test('pages::umat.index')
        ->call('openCreateModal')
        ->set('form.nama_lengkap', 'Tidak Boleh')
        ->call('saveUmat')
        ->assertForbidden();

    Livewire::test('pages::umat.index')
        ->call('deleteUmat', $umat->id)
        ->assertForbidden();

    $this->assertDatabaseMissing('umat', ['nama_lengkap' => 'Tidak Boleh']);
    $this->assertModelExists($umat);
});

test('admin can create and delete umat', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::umat.index')
        ->call('openCreateModal')
        ->set('form.nama_lengkap', 'Boleh Dibuat')
        ->call('saveUmat')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('umat', ['nama_lengkap' => 'Boleh Dibuat']);
});

test('pendeta cannot access the users page', function () {
    $pendeta = User::factory()->pendeta()->create();

    $this->actingAs($pendeta)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('admin can access the users page and create a user', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertSuccessful();

    Livewire::test('pages::users.index')
        ->call('openCreateModal')
        ->set('form.name', 'Operator Baru')
        ->set('form.email', 'operator@example.com')
        ->set('form.password', 'rahasia123')
        ->set('form.role', User::ROLE_PENDETA)
        ->call('saveUser')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'operator@example.com',
        'role' => User::ROLE_PENDETA,
    ]);
});

test('admin cannot delete their own account', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::users.index')
        ->call('deleteUser', $admin->id);

    $this->assertModelExists($admin);
});
