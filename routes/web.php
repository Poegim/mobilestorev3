<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Items\Index as ItemsIndex;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('items', ItemsIndex::class)->name('items.index');

    Route::prefix('shop/{shop}')->name('shop.')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::get('items', ItemsIndex::class)->name('items.index');
    });
});

require __DIR__.'/settings.php';
