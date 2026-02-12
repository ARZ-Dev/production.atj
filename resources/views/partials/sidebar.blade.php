<aside class="pe-app-sidebar" id="sidebar">
    <div class="pe-app-sidebar-logo px-6 d-flex align-items-center position-relative mt-3 mb-2">
        <!--begin::Brand Image-->
        <a href="{{route('dashboard')}}" class="fs-18 fw-semibold text-center">
            <img height="60" class="pe-app-sidebar-logo-default d-none" src="{{asset('assets/images/favicon.png')}}">
            <img height="15" class="pe-app-sidebar-logo-minimize d-none" src="{{asset('assets/images/favicon.png')}}">
        </a>
        <!--end::Brand Image-->
    </div>
    <nav class="pe-app-sidebar-menu nav nav-pills simplebar-scrollable-y" data-simplebar="init" id="sidebar-simplebar">
        <div class="simplebar-wrapper" style="margin: 0px;">
            <div class="simplebar-height-auto-observer-wrapper">
                <div class="simplebar-height-auto-observer"></div>
            </div>
            <div class="simplebar-mask">
                <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                    <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                        style="height: 100%; overflow: hidden scroll;">
                        <div class="simplebar-content" style="padding: 0px;">
                            <ul class="pe-main-menu list-unstyled">
                                <li class="pe-slide pe-has-sub">
                                    <a href="{{ route('dashboard') }}"
                                        class="pe-nav-link {{ request()->is('admin/dashboard') ? " active" : "" }}">
                                        <i class="bi bi-speedometer2 pe-nav-icon"></i>
                                        <span class="pe-nav-content">Dashboard</span>
                                    </a>
                                </li>
                                @hasAnyPermission(['production.company-list' , 'production.role-list', 'production.permission-list', 'production.user-list'])
                                <li class="pe-slide pe-has-sub">
                                    <a href="#collapseDashboards"
                                        class="pe-nav-link {{ request()->is('admin/companies*') || request()->is('admin/roles*') || request()->is('admin/users*') ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ request()->is('admin/companies*') || request()->is('admin/roles*') || request()->is('admin/users*') ? 'true' : 'false' }}"
                                        aria-controls="collapseDashboards">
                                        <i class="bi bi-gear pe-nav-icon"></i>
                                        <span class="pe-nav-content">Settings</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse" id="collapseDashboards">
                                        @hasPermission('production.company-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('companies') }}"
                                                class="pe-nav-link {{ request()->is('admin/companies*') ? " active" : ""
                                                }}">
                                                Companies
                                            </a>
                                        </li>
                                        @endhasPermission
                                        @hasPermission('production.role-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('roles') }}"
                                                class="pe-nav-link {{ request()->is('admin/roles*') ? " active" : ""
                                                }}">
                                                Roles
                                            </a>
                                        </li>
                                        @endhasPermission
                                        @hasPermission('production.user-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('users') }}"
                                                class="pe-nav-link {{ request()->is('admin/users*') ? " active" : ""
                                                }}">
                                                Users
                                            </a>
                                        </li>
                                        @endhasPermission
                                    </ul>
                                </li>
                                @endhasAnyPermission

                                @hasAnyPermission(['production.warehouseType-list', 'production.machineType-list', 'production.factory-list'])
                                <li class="pe-slide pe-has-sub">
                                    <a href="#collapseWarehouses"
                                        class="pe-nav-link {{ request()->is('admin/warehouse-types*') || request()->is('admin/machine-types*') || request()->is('admin/factories*') || request()->is('admin/production-lines*') ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ request()->is('admin/warehouse-types*') || request()->is('admin/machine-types*') || request()->is('admin/factories*') || request()->is('admin/production-lines*') ? 'true' : 'false' }}"
                                        aria-controls="collapseWarehouses">
                                        <i class="bi bi-house pe-nav-icon"></i>
                                        <span class="pe-nav-content">Facilities</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse" id="collapseWarehouses">

                                        @hasPermission('production.warehouseType-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('warehouse-types') }}"
                                                class="pe-nav-link {{ request()->is('admin/warehouse-types*') ? "
                                                active" : "" }}">
                                                Warehouse Types
                                            </a>
                                        </li>
                                        @endhasPermission
                                        @hasPermission('production.machineType-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('machine-types') }}"
                                                class="pe-nav-link {{ request()->is('admin/machine-types*') ? " active"
                                                : "" }}">
                                                Machine Types
                                            </a>
                                        </li>
                                        @endhasPermission
                                        @hasPermission('production.factory-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('factories') }}"
                                                class="pe-nav-link {{ request()->is('admin/factories*') ? " active" : ""
                                                }}">
                                                Factories
                                            </a>
                                        </li>
                                        @endhasPermission

                                    </ul>
                                </li>
                                @endhasAnyPermission
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="simplebar-placeholder" style="width: 239px; height: 1476px;"></div>
        </div>
        <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
            <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
        </div>
        <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
            <div class="simplebar-scrollbar"
                style="height: 135px; transform: translate3d(0px, 0px, 0px); display: block;"></div>
        </div>
    </nav>
</aside>
