<?php
// app/Helpers/helpers.php

if (!function_exists('authUser')) {
    /**
     * Get the authenticated user from the auth service.
     */
    function authUser(): ?\App\Models\AuthUser
    {
        return request()->attributes->get('auth_user');
    }
}

if (!function_exists('authorizeRequest')) {
    /**
     * Check if the authenticated user has access to this page.
     */
    function authorizeRequest($permission)
    {
        $user = authUser();
        if (!$user) {
            abort(403, 'Forbidden: insufficient permissions');
        }

        // Check permissions
        if (!$user->hasPermission($permission)) {
            abort(403, 'Forbidden: insufficient permissions');
        }

        return true;
    }
}
