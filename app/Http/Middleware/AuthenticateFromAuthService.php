<?php
// app/Http/Middleware/AuthenticateFromAuthService.php

namespace App\Http\Middleware;

use App\Services\AuthServiceClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromAuthService
{
    public function __construct(
        private AuthServiceClient $authService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Check if user has a valid session with auth token
        $accessToken = session('auth_access_token');

        if (!$accessToken) {
            return $this->redirectToLogin($request);
        }

        // Fetch user from auth service (cached)
        $user = $this->authService->getUser($accessToken);

        if (!$user) {
            // Token expired or invalid — clear session and redirect
            session()->forget(['auth_access_token', 'auth_user']);
            return $this->redirectToLogin($request);
        }

        // Check if user can access this module
        if (!$user->canAccessModule('production')) {
            abort(403, 'You do not have access to the Productions module.');
        }

        // Set user on the request so controllers can access it
        $request->attributes->set('auth_user', $user);

        // Also share with views
        view()->share('authUser', $user);

        return $next($request);
    }

    private function redirectToLogin(Request $request): Response
    {
        $loginUrl = config('auth-service.login_url');

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return redirect()->away($loginUrl);
    }
}
