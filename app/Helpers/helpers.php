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
