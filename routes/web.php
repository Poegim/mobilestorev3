<?php

use Illuminate\Support\Facades\Route;

// Livewire components
use App\Livewire\Items\Index as ItemsIndex;
use App\Livewire\Sells\Index as SellsIndex;
use App\Livewire\Purchases\Index as PurchasesIndex;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('items', ItemsIndex::class)->name('items.index');
    Route::get('sells', SellsIndex::class)->name('sells.index');
    Route::get('purchases', PurchasesIndex::class)->name('purchases.index');

    /**
     *  Shop routes
     */
    Route::prefix('shop/{shop}')->name('shop.')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::get('items', ItemsIndex::class)->name('items.index');
        Route::get('sells', SellsIndex::class)->name('sells.index');
        Route::get('purchases', PurchasesIndex::class)->name('purchases.index');
    });
});

require __DIR__.'/settings.php';
