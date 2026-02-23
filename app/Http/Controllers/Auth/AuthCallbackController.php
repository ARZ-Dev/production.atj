<?php
// app/Http/Controllers/Auth/AuthCallbackController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthServiceClient;
use Illuminate\Http\Request;

class AuthCallbackController extends Controller
{
    public function __construct(
        private AuthServiceClient $authService
    ) {}

    /**
     * Handle one-time token login redirect from auth service.
     * Auth service redirects: operation.xyz.com/auth/token-login?token=xxx
     */
    public function tokenLogin(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->away(config('auth-service.login_url'))
                ->with('error', 'No authentication token provided.');
        }

        // Exchange one-time token for access token + user data
        $result = $this->authService->exchangeOneTimeToken($token);

        if (!$result) {
            return redirect()->away(config('auth-service.login_url'))
                ->with('error', 'Invalid or expired login token. Please try again.');
        }

        // Store access token in session
        session([
            'auth_access_token' => $result['access_token'],
            'auth_user' => $result['user'],
            'auth_token_expires' => $result['expires_at'],
        ]);

        // Redirect to operation dashboard
        return redirect()->route('dashboard');
    }

    /**
     * Logout: clear local session and redirect to auth service logout.
     */
    public function logout(Request $request)
    {
        $accessToken = session('auth_access_token');

        // Invalidate local cache
        if ($accessToken) {
            $this->authService->invalidateCache($accessToken);
        }

        // Clear session
        session()->forget([
            'auth_access_token',
            'auth_user',
            'auth_token_expires',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to auth service logout (which then shows login page)
        return redirect()->away(config('auth-service.url') . '/logout');
    }
}
