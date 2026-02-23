<?php
// app/Helpers/helpers.php

if (!function_exists('authUser')) {
    /**
     * Get the authenticated user from the auth service.
     */
    function authUser(): ?\App\Models\AuthUser
    {
        $authUser = request()->attributes->get('auth_user');
        if (!$authUser) {
            // check if the auth user is stored in the session (for web requests)
            $authUser = session('auth_user');
            // cast to AuthUser if it's an array
            if (is_array($authUser)) {
                $authUser = new \App\Models\AuthUser($authUser, $authUser['accessToken'] ?? '');
            }
        }
        return $authUser;
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
