<?php
// app/Services/AuthServiceClient.php

namespace App\Services;

use App\Models\AuthUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthServiceClient
{
    private string $apiUrl;
    private int $cacheTtl;

    public function __construct()
    {
        $this->apiUrl = config('auth-service.api_url');
        $this->cacheTtl = config('auth-service.cache_ttl', 300);
    }

    /**
     * Exchange a one-time token for an access token and user data.
     */
    public function exchangeOneTimeToken(string $token): ?array
    {
        try {
            $response = Http::timeout(10)
                ->post("{$this->apiUrl}/auth/verify-token", [
                    'token' => $token,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Auth Service: Failed to verify one-time token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Auth Service: Exception while verifying token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get user info using an access token.
     * Results are cached to reduce load on auth service.
     */
    public function getUser(string $accessToken): ?AuthUser
    {
        $cacheKey = 'auth_user:' . md5($accessToken);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($accessToken) {
            try {
                $response = Http::timeout(10)
                    ->withToken($accessToken)
                    ->get("{$this->apiUrl}/user/me");

                if ($response->successful()) {
                    return new AuthUser($response->json(), $accessToken);
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Auth Service: Exception while fetching user', [
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Check a specific permission for the token bearer.
     */
    public function checkPermission(string $accessToken, string $permission): bool
    {
        $cacheKey = "auth_perm:{$permission}:" . md5($accessToken);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($accessToken, $permission) {
            try {
                $response = Http::timeout(10)
                    ->withToken($accessToken)
                    ->post("{$this->apiUrl}/user/check-permission", [
                        'permission' => $permission,
                    ]);

                if ($response->successful()) {
                    return $response->json('has_permission', false);
                }

                return false;
            } catch (\Exception $e) {
                Log::error('Auth Service: Exception while checking permission', [
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        });
    }

    /**
     * Invalidate cached user data (call on logout).
     */
    public function invalidateCache(string $accessToken): void
    {
        $cacheKey = 'auth_user:' . md5($accessToken);
        Cache::forget($cacheKey);
    }
}
