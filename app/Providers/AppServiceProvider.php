<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateFromAuthService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Re-run the auth-service check on every Livewire action, not just on
        // full page loads — otherwise actions run on the user snapshot saved at
        // login, after the token expires or the user loses access.
        Livewire::addPersistentMiddleware([
            AuthenticateFromAuthService::class,
        ]);
    }
}
