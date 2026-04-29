<?php

use App\Livewire\DashboardView;
use App\Livewire\Events\EventCreate;
use App\Livewire\EventTypes\EventTypeIndex;
use App\Livewire\Plans\PlanIndex;
use App\Livewire\Plans\PlanView;
use App\Livewire\RolesPermissions\PermissionView;
use App\Livewire\Shifts\ShiftIndex;
use App\Http\Controllers\Auth\AuthCallbackController;
use App\Livewire\Units\UnitCreate;
use App\Livewire\Units\UnitIndex;
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
