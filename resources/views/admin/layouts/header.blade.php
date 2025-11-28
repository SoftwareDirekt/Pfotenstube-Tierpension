<nav
    class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme mt-2    "
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
        <i class="mdi mdi-menu mdi-24px"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

        <ul class="navbar-nav flex-row align-items-center ms-auto">
        
        <!-- Notifications -->
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-2">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <i class="mdi mdi-bell-outline mdi-20px" id="notification_bell"></i>
                <span class="position-absolute badge rounded-circle bg-danger d-none" id="notification-dot" style="width: 8px; height: 8px; padding: 0; top: 2px; right: 2px;">
                    <span class="visually-hidden">unread messages</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end py-0" style="min-width: 400px;">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-2">
                        <h6 class="mb-0 me-auto">Benachrichtigungen</h6>
                        <a href="javascript:void(0)" class="dropdown-notifications-all text-body" id="mark-all-read-link">
                            <small>Alle als gelesen markieren</small>
                        </a>
                    </div>
                </li>
                <li class="dropdown-notifications-list scrollable-container" style="max-height: 350px; overflow-y: auto;">
                    <ul class="list-group list-group-flush" id="notifications-container">
                        <li class="dropdown-item text-center p-3">
                            <small class="text-muted">Keine neuen Benachrichtigungen</small>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
        <!--/ Notifications -->

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown me-2">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="avatar avatar-online">
                <img src="assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
            </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="/">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                    <div class="avatar avatar-online">
                        <img src="assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                    </div>
                    <div class="flex-grow-1">
                    <span class="fw-semibold d-block">Admin</span>
                    <small class="text-muted">Admin</small>
                    </div>
                </div>
                </a>
            </li>
            <li>
                <div class="dropdown-divider"></div>
            </li>
            <li>
                <a class="dropdown-item" href="{{route('admin.settings')}}">
                <i class="mdi mdi-cog-outline me-2"></i>
                <span class="align-middle">Einstellungen</span>
                </a>
            </li>
            <li>
                <div class="dropdown-divider"></div>
            </li>
            <li>
                <a class="dropdown-item" href="{{route('admin.logout')}}">
                <i class="mdi mdi-logout me-2"></i>
                <span class="align-middle">Ausloggen</span>
                </a>
            </li>
            </ul>
        </li>
        <!--/ User -->
        </ul>
    </div>

    <!-- Search Small Screens -->
    <div class="navbar-search-wrapper search-input-wrapper d-none">
        <input
        type="text"
        class="form-control search-input container-xxl border-0"
        placeholder="Search..."
        aria-label="Search..." />
        <i class="mdi mdi-close search-toggler cursor-pointer"></i>
    </div>
</nav>