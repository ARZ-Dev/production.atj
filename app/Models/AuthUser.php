<?php
// app/Models/AuthUser.php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;

class AuthUser implements Authenticatable
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $phone;
    public ?string $avatar;
    public array $roles;
    public array $permissions;
    public array $accessibleModules;
    public string $accessToken;

    public function __construct(array $data, string $accessToken)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->phone = $data['phone'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->roles = collect($data['roles'] ?? [])->toArray();
        $this->permissions = collect($data['permissions'] ?? [])->toArray();
        $this->accessibleModules = $data['accessible_modules'] ?? [];
        $this->accessToken = $accessToken;
    }

    /**
     * Check if user has a given permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (in_array('super-admin', $this->roles)) {
            return true;
        }
        return in_array($permission, $this->permissions);
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (in_array('super-admin', $this->roles)) {
            return true;
        }
        return !empty(array_intersect($this->permissions, $permissions));
    }

    /**
     * Check if user has a given role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($this->roles, $roles));
    }

    /**
     * Check if user can access a specific module.
     */
    public function canAccessModule(string $module): bool
    {
        return in_array($module, $this->accessibleModules);
    }

    // ─── Authenticatable Interface ────────────────────────────

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return '';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // Not used
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
