<?php $sidebarUser = auth()->user(); ?>
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <a href="<?php echo e(route('dashboard')); ?>" class="logo logo-dark admin-brand-link">
            <span class="admin-brand-shell">
                <img src="<?php echo e(asset('assets/images/ccbrt-logo.svg')); ?>" alt="CCBRT Logo" class="admin-brand-logo admin-brand-logo-lg">
                <span class="admin-brand-text sidebar-brand-copy" style="color:#065321;">
                    <span class="admin-brand-title">CCBRT</span>
                    <span class="admin-brand-subtitle">Feedback System</span>
                </span>
            </span>
         </a>
        <a href="<?php echo e(route('dashboard')); ?>" class="logo logo-light admin-brand-link">
            <span class="admin-brand-shell">
                <img src="<?php echo e(asset('assets/images/ccbrt-logo.svg')); ?>" alt="CCBRT Logo" class="admin-brand-logo admin-brand-logo-lg">
                <span class="admin-brand-text text-white sidebar-brand-copy">
                    <span class="admin-brand-title">CCBRT</span>
                    <span class="admin-brand-subtitle">Feedback System</span>
                </span>
            </span>
         </a>
        <button type="button" class="btn btn-sm p-0 fs-3xl header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">

                <li class="menu-title"><span>Main</span></li>

                <!-- Dashboard — visible to all -->
                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>"
                        href="<?php echo e(route('dashboard')); ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php if($sidebarUser): ?>

                
                <?php if($sidebarUser->canManageComplaints()): ?>
                <li class="menu-title"><span>Feedback</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('feedback.admin.*') ? 'active' : ''); ?>"
                        href="<?php echo e(route('feedback.admin.index')); ?>">
                        <i class="bi bi-chat-left-text"></i>
                        <span>All Submissions</span>
                        <?php $newCount = \App\Models\Feedback::where('status','new')->count(); ?>
                        <?php if($newCount > 0): ?>
                            <span class="badge bg-danger ms-auto"><?php echo e($newCount); ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('feedback.admin.index') && request('status') == 'under_review' ? 'active' : ''); ?>"
                        href="<?php echo e(route('feedback.admin.index')); ?>?status=under_review">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Under Review</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('feedback.admin.index') && request('status') == 'responded' ? 'active' : ''); ?>"
                        href="<?php echo e(route('feedback.admin.index')); ?>?status=responded">
                        <i class="bi bi-check2-circle"></i>
                        <span>Responded</span>
                    </a>
                </li>
                <?php endif; ?>

                
                <?php if($sidebarUser->canViewReports()): ?>
                <li class="menu-title"><span>Reports</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('reports.*') ? 'active' : ''); ?>"
                        href="<?php echo e(route('reports.feedback.index')); ?>">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Feedback Reports</span>
                    </a>
                </li>
                <?php endif; ?>

                
                <?php if($sidebarUser->canManageUsers()): ?>
                <li class="menu-title"><span>Administration</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('users.index') ? 'active' : ''); ?>"
                        href="<?php echo e(route('users.index')); ?>">
                        <i class="bi bi-people"></i>
                        <span>Manage Users</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('users.pending') ? 'active' : ''); ?>"
                        href="<?php echo e(route('users.pending')); ?>">
                        <i class="bi bi-person-check"></i>
                        <span>Pending Approvals</span>
                        <?php $pending = \App\Models\User::where('is_active', false)->where('is_first_user', false)->count(); ?>
                        <?php if($pending > 0): ?>
                            <span class="badge bg-warning text-dark ms-auto"><?php echo e($pending); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php endif; ?>

                <!-- Divider -->
                <li class="menu-title mt-2"><span>Account</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link <?php echo e(request()->routeIs('profile.edit') ? 'active' : ''); ?>"
                        href="<?php echo e(route('profile.edit')); ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>My Profile</span>
                    </a>
                </li>

                <li class="nav-item">
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="nav-link menu-link w-100 text-start border-0 bg-transparent">
                            <i class="bi bi-box-arrow-right text-danger"></i>
                            <span class="text-danger">Logout</span>
                        </button>
                    </form>
                </li>

            </ul>
        </div>
    </div>

    <div class="sidebar-background"></div>
</div>
<?php /**PATH /var/www/Customer Feedback System/resources/views/partials/sidebar.blade.php ENDPATH**/ ?>