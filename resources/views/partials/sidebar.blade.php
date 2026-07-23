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
                                @hasAnyPermission(['production.role-list', 'production.permission-list',
                                'production.user-list'])
                                <li class="pe-slide pe-has-sub">
                                    <a href="#collapseSettings"
                                        class="pe-nav-link {{ request()->is('admin/roles*') || request()->is('admin/users*') ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ request()->is('admin/roles*') || request()->is('admin/users*') ? 'true' : 'false' }}"
                                        aria-controls="collapseSettings">
                                        <i class="bi bi-gear pe-nav-icon"></i>
                                        <span class="pe-nav-content">Settings</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse {{ request()->is('admin/roles*') || request()->is('admin/users*') ? 'show' : '' }}"
                                        id="collapseSettings">
                                        @hasPermission('production.role-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('roles.index') }}"
                                                class="pe-nav-link {{ request()->is('admin/roles*') ? " active" : ""
                                                }}">
                                                Roles
                                            </a>
                                        </li>
                                        @endhasPermission
                                        @hasPermission('production.user-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('users.index') }}"
                                                class="pe-nav-link {{ request()->is('admin/users*') ? " active" : ""
                                                }}">
                                                Users
                                            </a>
                                        </li>
                                        @endhasPermission
                                    </ul>
                                </li>
                                @endhasAnyPermission

                                @hasAnyPermission(['production.warehouseType-list', 'production.warehouse-list'])
                                <li class="pe-slide pe-has-sub">
                                    <a href="#collapseWarehouses"
                                        class="pe-nav-link {{ request()->is('admin/warehouse-types*') || request()->is('admin/warehouses*') ? 'collapsed active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ request()->is('admin/warehouse-types*') || request()->is('admin/warehouses*') ? 'true' : 'false' }}"
                                        aria-controls="collapseWarehouses">
                                        <i class="bi bi-house pe-nav-icon"></i>
                                        <span class="pe-nav-content">Warehouses</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse {{ request()->is('admin/warehouse-types*') || request()->is('admin/warehouses*') ? 'show' : '' }}"
                                        id="collapseWarehouses">

                                        @hasPermission('production.warehouseType-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('warehouse-types.index') }}"
                                                class="pe-nav-link {{ request()->is('admin/warehouse-types*') ? "
                                                active" : "" }}">
                                                Warehouse Types
                                            </a>
                                        </li>
                                        @endhasPermission

                                        @hasPermission('production.warehouse-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('warehouses.index') }}"
                                                class="pe-nav-link {{ request()->is('admin/warehouses*') ? " active"
                                                : "" }}">
                                                Warehouses
                                            </a>
                                        </li>
                                        @endhasPermission

                                    </ul>
                                </li>
                                @endhasAnyPermission

                                @hasAnyPermission(['production.suppliers-list'])
                                <li class="pe-slide pe-has-sub">
                                    <a href="#collapseSuppliers"
                                        class="pe-nav-link {{ request()->is('admin/suppliers*') ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ request()->is('admin/suppliers*') ? 'true' : 'false' }}"
                                        aria-controls="collapseSuppliers">
                                        <i class="bi bi-people pe-nav-icon"></i>
                                        <span class="pe-nav-content">Suppliers</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse" id="collapseSuppliers">
                                        @hasPermission('production.suppliers-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('suppliers.index') }}"
                                                class="pe-nav-link {{ request()->is('admin/suppliers*') ? " active" : ""
                                                }}">
                                                Supplier List
                                            </a>
                                        </li>
                                        @endhasPermission
                                    </ul>
                                </li>

                                @endhasAnyPermission



                                @hasAnyPermission(['production.itemType-list', 'production.item-list'])
                                <li class="pe-slide pe-has-sub">
                                    <a href="#collapseItems"
                                        class="pe-nav-link {{ request()->is('admin/item-types*') || request()->is('admin/items*') ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ request()->is('admin/item-types*') || request()->is('admin/items*') ? 'true' : 'false' }}"
                                        aria-controls="collapseItems">
                                        <i class="bi bi-box-seam pe-nav-icon"></i>
                                        <span class="pe-nav-content">Supply Chain</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse" id="collapseItems">
                                        @hasPermission('production.itemType-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('item-types.index') }}"
                                                class="pe-nav-link {{ request()->is('admin/item-types*') ? " active"
                                                : "" }}">
                                                Item Types
                                            </a>
                                        </li>
                                        @endhasPermission
                                        @hasPermission('production.item-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('items.index') }}"
                                                class="pe-nav-link {{ request()->is('admin/items*') ? " active" : ""
                                                }}">
                                                Item List
                                            </a>
                                        </li>
                                        @endhasPermission
                                    </ul>
                                </li>
                                @endhasAnyPermission

                                @hasAnyPermission([
                                'production.itemRequest-list',
                                'production.StockIn-list',
                                'production.StockOut-list',
                                'production.Waste-list',
                                'production.WarehouseInventory-list',
                                'production.Transfer-list'
                                ])
                                <li class="pe-slide pe-has-sub">
                                    @php
                                        $stockTabActive = request()->is('admin/item-requests*')
                                            || request()->is('admin/item-stock-ins*')
                                            || request()->is('admin/item-stock-outs*')
                                            || request()->is('admin/item-wastes*')
                                            || request()->is('admin/item-warehouse-inventory*')
                                            || request()->is('admin/item-transfers*');
                                    @endphp
                                    <a href="#collapseProduction" class="pe-nav-link {{ $stockTabActive ? 'active' : '' }}"
                                       data-bs-toggle="collapse" aria-expanded="{{ $stockTabActive ? 'true' : 'false' }}"
                                       aria-controls="collapseProduction">

                                        <i class="bi bi-rulers pe-nav-icon"></i>
                                        <span class="pe-nav-content">Stock Management</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse {{ $stockTabActive ? "show" : "" }}" id="collapseProduction">
                                        {{-- Requests --}}
                                        @hasPermission('production.itemRequest-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('item-requests') }}"
                                                class="pe-nav-link {{ request()->is('admin/item-requests*') ? 'active' : '' }}">
                                                Item Requests
                                            </a>
                                        </li>
                                        @endhasPermission

                                        {{-- Stock In --}}
                                        @hasPermission('production.StockIn-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('item-stock-ins') }}"
                                                class="pe-nav-link {{ request()->is('admin/item-stock-ins*') ? 'active' : '' }}">
                                                Stock In List
                                            </a>
                                        </li>
                                        @endhasPermission

                                        {{-- Stock Out --}}
                                        @hasPermission('production.StockOut-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('item-stock-outs') }}"
                                                class="pe-nav-link {{ request()->is('admin/item-stock-outs*') ? 'active' : '' }}">
                                                Stock Out List
                                            </a>
                                        </li>
                                        @endhasPermission

                                        {{-- Waste --}}
                                        @hasPermission('production.Waste-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('item-wastes') }}"
                                                class="pe-nav-link {{ request()->is('admin/item-wastes*') ? 'active' : '' }}">
                                                Waste List
                                            </a>
                                        </li>
                                        @endhasPermission

                                        {{-- Transfer --}}
                                        @hasPermission('production.rawMaterialTransfer-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('item-transfers') }}"
                                                class="pe-nav-link {{ request()->is('admin/item-transfers*') ? 'active' : '' }}">
                                                Transfer List
                                            </a>
                                        </li>
                                        @endhasPermission

                                        {{-- Warehouse Inventory --}}
                                        @hasPermission('production.rawMaterialWarehouseInventory-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('item-warehouse-inventory') }}"
                                                class="pe-nav-link {{ request()->is('admin/item-warehouse-inventory*') ? 'active' : '' }}">
                                                Warehouse Inventory
                                            </a>
                                        </li>
                                        @endhasPermission

                                    </ul>
                                </li>
                                @endhasAnyPermission




                                @hasAnyPermission(['production.recipeType-list', 'production.recipe-list'])
                                <li class="pe-slide pe-has-sub">
                                    @php
                                    $schedulingActive = request()->is('admin/recipe-types*')
                                    || request()->is('admin/recipes*');
                                    @endphp
                                    <a href="#collapseRecipes"
                                        class="pe-nav-link {{ $schedulingActive ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ $schedulingActive ? 'true' : 'false' }}"
                                        aria-controls="collapseRecipes">
                                        <i class="bi bi-list pe-nav-icon"></i>
                                        <span class="pe-nav-content">Recipes</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse {{ $schedulingActive ? 'show' : '' }}"
                                        id="collapseRecipes">

                                        @hasPermission('production.recipeType-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('recipe-types') }}"
                                                class="pe-nav-link {{ request()->is('admin/recipe-types*') ? 'active' : '' }}">
                                                Recipe Types
                                            </a>
                                        </li>
                                        @endhasPermission

                                        @hasPermission('production.recipe-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('recipes') }}"
                                                class="pe-nav-link {{ request()->is('admin/recipes*') ? 'active' : '' }}">
                                                Recipes
                                            </a>
                                        </li>
                                        @endhasPermission

                                    </ul>
                                </li>
                                @endhasAnyPermission



                                @hasAnyPermission(['production.shift-list', 'production.eventType-list',
                                'production.plan-list'])
                                <li class="pe-slide pe-has-sub">
                                    @php
                                    $schedulingActive = request()->is('admin/shifts*')
                                    || request()->is('admin/event-types*')
                                    || request()->is('admin/plans*')
                                    || request()->is('admin/events*');
                                    @endphp
                                    <a href="#collapseShifts"
                                        class="pe-nav-link {{ $schedulingActive ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ $schedulingActive ? 'true' : 'false' }}"
                                        aria-controls="collapseShifts">
                                        <i class="bi bi-calendar-event pe-nav-icon"></i>
                                        <span class="pe-nav-content">Scheduling & Events</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse {{ $schedulingActive ? 'show' : '' }}"
                                        id="collapseShifts">

                                        @hasPermission('production.eventType-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('event-types') }}"
                                                class="pe-nav-link {{ request()->is('admin/event-types*') ? 'active' : '' }}">
                                                Event Types
                                            </a>
                                        </li>
                                        @endhasPermission

                                        @hasPermission('production.shift-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('shifts') }}"
                                                class="pe-nav-link {{ request()->is('admin/shifts*') ? 'active' : '' }}">
                                                Shifts
                                            </a>
                                        </li>
                                        @endhasPermission


                                        <li class="pe-slide-item">
                                            <a href="{{ route('plans') }}"
                                                class="pe-nav-link {{ request()->is('admin/plans*') || request()->is('admin/events*') ? 'active' : '' }}">
                                                Plans
                                            </a>
                                        </li>


                                    </ul>
                                </li>
                                @endhasAnyPermission

                                @hasAnyPermission(['production.preparation-list', 'production.line-list', 'production.production-line-list'])
                                <li class="pe-slide pe-has-sub">
                                    @php
                                        $productionActive = request()->is('admin/preparations*')
                                            || request()->is('admin/lines*')
                                            || request()->is('admin/production-lines*');
                                    @endphp
                                    <a href="#collapseProductionModules"
                                        class="pe-nav-link {{ $productionActive ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ $productionActive ? 'true' : 'false' }}"
                                        aria-controls="collapseProductionModules">
                                        <i class="bi bi-diagram-3 pe-nav-icon"></i>
                                        <span class="pe-nav-content">Production Setup</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse {{ $productionActive ? 'show' : '' }}"
                                        id="collapseProductionModules">

                                        @hasPermission('production.preparation-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('preparations') }}"
                                                class="pe-nav-link {{ request()->is('admin/preparations*') ? 'active' : '' }}">
                                                Preparations
                                            </a>
                                        </li>
                                        @endhasPermission

                                        @hasPermission('production.line-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('lines') }}"
                                                class="pe-nav-link {{ request()->is('admin/lines*') ? 'active' : '' }}">
                                                Lines
                                            </a>
                                        </li>
                                        @endhasPermission

                                        @hasPermission('production.production-line-list')
                                        <li class="pe-slide-item">
                                            <a href="{{ route('production-lines') }}"
                                                class="pe-nav-link {{ request()->is('admin/production-lines*') ? 'active' : '' }}">
                                                Production Lines
                                            </a>
                                        </li>
                                        @endhasPermission

                                    </ul>
                                </li>
                                @endhasAnyPermission

                                <li class="pe-slide pe-has-sub">
                                    @php
                                    $guideActive = request()->is('admin/guide-categories*')
                                    || request()->is('admin/guides*');
                                    @endphp
                                    <a href="#collapseGuides"
                                        class="pe-nav-link {{ $guideActive ? 'active' : '' }}"
                                        data-bs-toggle="collapse"
                                        aria-expanded="{{ $guideActive ? 'true' : 'false' }}"
                                        aria-controls="collapseGuides">
                                        <i class="bi bi-journal-text pe-nav-icon"></i>
                                        <span class="pe-nav-content">Guide</span>
                                        <i class="ri-arrow-down-s-line pe-nav-arrow"></i>
                                    </a>

                                    <ul class="pe-slide-menu collapse {{ $guideActive ? 'show' : '' }}"
                                        id="collapseGuides">

                                        <li class="pe-slide-item">
                                            <a href="{{ route('guide-categories') }}"
                                                class="pe-nav-link {{ request()->is('admin/guide-categories*') ? 'active' : '' }}">
                                                Guide Categories
                                            </a>
                                        </li>

                                        <li class="pe-slide-item">
                                            <a href="{{ route('guides') }}"
                                                class="pe-nav-link {{ request()->is('admin/guides*') && !request()->is('admin/guides/view*') ? 'active' : '' }}">
                                                Guides
                                            </a>
                                        </li>

                                    </ul>
                                </li>

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
