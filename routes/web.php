<?php

use App\Http\Controllers\KeluargaPdfController;
use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;

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
            'growth' => [
                'masuk_bulan' => Umat::query()
                    ->whereNotNull('tanggal_masuk')
                    ->whereYear('tanggal_masuk', now()->year)
                    ->whereMonth('tanggal_masuk', now()->month)
                    ->count(),
                'masuk_tahun' => Umat::query()
                    ->whereNotNull('tanggal_masuk')
                    ->whereYear('tanggal_masuk', now()->year)
                    ->count(),
                'keluar_bulan' => Umat::query()
                    ->whereIn('status', Umat::STATUS_ARSIP)
                    ->whereNotNull('tanggal_keluar')
                    ->whereYear('tanggal_keluar', now()->year)
                    ->whereMonth('tanggal_keluar', now()->month)
                    ->count(),
                'keluar_tahun' => Umat::query()
                    ->whereIn('status', Umat::STATUS_ARSIP)
                    ->whereNotNull('tanggal_keluar')
                    ->whereYear('tanggal_keluar', now()->year)
                    ->count(),
            ],
            'demografi' => Umat::demografiUsia(),
            'recentActivities' => Activity::query()
                ->with('causer')
                ->latest()
                ->take(8)
                ->get(),
        ]);
    })->name('dashboard');
    Route::livewire('areas', 'pages::areas.index')->name('areas.index');
    Route::livewire('kemah', 'pages::kemah.index')->name('kemah.index');
    Route::livewire('keluarga', 'pages::keluarga.index')->name('keluarga.index');
    Route::get('keluarga/{keluarga}/pdf', KeluargaPdfController::class)->name('keluarga.pdf');
    Route::livewire('umat', 'pages::umat.index')->name('umat.index');
    Route::livewire('users', 'pages::users.index')->name('users.index')->middleware('can:manage-data');
});

require __DIR__.'/settings.php';
