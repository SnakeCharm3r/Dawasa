@auth
<!-- TOPBAR -->
<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm" type="button" aria-label="Toggle Sidebar">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>

    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2" type="button" aria-label="Open Sidebar">
        <i class="ti ti-layout-sidebar-left-expand"></i>
    </button>

    <div class="d-flex align-items-center ms-auto">
        <ul class="list-unstyled d-flex align-items-center mb-0 gap-2">
            <!-- Notifications -->
            <li class="dropdown">
                <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-2 ms-n2" id="notificationCount">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-0" style="min-width: 300px;">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Notifications</h6>
                        <a href="#" class="small text-primary">Mark all read</a>
                    </div>
                    <ul class="list-unstyled p-0 m-0" id="notificationList">
                        <li class="p-3 text-center text-muted">
                            <i class="ti ti-bell-off mb-2 d-block"></i>
                            No notifications
                        </li>
                    </ul>
                    <div class="p-2 border-top text-center">
                        <a href="#" class="small text-primary">View all notifications</a>
                    </div>
                </div>
            </li>

            <!-- User Profile -->
            <li class="ms-2 dropdown">
                <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" class="d-flex align-items-center gap-2 text-decoration-none">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <span class="text-primary fw-bold small">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 220px;">
                    <div class="d-flex gap-3 align-items-center border-bottom px-3 py-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <span class="text-primary fw-bold">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                            <small class="text-muted">
                                @foreach(auth()->user()->roles as $role)
                                    {{ $role->name }}{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </small>
                        </div>
                    </div>
                    <div class="p-2">
                        <a class="dropdown-item py-2" href="{{ route('dashboard') }}">
                            <i class="ti ti-home me-2"></i> Dashboard
                        </a>
                        <a class="dropdown-item py-2" href="#">
                            <i class="ti ti-user me-2"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger bg-transparent border-0 w-100 text-start">
                                <i class="ti ti-logout me-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>
@endauth
