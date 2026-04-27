<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>

<?php
    use App\Models\Feedback;
    use App\Models\User;

    // --- Totals ---
    $totalFeedback  = Feedback::count();
    $totalUsers     = User::where('is_active', true)->count();
    $pendingUsers   = User::where('is_active', false)->where('is_first_user', false)->count();
    $urgentOpen     = Feedback::where('is_urgent', true)->whereNotIn('status', ['closed'])->count();

    // --- By Status ---
    $statusNew          = Feedback::where('status', 'new')->count();
    $statusUnderReview  = Feedback::where('status', 'under_review')->count();
    $statusResponded    = Feedback::where('status', 'responded')->count();
    $statusClosed       = Feedback::where('status', 'closed')->count();

    // --- By Feedback Type ---
    $typeComplaints  = Feedback::where('feedback_type', 'complaint')->count();
    $typeCompliments = Feedback::where('feedback_type', 'compliment')->count();
    $typeSuggestions = Feedback::where('feedback_type', 'suggestion')->count();
    $typeEnquiries   = Feedback::where('feedback_type', 'enquiry')->count();

    // --- By Service Category ---
    $byCategory = Feedback::selectRaw('service_category, COUNT(*) as total')
        ->groupBy('service_category')
        ->orderByDesc('total')
        ->get();

    // --- Recent ---
    $recentFeedback = Feedback::with('assignedTo')->orderByDesc('created_at')->limit(8)->get();
?>


<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Dashboard</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>


<div class="row g-3">

    
    <div class="col-xl-3 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted small mb-0">Total Submissions</p>
                        <h2 class="mt-3 mb-1 ff-secondary fw-bold"><?php echo e($totalFeedback); ?></h2>
                        <p class="mb-0 small text-muted">All feedback received</p>
                    </div>
                    <div class="flex-shrink-0 ms-3">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle rounded-circle fs-2xl">
                                <i class="bi bi-chat-left-text text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-xl-3 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted small mb-0">Active Users</p>
                        <h2 class="mt-3 mb-1 ff-secondary fw-bold"><?php echo e($totalUsers); ?></h2>
                        <?php if($pendingUsers > 0): ?>
                            <p class="mb-0 small">
                                <span class="badge bg-warning-subtle text-warning"><?php echo e($pendingUsers); ?> pending approval</span>
                            </p>
                        <?php else: ?>
                            <p class="mb-0 small text-muted">All users active</p>
                        <?php endif; ?>
                    </div>
                    <div class="flex-shrink-0 ms-3">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-success-subtle rounded-circle fs-2xl">
                                <i class="bi bi-people text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-xl-3 col-sm-6">
        <a href="<?php echo e(route('feedback.admin.index')); ?>?status=new" class="text-decoration-none">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted small mb-0">Awaiting Review</p>
                        <h2 class="mt-3 mb-1 ff-secondary fw-bold text-danger"><?php echo e($statusNew); ?></h2>
                        <p class="mb-0 small"><span class="badge bg-danger-subtle text-danger">Needs attention</span></p>
                    </div>
                    <div class="flex-shrink-0 ms-3">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-2xl">
                                <i class="bi bi-inbox text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>

    
    <div class="col-xl-3 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted small mb-0">Urgent / Priority</p>
                        <h2 class="mt-3 mb-1 ff-secondary fw-bold <?php echo e($urgentOpen > 0 ? 'text-danger' : ''); ?>"><?php echo e($urgentOpen); ?></h2>
                        <p class="mb-0 small text-muted">Open urgent submissions</p>
                    </div>
                    <div class="flex-shrink-0 ms-3">
                        <div class="avatar-sm">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-2xl">
                                <i class="bi bi-exclamation-triangle text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>



<div class="row g-3 mt-1">

    
    <div class="col-xl-5 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pie-chart me-2 text-primary"></i>Feedback by Type
                </h5>
                <span class="badge bg-primary-subtle text-primary"><?php echo e($totalFeedback); ?> total</span>
            </div>
            <div class="card-body">
                <?php
                    $types = [
                        ['label' => 'Complaints',   'count' => $typeComplaints,  'color' => 'danger',   'icon' => 'bi-exclamation-octagon'],
                        ['label' => 'Compliments',  'count' => $typeCompliments, 'color' => 'success',  'icon' => 'bi-hand-thumbs-up'],
                        ['label' => 'Suggestions',  'count' => $typeSuggestions, 'color' => 'info',     'icon' => 'bi-lightbulb'],
                        ['label' => 'Enquiries',    'count' => $typeEnquiries,   'color' => 'secondary','icon' => 'bi-question-circle'],
                    ];
                ?>

                <div class="vstack gap-3">
                    <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $pct = $totalFeedback > 0 ? round(($t['count'] / $totalFeedback) * 100) : 0; ?>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-xs flex-shrink-0">
                                    <span class="avatar-title bg-<?php echo e($t['color']); ?>-subtle rounded-circle">
                                        <i class="bi <?php echo e($t['icon']); ?> text-<?php echo e($t['color']); ?>"></i>
                                    </span>
                                </div>
                                <span class="fw-medium text-dark small"><?php echo e($t['label']); ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold fs-6"><?php echo e($t['count']); ?></span>
                                <span class="text-muted small">(<?php echo e($pct); ?>%)</span>
                            </div>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-<?php echo e($t['color']); ?>"
                                 role="progressbar"
                                 style="width:<?php echo e($pct); ?>%"
                                 aria-valuenow="<?php echo e($pct); ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-xl-3 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-kanban me-2 text-warning"></i>Status Pipeline
                </h5>
            </div>
            <div class="card-body p-0">
                <?php
                    $statuses = [
                        ['label' => 'New',          'count' => $statusNew,         'color' => 'danger',    'status' => 'new'],
                        ['label' => 'Under Review',  'count' => $statusUnderReview, 'color' => 'warning',   'status' => 'under_review'],
                        ['label' => 'Responded',     'count' => $statusResponded,   'color' => 'success',   'status' => 'responded'],
                        ['label' => 'Closed',        'count' => $statusClosed,      'color' => 'secondary', 'status' => 'closed'],
                    ];
                ?>
                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('feedback.admin.index')); ?>?status=<?php echo e($s['status']); ?>"
                   class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom text-decoration-none hover-bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-<?php echo e($s['color']); ?>-subtle d-inline-flex align-items-center justify-content-center"
                              style="width:10px;height:10px;flex-shrink:0;">
                        </span>
                        <span class="fw-medium small text-dark"><?php echo e($s['label']); ?></span>
                    </div>
                    <span class="badge bg-<?php echo e($s['color']); ?> rounded-pill"><?php echo e($s['count']); ?></span>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <div class="px-4 py-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Total</span>
                    <span class="fw-bold"><?php echo e($totalFeedback); ?></span>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-xl-4 col-lg-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-hospital me-2 text-info"></i>By Service Area
                </h5>
            </div>
            <div class="card-body p-0">
                <?php
                    $categoryLabels = \App\Models\Feedback::SERVICE_CATEGORIES;
                ?>
                <?php $__empty_1 = true; $__currentLoopData = $byCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $pct = $totalFeedback > 0 ? round(($cat->total / $totalFeedback) * 100) : 0;
                    $label = $categoryLabels[$cat->service_category] ?? ucfirst(str_replace('_', ' ', $cat->service_category));
                ?>
                <div class="px-4 py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-medium text-dark"><?php echo e($label); ?></span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold small"><?php echo e($cat->total); ?></span>
                            <span class="text-muted" style="font-size:11px;"><?php echo e($pct); ?>%</span>
                        </div>
                    </div>
                    <div class="progress" style="height:4px;">
                        <div class="progress-bar bg-info" style="width:<?php echo e($pct); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-inbox d-block fs-3 mb-2 opacity-25"></i>No submissions yet.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>



<?php if((auth()->user()->canManageUsers() && $pendingUsers > 0) || $urgentOpen > 0): ?>
<div class="row g-3 mt-1">
    <?php if(auth()->user()->canManageUsers() && $pendingUsers > 0): ?>
    <div class="col-md-6">
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-0 py-3" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-check fs-4"></i>
                <div>
                    <div class="fw-semibold"><?php echo e($pendingUsers); ?> Registration<?php echo e($pendingUsers > 1 ? 's' : ''); ?> Pending Approval</div>
                    <div class="small">New users are waiting for admin review.</div>
                </div>
            </div>
            <a href="<?php echo e(route('users.pending')); ?>" class="btn btn-warning btn-sm flex-shrink-0 ms-3">Review Now</a>
        </div>
    </div>
    <?php endif; ?>
    <?php if($urgentOpen > 0): ?>
    <div class="col-md-6">
        <div class="alert alert-danger d-flex align-items-center justify-content-between mb-0 py-3" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle fs-4"></i>
                <div>
                    <div class="fw-semibold"><?php echo e($urgentOpen); ?> Urgent Submission<?php echo e($urgentOpen > 1 ? 's' : ''); ?> Open</div>
                    <div class="small">High-priority feedback requires immediate attention.</div>
                </div>
            </div>
            <a href="<?php echo e(route('feedback.admin.index')); ?>" class="btn btn-danger btn-sm flex-shrink-0 ms-3">View All</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>



<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Recent Submissions
                </h5>
                <?php if(auth()->user()->canManageComplaints()): ?>
                <a href="<?php echo e(route('feedback.admin.index')); ?>" class="btn btn-sm btn-outline-primary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-centered align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Reference</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Service Area</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <?php if(auth()->user()->canManageComplaints()): ?>
                                <th class="text-end pe-3"></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentFeedback; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-medium text-primary font-monospace small"><?php echo e($item->reference_number); ?></span>
                                    <?php if($item->is_urgent): ?>
                                        <span class="badge bg-danger ms-1" style="font-size:9px;">URGENT</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold small"><?php echo e($item->patient_name); ?></td>
                                <td>
                                    <?php
                                        $tc = ['complaint'=>'bg-danger','compliment'=>'bg-success','suggestion'=>'bg-info','enquiry'=>'bg-secondary'][$item->feedback_type] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo e($tc); ?>"><?php echo e(ucfirst($item->feedback_type)); ?></span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo e(\App\Models\Feedback::SERVICE_CATEGORIES[$item->service_category] ?? ucfirst(str_replace('_', ' ', $item->service_category))); ?>

                                </td>
                                <td><?php echo $item->getStatusBadge(); ?></td>
                                <td class="text-muted small"><?php echo e($item->created_at->diffForHumans()); ?></td>
                                <?php if(auth()->user()->canManageComplaints()): ?>
                                <td class="text-end pe-3">
                                    <a href="<?php echo e(route('feedback.admin.show', $item)); ?>"
                                       class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    No feedback submissions yet.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/dashboard.blade.php ENDPATH**/ ?>