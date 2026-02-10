<?php
// app/Http/Middleware/CheckAuthServicePermission.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthServicePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = authUser();

        if (!$user) {
            abort(403, 'Unauthenticated.');
        }

        foreach ($permissions as $permission) {
            if (!$user->hasPermission($permission)) {
                abort(403, "You do not have the required permission: {$permission}");
            }
        }

        return $next($request);
    }
}
