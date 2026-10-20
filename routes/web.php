<?php

use App\Livewire\Dashboard;
use App\Livewire\Items\Index as ItemsIndex;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Sells\Index as SellsIndex;
use App\Livewire\Sells\Show as SellsShow;
use App\Livewire\Transfers\Index as TransfersIndex;
use App\Livewire\Transfers\Create as TransfersCreate;
use App\Livewire\Transfers\Show as TransfersShow;
use App\Livewire\Admin\Users\Create as AdminUsersCreate;
use App\Livewire\Admin\Users\Index as AdminUsersIndex;
use App\Livewire\Admin\Users\Show as AdminUsersShow;
use App\Livewire\Shops\Index as ShopsIndex;
use Illuminate\Support\Facades\Route;
use App\Livewire\Catalog\Products\Index as CatalogProductsIndex;
use App\Livewire\Catalog\Products\Create as CatalogProductsCreate;
use App\Livewire\Catalog\Products\Edit as CatalogProductsEdit;
use App\Livewire\Catalog\Devices\Index as CatalogDevicesIndex;
use App\Livewire\Catalog\Brands\Index as CatalogBrandsIndex;
use App\Livewire\Contacts\Index as ContactsIndex;
use App\Livewire\Purchases\Create as PurchasesCreate;
use App\Livewire\Purchases\Show as PurchasesShow;



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('items', ItemsIndex::class)->name('items.index');
    
    Route::get('sells', SellsIndex::class)->name('sells.index');
    Route::get('sells/{sell}', SellsShow::class)->name('sells.show');
    
    Route::get('purchases', PurchasesIndex::class)->name('purchases.index');
    Route::get('purchases/{purchase}', PurchasesShow::class)->name('purchases.show');
    
    Route::get('shops', ShopsIndex::class)->name('shops.index');

    Route::get('transfers', TransfersIndex::class)->name('transfers.index');
    Route::get('transfers/{transfer}', TransfersShow::class)->name('transfers.show');

    Route::get('catalog/products', CatalogProductsIndex::class)->name('catalog.products.index');
    Route::get('catalog/products/create', CatalogProductsCreate::class)->name('catalog.products.create');
    Route::get('catalog/products/{product}/edit', CatalogProductsEdit::class)->name('catalog.products.edit');
    
    Route::get('catalog/devices', CatalogDevicesIndex::class)->name('catalog.devices.index');
    
    Route::get('catalog/brands', CatalogBrandsIndex::class)->name('catalog.brands.index');
    
    Route::get('contacts', ContactsIndex::class)->name('contacts.index');

    Route::prefix('shop/{shop}')->name('shop.')->scopeBindings()->group(function () {
        Route::get('dashboard', Dashboard::class)->name('dashboard');

        Route::get('items', ItemsIndex::class)->name('items.index');

        Route::get('sells', SellsIndex::class)->name('sells.index');
        Route::get('sells/{sell}', SellsShow::class)->name('sells.show');

        Route::get('purchases/create', PurchasesCreate::class)->name('purchases.create');
        Route::get('purchases', PurchasesIndex::class)->name('purchases.index');
        Route::get('purchases/{purchase}', PurchasesShow::class)->name('purchases.show');

        Route::get('transfers', TransfersIndex::class)->name('transfers.index');
        Route::get('transfers/create', TransfersCreate::class)->name('transfers.create');
        Route::get('transfers/{transfer}', TransfersShow::class)->name('transfers.show');
    });
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('users', AdminUsersIndex::class)->name('users.index');
        Route::get('users/create', AdminUsersCreate::class)->name('users.create');
        Route::get('users/{user}', AdminUsersShow::class)->name('users.show');
    });



require __DIR__.'/settings.php';