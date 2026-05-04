<?php

use App\Livewire\DashboardView;
use App\Livewire\Events\EventCreate;
use App\Livewire\EventTypes\EventTypeIndex;
use App\Livewire\Plans\PlanIndex;
use App\Livewire\Plans\PlanView;
use App\Livewire\RawMaterialRequests\RawMaterialRequestCreate;
use App\Livewire\RawMaterialRequests\RawMaterialRequestIndex;
use App\Livewire\RawMaterials\RawMaterialCreate;
use App\Livewire\RawMaterials\RawMaterialIndex;
use App\Livewire\RawMaterialStockIn\RawMaterialStockInCreate;
use App\Livewire\RawMaterialStockIn\RawMaterialStockInIndex;
use App\Livewire\RolesPermissions\PermissionView;
use App\Livewire\Shifts\ShiftIndex;
use App\Http\Controllers\Auth\AuthCallbackController;
use App\Livewire\RawMaterialStockOut\RawMaterialStockOutCreate;
use App\Livewire\RawMaterialStockOut\RawMaterialStockOutIndex;
use App\Livewire\RawMaterialTransfer\RawMaterialTransferCreate;
use App\Livewire\RawMaterialTransfer\RawMaterialTransferIndex;
use App\Livewire\Units\UnitCreate;
use App\Livewire\Units\UnitIndex;
use App\Livewire\RawMaterialWarehouseInventory\RawMaterialWarehouseInventoryIndex;
use App\Livewire\RawMaterialWaste\RawMaterialWasteCreate;
use App\Livewire\RawMaterialWaste\RawMaterialWasteIndex;
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
    // | Units
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'units'], function () {
        Route::get('/', UnitIndex::class)->name('units');
        Route::get('/create', UnitCreate::class)->name('units.create');
        Route::get('/edit/{id}', UnitCreate::class)->name('units.edit');
    });

    // |--------------------------------------------------------------------------
    // | Raw Materials
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'raw-materials'], function () {
        Route::get('/', RawMaterialIndex::class)->name('raw-materials');
        Route::get('/create', RawMaterialCreate::class)->name('raw-materials.create');
        Route::get('/edit/{id}', RawMaterialCreate::class)->name('raw-materials.edit');
    });


    // |--------------------------------------------------------------------------
    // | Raw Material Requests
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'raw-material-requests'], function () {
        Route::get('/', RawMaterialRequestIndex::class)->name('raw-material-requests');
        Route::get('/create', RawMaterialRequestCreate::class)->name('raw-material-requests.create');
        Route::get('/edit/{id}', RawMaterialRequestCreate::class)->name('raw-material-requests.edit');
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
    // | Units
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'units'], function () {
        Route::get('/', UnitIndex::class)->name('units');
        Route::get('/create', UnitCreate::class)->name('units.create');
        Route::get('/edit/{id}', UnitCreate::class)->name('units.edit');
    });


    // |--------------------------------------------------------------------------
    // | Stock Ins
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'raw-material-stock-ins'], function () {
        Route::get('/', RawMaterialStockInIndex::class)->name('raw-material-stock-ins');
        Route::get('/create', RawMaterialStockInCreate::class)->name('raw-material-stock-ins.create');
        Route::get('/edit/{id}', RawMaterialStockInCreate::class)->name('raw-material-stock-ins.edit');
        Route::get('/view/{id}/{viewStatus}', RawMaterialStockInCreate::class)->name('raw-material-stock-ins.view');

    });

    // |--------------------------------------------------------------------------
    // | Stock Outs
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'raw-material-stock-outs'], function () {
        Route::get('/', RawMaterialStockOutIndex::class)->name('raw-material-stock-outs');
        Route::get('/create', RawMaterialStockOutCreate::class)->name('raw-material-stock-outs.create');
        Route::get('/edit/{id}', RawMaterialStockOutCreate::class)->name('raw-material-stock-outs.edit');
        Route::get('/view/{id}/{viewStatus}', RawMaterialStockOutCreate::class)->name('raw-material-stock-outs.view');

    });

    // |--------------------------------------------------------------------------
    // | Waste
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'raw-material-wastes'], function () {
        Route::get('/', RawMaterialWasteIndex::class)->name('raw-material-wastes');
        Route::get('/create', RawMaterialWasteCreate::class)->name('raw-material-wastes.create');
        Route::get('/edit/{id}', RawMaterialWasteCreate::class)->name('raw-material-wastes.edit');
        Route::get('/view/{id}/{viewStatus}', RawMaterialWasteCreate::class)->name('raw-material-wastes.view');

    });

    // |--------------------------------------------------------------------------
    // | Warehouse Inventory
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'raw-material-warehouse-inventory'], function () {
        Route::get('/', RawMaterialWarehouseInventoryIndex::class)->name('raw-material-warehouse-inventory');
    });

    // |---------------------------------------------------------------------------
    // | Transfers
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'raw-material-transfers'], function () {
        Route::get('/', RawMaterialTransferIndex::class)->name('raw-material-transfers');
        Route::get('/create', RawMaterialTransferCreate::class)->name('raw-material-transfers.create');
        Route::get('/edit/{id}', RawMaterialTransferCreate::class)->name('raw-material-transfers.edit');
        Route::get('/view/{id}/{viewStatus}', RawMaterialTransferCreate::class)->name('raw-material-transfers.view');
        Route::get('/approve-load/{id}', RawMaterialTransferCreate::class)->name('raw-material-transfers.approve-load');
        Route::get('/approve-receive/{id}', RawMaterialTransferCreate::class)->name('raw-material-transfers.approve-receive');
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
