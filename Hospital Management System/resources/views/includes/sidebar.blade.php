@auth
<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar">
    <div class="logo-area">
        <a href="{{ route('dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
            <div class="bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="ti ti-heartbeat text-primary"></i>
            </div>
            <span class="logo-text ms-2 fw-semibold text-dark">HMS</span>
        </a>
    </div>

    <ul class="nav flex-column">
        <!-- Main Menu -->
        <li class="px-4 py-2 mt-2">
            <small class="nav-text text-muted text-uppercase fs-7">Main</small>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="ti ti-layout-dashboard"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>

        @can('patients.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('patients.*') ? 'active' : '' }}" href="{{ route('patients.index') }}">
                <i class="ti ti-users"></i>
                <span class="nav-text">Patients</span>
            </a>
        </li>
        @endcan

        @can('appointments.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="#">
                <i class="ti ti-calendar-event"></i>
                <span class="nav-text">Appointments</span>
            </a>
        </li>
        @endcan

        @can('inventory.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                <i class="ti ti-packages"></i>
                <span class="nav-text">Inventory</span>
            </a>
        </li>
        @endcan

        @canany(['reports.view_sales', 'reports.view_patients', 'reports.view_staff', 'reports.view_financial', 'reports.view_inventory'])
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <i class="ti ti-chart-bar"></i>
                <span class="nav-text">Reports</span>
            </a>
        </li>
        @endcanany

        @can('billing.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}" href="#">
                <i class="ti ti-receipt-2"></i>
                <span class="nav-text">Billing</span>
            </a>
        </li>
        @endcan

        <!-- Administration -->
        @canany(['users.view', 'roles.view', 'permissions.view', 'system_preferences.view'])
        <li class="px-4 pt-4 pb-2">
            <small class="nav-text text-muted text-uppercase fs-7">Administration</small>
        </li>

        @can('users.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                <i class="ti ti-user-cog"></i>
                <span class="nav-text">Users</span>
            </a>
        </li>
        @endcan

        @can('roles.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                <i class="ti ti-shield-check"></i>
                <span class="nav-text">Roles</span>
            </a>
        </li>
        @endcan

        @can('permissions.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">
                <i class="ti ti-key"></i>
                <span class="nav-text">Permissions</span>
            </a>
        </li>
        @endcan

        @can('system_preferences.view')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('system-preferences.*') ? 'active' : '' }}" href="{{ route('system-preferences.index') }}">
                <i class="ti ti-settings"></i>
                <span class="nav-text">Settings</span>
            </a>
        </li>
        @endcan
        @endcanany

        <!-- Account -->
        <li class="px-4 pt-4 pb-2">
            <small class="nav-text text-muted text-uppercase fs-7">Account</small>
        </li>

        <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}" class="d-inline w-100">
                @csrf
                <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                    <i class="ti ti-logout"></i>
                    <span class="nav-text">Logout</span>
                </button>
            </form>
        </li>
    </ul>
</aside>
@endauth
