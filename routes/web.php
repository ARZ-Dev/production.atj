<?php

use App\Livewire\Auth\Login;
use App\Livewire\Companies\CompanyCreate;
use App\Livewire\Companies\CompanyIndex;
use App\Livewire\DashboardView;
use App\Livewire\Events\EventCreate;
use App\Livewire\Events\EventIndex;
use App\Livewire\EventTypes\EventTypeCreate;
use App\Livewire\EventTypes\EventTypeIndex;
use App\Livewire\Factories\FactoryCreate;
use App\Livewire\Factories\FactoryIndex;
use App\Livewire\MachineTypes\MachineTypeCreate;
use App\Livewire\MachineTypes\MachineTypeIndex;
use App\Livewire\Plans\PlanIndex;
use App\Livewire\Plans\PlanView;
use App\Livewire\ProductionLines\ProductionLineCreate;
use App\Livewire\ProductionLines\ProductionLineIndex;
use App\Livewire\RolesPermissions\PermissionView;
use App\Livewire\RolesPermissions\RoleView;
use App\Livewire\Shifts\ShiftCreate;
use App\Livewire\Shifts\ShiftIndex;
use App\Livewire\Users\UserCreate;
use App\Livewire\Users\UserIndex;
use App\Livewire\Warehouses\Warehouses\WarehouseCreate;
use App\Livewire\Warehouses\Warehouses\WarehouseIndex;
use App\Livewire\Warehouses\WarehouseTypes\WarehouseTypeCreate;
use App\Livewire\Warehouses\WarehouseTypes\WarehouseTypeIndex;
use App\Http\Controllers\Auth\AuthCallbackController;
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
    // | Machine Types
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'machine-types'], function () {
        Route::get('/', MachineTypeIndex::class)->name('machine-types');
        Route::get('/create', MachineTypeCreate::class)->name('machine-types.create');
        Route::get('/edit/{id}', MachineTypeCreate::class)->name('machine-types.edit');
        Route::get('/view/{id}', MachineTypeCreate::class)->name('machine-types.view');
    });
    
    Route::resource('/suppliers', \App\Http\Controllers\SupplierController::class)->except(['show', 'update', 'destroy']);
    Route::post('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');
    Route::get('/suppliers/delete/{id}', [\App\Http\Controllers\SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // |--------------------------------------------------------------------------
    // | Locations
    // |--------------------------------------------------------------------------
    Route::get('/provinces/{countryId}', [\App\Http\Controllers\SupplierController::class, 'getProvinces'])->name('get-provinces');


    // |--------------------------------------------------------------------------
    // | Units
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'units'], function () {
        Route::get('/', UnitIndex::class)->name('units');
        Route::get('/create', UnitCreate::class)->name('units.create');
        Route::get('/edit/{id}', UnitCreate::class)->name('units.edit');
    });

    // |--------------------------------------------------------------------------
    // | Factories
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'factories'], function () {
        Route::get('/', FactoryIndex::class)->name('factories');
        Route::get('/create', FactoryCreate::class)->name('factories.create');
        Route::get('/edit/{id}', FactoryCreate::class)->name('factories.edit');
        Route::get('/view/{id}', FactoryCreate::class)->name('factories.view');
    });

    // |--------------------------------------------------------------------------
    // | Production Lines
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'production-lines'], function () {
        Route::get('/{factoryId}', ProductionLineIndex::class)->name('production-lines');
        Route::get('/{factoryId}/create', ProductionLineCreate::class)->name('production-lines.create');
        Route::get('/{factoryId}/edit/{id}', ProductionLineCreate::class)->name('production-lines.edit');
        Route::get('/{factoryId}/view/{id}', ProductionLineCreate::class)->name('production-lines.view');
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
