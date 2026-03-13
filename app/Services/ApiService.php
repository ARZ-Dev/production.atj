<?php
// app/Services/AuthServiceClient.php

namespace App\Services;

use App\Models\AuthUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('auth-service.api_url');
    }

    /**
     * Get the Bearer token from session.
     */
    private function getToken(): ?string
    {
        return session('auth_access_token');
    }

    // ─── HTTP Helpers ────────────────────────────────────────

    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::withToken($this->getToken())
                ->timeout(10)
                ->get("{$this->apiUrl}{$endpoint}", $query);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error("Auth API GET {$endpoint} failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Auth service unavailable.'];
        }
    }

    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withToken($this->getToken())
                ->timeout(10)
                ->post("{$this->apiUrl}{$endpoint}", $data);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error("Auth API POST {$endpoint} failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Auth service unavailable.'];
        }
    }

    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withToken($this->getToken())
                ->timeout(10)
                ->put("{$this->apiUrl}{$endpoint}", $data);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error("Auth API PUT {$endpoint} failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Auth service unavailable.'];
        }
    }

    public function delete(string $endpoint, array $query = []): array
    {
        try {
            $response = Http::withToken($this->getToken())
                ->timeout(10)
                ->delete("{$this->apiUrl}{$endpoint}", $query);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            Log::error("Auth API DELETE {$endpoint} failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Auth service unavailable.'];
        }
    }

    public function handleResponse($response): array
    {
        $data = $response->json() ?? [];

        if ($response->status() === 401) {
            // Token expired — could trigger re-auth here
            $data['auth_expired'] = true;
        }

        if (!isset($data['success'])) {
            $data['success'] = $response->successful();
        }

        $data['http_status'] = $response->status();

        return $data;
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
