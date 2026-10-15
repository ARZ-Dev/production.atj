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
            return $this->unauthenticated($request);
        }

        // Fetch user from auth service (cached)
        $user = $this->authService->getUser($accessToken);

        if (!$user) {
            // Token expired or invalid — clear session and redirect
            session()->forget(['auth_access_token', 'auth_user']);
            return $this->unauthenticated($request);
        }

        // Check if user can access this module
        if (!$user->canAccessModule('production')) {
            abort(403, 'You do not have access to the Productions module.');
        }

        // Set user on the request so controllers can access it. On a Livewire
        // action this runs as persistent middleware against a *copy* of the
        // request, so set it on the live request too — that's where authUser()
        // looks.
        $request->attributes->set('auth_user', $user);
        request()->attributes->set('auth_user', $user);

        // Also share with views
        view()->share('authUser', $user);

        return $next($request);
    }

    private function unauthenticated(Request $request): Response
    {
        // A Livewire action: answer 419, which Livewire turns into "This page has
        // expired — refresh?"; the reload is then a full page load that redirects
        // to the login. (fetch() would follow a redirect to the auth service
        // cross-origin and fail silently, and Livewire ignores any other response
        // a persistent middleware returns.)
        if ($request->hasHeader('X-Livewire')) {
            abort(419, 'Your session has expired. Please log in again.');
        }

        return $this->redirectToLogin($request);
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
