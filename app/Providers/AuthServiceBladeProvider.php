<?php
// app/Providers/AuthServiceBladeProvider.php (Operation Module)

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AuthServiceBladeProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Usage: @hasPermission('production.orders.view') ... @endhasPermission
         */
        Blade::if('hasPermission', function (string $permission) {
            $user = authUser();
            return $user && $user->hasPermission($permission);
        });

        /**
         * Usage: @hasAnyPermission(['production.orders.view', 'productions.orders.create']) ... @endhasAnyPermission
         */
        Blade::if('hasAnyPermission', function (array $permissions) {
            $user = authUser();
            return $user && $user->hasAnyPermission($permissions);
        });

        /**
         * Usage: @hasRole('production-manager') ... @endhasRole
         */
        Blade::if('hasRole', function (string $role) {
            $user = authUser();
            return $user && $user->hasRole($role);
        });

        /**
         * Usage: @hasAnyRole(['admin', 'production-manager']) ... @endhasAnyRole
         */
        Blade::if('hasAnyRole', function (array $roles) {
            $user = authUser();
            return $user && $user->hasAnyRole($roles);
        });
    }
}
