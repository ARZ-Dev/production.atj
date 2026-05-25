<?php

use App\Livewire\DashboardView;
use App\Livewire\Events\EventCreate;
use App\Livewire\EventTypes\EventTypeIndex;
use App\Livewire\ItemRequests\ItemRequestCreate;
use App\Livewire\ItemRequests\ItemRequestIndex;
use App\Livewire\Plans\PlanIndex;
use App\Livewire\Plans\PlanView;
use App\Livewire\StockIn\StockInCreate;
use App\Livewire\StockIn\StockInIndex;
use App\Livewire\Recipes\RecipeCreate;
use App\Livewire\Recipes\RecipeIndex;
use App\Livewire\RolesPermissions\PermissionView;
use App\Livewire\Shifts\ShiftIndex;
use App\Http\Controllers\Auth\AuthCallbackController;
use App\Livewire\StockOut\StockOutCreate;
use App\Livewire\StockOut\StockOutIndex;
use App\Livewire\Transfer\TransferCreate;
use App\Livewire\Transfer\TransferIndex;
use App\Livewire\WarehouseInventory\WarehouseInventoryIndex;
use App\Livewire\Waste\WasteCreate;
use App\Livewire\Waste\WasteIndex;
use Illuminate\Support\Facades\Route;


// ─── Public routes (no auth) ──────────────────────────
Route::get('/auth/token-login', [AuthCallbackController::class, 'tokenLogin'])
    ->name('auth.token-login');

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth.service'])->prefix('admin')->group(function () {

    Route::post('/auth/logout', [AuthCallbackController::class, 'logout'])
        ->name('auth.logout');


    Route::get('/dashboard', DashboardView::class)->name('dashboard');
    Route::get('/permissions', PermissionView::class)->name('permissions');

    Route::resource('/roles', \App\Http\Controllers\RoleController::class)->except(['show', 'update', 'destroy']);
    Route::post('/roles/{id}', [\App\Http\Controllers\RoleController::class, 'update'])->name('roles.update');
    Route::get('/roles/delete/{id}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy');

    Route::resource('/users', \App\Http\Controllers\UserController::class)->except(['show', 'update', 'destroy']);
    Route::post('/users/{id}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::get('/users/delete/{id}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/departments/{id}/warehouses', [\App\Http\Controllers\UserController::class, 'getWarehouses'])->name('users.warehouses');
    Route::get('/users/warehouses/{id}/item-types', [\App\Http\Controllers\UserController::class, 'getItemTypes'])->name('users.item-types');
    Route::get('/users/departments/{id}/users', [\App\Http\Controllers\UserController::class, 'getDepartmentUsers'])->name('users.department-users');


    // |--------------------------------------------------------------------------
    // | Warehouses Types
    // |--------------------------------------------------------------------------

    Route::resource('/warehouse-types', \App\Http\Controllers\WarehouseTypeController::class)->except(['show', 'update', 'destroy']);
    Route::post('/warehouse-types/{id}', [\App\Http\Controllers\WarehouseTypeController::class, 'update'])->name('warehouse-types.update');
    Route::get('/warehouse-types/delete/{id}', [\App\Http\Controllers\WarehouseTypeController::class, 'destroy'])->name('warehouse-types.destroy');


    // |--------------------------------------------------------------------------
    // | Warehouses
    // --------------------------------------------------------------------------

    Route::resource('/warehouses', \App\Http\Controllers\WarehouseController::class)->except(['show', 'update', 'destroy']);
    Route::post('/warehouses/{id}', [\App\Http\Controllers\WarehouseController::class, 'update'])->name('warehouses.update');
    Route::get('/warehouses/delete/{id}', [\App\Http\Controllers\WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    // |--------------------------------------------------------------------------
    // | Suppliers
    // |--------------------------------------------------------------------------

    Route::resource('/suppliers', \App\Http\Controllers\SupplierController::class)->except(['show', 'update', 'destroy']);
    Route::post('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');
    Route::get('/suppliers/delete/{id}', [\App\Http\Controllers\SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // |--------------------------------------------------------------------------
    // | Item Types
    // |--------------------------------------------------------------------------

    Route::resource('/item-types', \App\Http\Controllers\ItemTypeController::class)->except(['show', 'update', 'destroy']);
    Route::post('/item-types/{id}', [\App\Http\Controllers\ItemTypeController::class, 'update'])->name('item-types.update');
    Route::get('/item-types/delete/{id}', [\App\Http\Controllers\ItemTypeController::class, 'destroy'])->name('item-types.destroy');

    // |--------------------------------------------------------------------------
    // | Items
    // |--------------------------------------------------------------------------

    Route::resource('/items', \App\Http\Controllers\ItemController::class)->except(['show', 'update', 'destroy']);
    Route::post('/items/{id}', [\App\Http\Controllers\ItemController::class, 'update'])->name('items.update');
    Route::get('/items/delete/{id}', [\App\Http\Controllers\ItemController::class, 'destroy'])->name('items.destroy');
    Route::get('/get-item-sub-types/{typeId}', [\App\Http\Controllers\ItemController::class, 'getSubTypes'])->name('items.get-sub-types');


    // |--------------------------------------------------------------------------
    // | Item Requests
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'item-requests'], function () {
        Route::get('/', ItemRequestIndex::class)->name('item-requests');
        Route::get('/create', ItemRequestCreate::class)->name('item-requests.create');
        Route::get('/edit/{id}', ItemRequestCreate::class)->name('item-requests.edit');
    });

    Route::resource('/suppliers', \App\Http\Controllers\SupplierController::class)->except(['show', 'update', 'destroy']);
    Route::post('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');
    Route::get('/suppliers/delete/{id}', [\App\Http\Controllers\SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // |--------------------------------------------------------------------------
    // | Locations
    // |--------------------------------------------------------------------------
    Route::get('/provinces/{countryId}', [\App\Http\Controllers\SupplierController::class, 'getProvinces'])->name('get-provinces');
    Route::get('/municipalities/{provinceId}', [\App\Http\Controllers\SupplierController::class, 'getMunicipalities'])->name('get-municipalities');
    Route::get('/neighborhoods/{municipalityId}', [\App\Http\Controllers\SupplierController::class, 'getNeighborhoods'])->name('get-neighborhoods');


    // |--------------------------------------------------------------------------
    // | Stock Ins
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'item-stock-ins'], function () {
        Route::get('/', StockInIndex::class)->name('item-stock-ins');
        Route::get('/create', StockInCreate::class)->name('item-stock-ins.create');
        Route::get('/edit/{id}', StockInCreate::class)->name('item-stock-ins.edit');
        Route::get('/view/{id}/{viewStatus}', StockInCreate::class)->name('item-stock-ins.view');

    });

    // |--------------------------------------------------------------------------
    // | Stock Outs
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'item-stock-outs'], function () {
        Route::get('/', StockOutIndex::class)->name('item-stock-outs');
        Route::get('/create', StockOutCreate::class)->name('item-stock-outs.create');
        Route::get('/edit/{id}', StockOutCreate::class)->name('item-stock-outs.edit');
        Route::get('/view/{id}/{viewStatus}', StockOutCreate::class)->name('item-stock-outs.view');

    });

    // |--------------------------------------------------------------------------
    // | Waste
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'item-wastes'], function () {
        Route::get('/', WasteIndex::class)->name('item-wastes');
        Route::get('/create', WasteCreate::class)->name('item-wastes.create');
        Route::get('/edit/{id}', WasteCreate::class)->name('item-wastes.edit');
        Route::get('/view/{id}/{viewStatus}', WasteCreate::class)->name('item-wastes.view');

    });

    // |--------------------------------------------------------------------------
    // | Warehouse Inventory
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'item-warehouse-inventory'], function () {
        Route::get('/', WarehouseInventoryIndex::class)->name('item-warehouse-inventory');
    });

    // |---------------------------------------------------------------------------
    // | Transfers
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'item-transfers'], function () {
        Route::get('/', TransferIndex::class)->name('item-transfers');
        Route::get('/create', TransferCreate::class)->name('item-transfers.create');
        Route::get('/edit/{id}', TransferCreate::class)->name('item-transfers.edit');
        Route::get('/view/{id}/{viewStatus}', TransferCreate::class)->name('item-transfers.view');
        Route::get('/approve-load/{id}', TransferCreate::class)->name('item-transfers.approve-load');
        Route::get('/approve-receive/{id}', TransferCreate::class)->name('item-transfers.approve-receive');
    });



    // |--------------------------------------------------------------------------
    // | Recipes
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'recipes'], function () {
        Route::get('/', RecipeIndex::class)->name('recipes');
        Route::get('/create', RecipeCreate::class)->name('recipes.create');
        Route::get('/edit/{id}', RecipeCreate::class)->name('recipes.edit');
        Route::get('/view/{id}', RecipeCreate::class)->name('recipes.view');

    });





    // |--------------------------------------------------------------------------
    // | Shift
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'shifts'], function () {
        Route::get('/', ShiftIndex::class)->name('shifts');

    });


    // |--------------------------------------------------------------------------
    // | Event Types
    // |--------------------------------------------------------------------------
    Route::group(['prefix' => 'event-types'], function () {
        Route::get('/', EventTypeIndex::class)->name('event-types');

    });

    // |--------------------------------------------------------------------------
    // | Plans
    // |--------------------------------------------------------------------------
    Route::group(['prefix' => 'plans'], function () {
        Route::get('/', PlanIndex::class)->name('plans');
        Route::get('/view/{id}/{status}', PlanView::class)->name('plans.view');

    });


    // |--------------------------------------------------------------------------
    // | Events
    // |--------------------------------------------------------------------------
    Route::group(['prefix' => 'events'], function () {
        // Route::get('/', EventIndex::class)->name('events');
        Route::get('{planId}/create', EventCreate::class)->name('events.create');
        // Route::get('/edit/{id}', EventCreate::class)->name('events.edit');
        // Route::get('/view/{id}', EventCreate::class)->name('events.view');
    });
});

// Route::get('{any}',[DashboardController::class, 'index'])->where('any', '.*'); // Catch-all route for the dashboard.
