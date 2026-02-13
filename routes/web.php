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
use Illuminate\Support\Facades\Route;


// Route::get('/', function () {
//     return view('index');
// });


Route::get('/', Login::class)->name('login');

Route::middleware(['auth'])->prefix('admin')->group(function () {

    Route::get('/dashboard', DashboardView::class)->name('dashboard');
    Route::get('/permissions', PermissionView::class)->name('permissions');
    Route::get('/roles', RoleView::class)->name('roles');



    // |--------------------------------------------------------------------------
    // |Companies
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'companies'], function () {
        Route::get('/', CompanyIndex::class)->name('companies');
        Route::get('/create', CompanyCreate::class)->name('companies.create');
        Route::get('/edit/{id}', CompanyCreate::class)->name('companies.edit');
        Route::get('/view/{id}', CompanyCreate::class)->name('companies.view');
    });


    // |--------------------------------------------------------------------------
    // | Users
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', UserIndex::class)->name('users');
        Route::get('/create', UserCreate::class)->name('users.create');
        Route::get('/edit/{id}', UserCreate::class)->name('users.edit');
        Route::get('/view/{id}', UserCreate::class)->name('users.view');
    });

    // |--------------------------------------------------------------------------
    // | Warehouses Types
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'warehouse-types'], function () {
        Route::get('/', WarehouseTypeIndex::class)->name('warehouse-types');
        Route::get('/create', WarehouseTypeCreate::class)->name('warehouse-types.create');
        Route::get('/edit/{id}', WarehouseTypeCreate::class)->name('warehouse-types.edit');
        Route::get('/view/{id}', WarehouseTypeCreate::class)->name('warehouse-types.view');
    });

    // |--------------------------------------------------------------------------
    // | Machine Types
    // |--------------------------------------------------------------------------

    Route::group(['prefix' => 'machine-types'], function () {
        Route::get('/', MachineTypeIndex::class)->name('machine-types');
        Route::get('/create', MachineTypeCreate::class)->name('machine-types.create');
        Route::get('/edit/{id}', MachineTypeCreate::class)->name('machine-types.edit');
        Route::get('/view/{id}', MachineTypeCreate::class)->name('machine-types.view');
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
