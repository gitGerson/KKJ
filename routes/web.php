<?php

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::post('preferences/locale', function (Request $request) {
    $validated = $request->validate([
        'locale' => ['required', 'in:id,en'],
    ]);

    $request->session()->put('locale', $validated['locale']);

    return back();
})->name('preferences.locale');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return view('dashboard', [
            'totals' => [
                'umat' => Umat::query()->count(),
                'keluarga' => Keluarga::query()->count(),
                'area' => Area::query()->count(),
                'kemah' => Kemah::query()->count(),
            ],
            'latestUmat' => Umat::query()
                ->with(['area', 'kemah', 'keluarga'])
                ->latest()
                ->take(8)
                ->get(),
            'topAreas' => Area::query()
                ->withCount('umat')
                ->orderByDesc('umat_count')
                ->orderBy('name')
                ->take(5)
                ->get(),
            'topKemah' => Kemah::query()
                ->withCount('umat')
                ->orderByDesc('umat_count')
                ->orderBy('name')
                ->take(5)
                ->get(),
            'largestKeluarga' => Keluarga::query()
                ->withCount('umat')
                ->orderByDesc('umat_count')
                ->orderBy('no_keluarga')
                ->take(5)
                ->get(),
            'unassigned' => [
                'area' => Umat::query()->whereNull('area_id')->count(),
                'kemah' => Umat::query()->whereNull('kemah_id')->count(),
                'keluarga' => Umat::query()->whereNull('keluarga_id')->count(),
            ],
        ]);
    })->name('dashboard');
    Route::livewire('areas', 'pages::areas.index')->name('areas.index');
    Route::livewire('kemah', 'pages::kemah.index')->name('kemah.index');
    Route::livewire('keluarga', 'pages::keluarga.index')->name('keluarga.index');
    Route::livewire('umat', 'pages::umat.index')->name('umat.index');
});

require __DIR__.'/settings.php';
