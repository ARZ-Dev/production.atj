<?php

namespace App\Utils;

use App\Models\EventCategories;

class Constants
{
 
    // Statuses
    public const VIEW_STATUS = 1;
    public const CONFIRM_STATUS = 2;
    public const EDIT_STATUS = 3;

    public const DISABLED_ATTRIBUTES = "disabled readonly";

    // Roles
    public const SUPER_ADMIN_ROLE_ID = 1;
    public const PROCUREMENT_MANAGER_ROLE_ID = 2;
    public const SALES_ROLE_ID = 3;

    // Badges
    public const SUPER_ADMIN_BADGE = '<span class="badge badge-center rounded-pill bg-label-danger me-3 w-px-30 h-px-30"><i class="ti ti-device-laptop ti-sm"></i></span>';
    public const PROCUREMENT_MANAGER_BADGE = '<span class="badge badge-center rounded-pill bg-label-success me-3 w-px-30 h-px-30"><i class="ti ti-user ti-sm"></i></span>';
    public const SALES_BADGE = '<span class="badge badge-center rounded-pill bg-label-warning me-3 w-px-30 h-px-30"><i class="ti ti-edit ti-sm"></i></span>';

    // Settings
    public const ROLE_SETTINGS = [
        self::SUPER_ADMIN_ROLE_ID => [
            'id' => self::SUPER_ADMIN_ROLE_ID,
            'badge' => self::SUPER_ADMIN_BADGE,
        ],
        self::PROCUREMENT_MANAGER_ROLE_ID => [
            'id' => self::PROCUREMENT_MANAGER_ROLE_ID,
            'badge' => self::PROCUREMENT_MANAGER_BADGE,
        ],
        self::SALES_ROLE_ID => [
            'id' => self::SALES_ROLE_ID,
            'badge' => self::SALES_BADGE,
        ],
    ];

    public const BADGE_CLASS = [
        "accepted" => "success",
        "pending" => "warning",
        "rejected" => "danger"
    ];

}
