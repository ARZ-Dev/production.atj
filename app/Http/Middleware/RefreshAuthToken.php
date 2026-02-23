<?php
// app/Http/Middleware/RefreshAuthToken.php

namespace App\Http\Middleware;

use App\Services\AuthServiceClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class RefreshAuthToken
{
    public function __construct(
        private AuthServiceClient $authService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $expiresAt = session('auth_token_expires');

        if ($expiresAt) {
            $expiresDate = Carbon::parse($expiresAt);

            // If token expires within 1 day, clear cache to force re-fetch
            if ($expiresDate->diffInHours(now()) < 24) {
                $accessToken = session('auth_access_token');
                if ($accessToken) {
                    $this->authService->invalidateCache($accessToken);
                }
            }

            // If token has expired, force re-login
            if ($expiresDate->isPast()) {
                session()->forget([
                    'auth_access_token',
                    'auth_user',
                    'auth_token_expires',
                ]);

                return redirect()->away(config('auth-service.login_url'));
            }
        }

        return $next($request);
    }
}
