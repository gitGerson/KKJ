<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('areas', 'pages::areas.index')->name('areas.index');
    Route::livewire('kemah', 'pages::kemah.index')->name('kemah.index');
    Route::livewire('umat', 'pages::umat.index')->name('umat.index');
});

require __DIR__.'/settings.php';
