<?php
    $authUser = auth()->user();
    $pendingCount = \App\Models\User::where('is_active', false)->where('is_first_user', false)->count();
    $newFeedbackCount = \App\Models\Feedback::where('status', 'new')->count();
    $latestNewFeedback = \App\Models\Feedback::where('status', 'new')->latest()->limit(5)->get();
    $canReviewPendingUsers = $authUser && ($authUser->isAdmin() || $authUser->isQAHod());
?>
<style>
    #page-topbar {
        z-index: 1045 !important;
    }

    #page-topbar .topbar-head-dropdown .dropdown-menu,
    #page-topbar .topbar-user .dropdown-menu {
        z-index: 1055;
        background-color: #fff;
    }

    #page-topbar .topbar-head-dropdown .dropdown-menu.show,
    #page-topbar .topbar-user .dropdown-menu.show {
        margin-top: 0.75rem;
    }

    #page-topbar .topbar-head-dropdown .dropdown-item,
    #page-topbar .topbar-user .dropdown-item,
    #page-topbar .dropdown-head,
    #page-topbar .dropdown-divider {
        position: relative;
        z-index: 1;
        background-color: #fff;
    }
</style>
<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="<?php echo e(route('dashboard')); ?>" class="logo logo-dark admin-brand-link">
                        <span class="admin-brand-shell">
                            <img src="<?php echo e(asset('assets/images/ccbrt-logo.svg')); ?>" alt="CCBRT Logo" class="admin-brand-logo">
                            <span class="admin-brand-text text-primary">
                                <span class="admin-brand-title">CCBRT</span>
                                <span class="admin-brand-subtitle">Feedback System</span>
                            </span>
                        </span>
                    </a>
                    <a href="<?php echo e(route('dashboard')); ?>" class="logo logo-light admin-brand-link">
                        <span class="admin-brand-shell">
                            <img src="<?php echo e(asset('assets/images/ccbrt-logo.svg')); ?>" alt="CCBRT Logo" class="admin-brand-logo">
                            <span class="admin-brand-text text-white">
                                <span class="admin-brand-title">CCBRT</span>
                                <span class="admin-brand-subtitle">Feedback System</span>
                            </span>
                        </span>
                    </a>
                </div>

                <button type="button"
                    class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger shadow-none"
                    id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span><span></span><span></span>
                    </span>
                </button>
            </div>

            <div class="d-flex align-items-center">

                <!-- Fullscreen toggle -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle"
                        data-toggle="fullscreen">
                        <i class='bi bi-arrows-fullscreen fs-lg'></i>
                    </button>
                </div>

                <!-- New Feedback Notifications -->
                <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle position-relative"
                        id="page-header-notifications-dropdown" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class='bi bi-bell fs-2xl'></i>
                        <?php if($newFeedbackCount > 0): ?>
                            <span class="position-absolute topbar-badge fs-3xs translate-middle badge rounded-pill bg-danger">
                                <?php echo e($newFeedbackCount); ?><span class="visually-hidden">new feedback</span>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 overflow-hidden shadow-lg border-0"
                        aria-labelledby="page-header-notifications-dropdown">
                        <div class="dropdown-head rounded-top bg-white">
                            <div class="p-3 border-bottom border-bottom-dashed">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-0 fs-lg fw-semibold">
                                            Notifications
                                            <?php if($newFeedbackCount > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger fs-sm"><?php echo e($newFeedbackCount); ?></span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="fs-md text-muted mt-1 mb-0">
                                            <?php echo e($newFeedbackCount); ?> new feedback submission(s)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="py-2 bg-white" style="max-height: 300px; overflow-y: auto;">
                            <?php if($newFeedbackCount > 0): ?>
                                <h6 class="text-overflow text-muted fs-sm my-2 px-3 text-uppercase">New Submissions</h6>
                                <?php $__currentLoopData = $latestNewFeedback; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feedbackNotification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(route('feedback.admin.show', $feedbackNotification)); ?>" class="dropdown-item notification-item d-block">
                                        <div class="d-flex align-items-start">
                                            <div class="avatar-xs me-3 flex-shrink-0">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-lg">
                                                    <i class="bi bi-chat-left-text"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <h6 class="mt-0 mb-1 fs-md fw-semibold text-truncate"><?php echo e($feedbackNotification->patient_name ?: 'Anonymous User'); ?></h6>
                                                <p class="mb-1 fs-sm text-muted text-truncate"><?php echo e($feedbackNotification->reference_number); ?> · <?php echo e(ucfirst($feedbackNotification->feedback_type)); ?></p>
                                                <p class="mb-0 fs-xs text-muted"><?php echo e($feedbackNotification->created_at->diffForHumans()); ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>

                            <?php if($canReviewPendingUsers && $pendingCount > 0): ?>
                                <h6 class="text-overflow text-muted fs-sm my-2 px-3 text-uppercase">User Approvals</h6>
                                <a href="<?php echo e(route('users.pending')); ?>" class="dropdown-item notification-item d-block">
                                    <div class="d-flex align-items-start">
                                        <div class="avatar-xs me-3 flex-shrink-0">
                                            <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-lg">
                                                <i class="bi bi-person-plus"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h6 class="mt-0 mb-1 fs-md fw-semibold"><?php echo e($pendingCount); ?> Pending Approval<?php echo e($pendingCount > 1 ? 's' : ''); ?></h6>
                                            <p class="mb-0 fs-sm text-muted">Review newly registered users waiting for access.</p>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>

                            <?php if($newFeedbackCount === 0 && !($canReviewPendingUsers && $pendingCount > 0)): ?>
                                <div class="text-center py-4 text-muted px-3">
                                    <i class="bi bi-check-circle fs-2xl d-block mb-2"></i>
                                    <p class="mb-0 fs-sm">No new notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 border-top border-top-dashed text-center bg-white">
                            <a href="<?php echo e(route('feedback.admin.index')); ?>" class="btn btn-sm btn-outline-primary w-100">View feedback queue</a>
                        </div>
                    </div>
                </div>

                <!-- Pending Users Badge (Admin/QA HOD only) -->
                <?php if($canReviewPendingUsers && $pendingCount > 0): ?>
                <div class="ms-1 header-item d-none d-sm-flex">
                    <a href="<?php echo e(route('users.pending')); ?>" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle position-relative" title="<?php echo e($pendingCount); ?> pending user approval(s)">
                        <i class='bi bi-person-plus fs-xl'></i>
                        <span class="position-absolute topbar-badge fs-3xs translate-middle badge rounded-pill bg-warning">
                            <?php echo e($pendingCount); ?>

                        </span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- User Dropdown -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn shadow-none" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <span class="rounded-circle header-profile-user bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
                                style="width:32px;height:32px;font-size:13px;">
                                <?php echo e(strtoupper(substr($authUser->fname ?? $authUser->name, 0, 1))); ?><?php echo e(strtoupper(substr($authUser->lname ?? '', 0, 1))); ?>

                            </span>
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">
                                    <?php echo e($authUser->getFullName()); ?>

                                </span>
                                <span class="d-none d-xl-block ms-1 fs-sm user-name-sub-text">
                                    <?php echo e($authUser->getRoleLabel()); ?>

                                </span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end overflow-hidden shadow-lg border-0" style="min-width: 240px;">
                        <div class="px-3 py-3 border-bottom bg-white">
                            <h6 class="dropdown-header p-0 mb-1">Welcome, <?php echo e($authUser->fname ?? $authUser->name); ?>!</h6>
                            <p class="text-muted small mb-0"><?php echo e($authUser->getRoleLabel()); ?></p>
                        </div>
                        <a class="dropdown-item py-2" href="<?php echo e(route('profile.edit')); ?>">
                            <i class="mdi mdi-account-circle text-muted fs-lg align-middle me-1"></i>
                            <span class="align-middle">My Profile</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="dropdown-item text-danger py-2">
                                <i class="mdi mdi-logout text-danger fs-lg align-middle me-1"></i>
                                <span class="align-middle">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</header>
<?php /**PATH /var/www/Customer Feedback System/resources/views/partials/header.blade.php ENDPATH**/ ?>