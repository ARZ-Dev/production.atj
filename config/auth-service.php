<?php
// config/auth-service.php

return [
    'url' => env('AUTH_SERVICE_URL', 'http://localhost:8000'),
    'api_url' => env('AUTH_SERVICE_API_URL', 'http://localhost:8000/api'),
    'login_url' => env('AUTH_SERVICE_URL', 'http://localhost:8000') . '/login?module=production',

    // Cache duration for user data (seconds)
    'cache_ttl' => env('AUTH_CACHE_TTL', 300), // 5 minutes
];
