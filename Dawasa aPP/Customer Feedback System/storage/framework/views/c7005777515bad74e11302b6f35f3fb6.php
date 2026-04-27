<?php $__env->startSection('title', 'Pending Approvals'); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Pending Approvals</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('users.index')); ?>">Users</a></li>
                    <li class="breadcrumb-item active">Pending</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">

        <?php if(session('status')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e(session('status')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-check me-2 text-warning"></i>Users Awaiting Approval
                </h5>
                <p class="text-muted small mb-0 mt-1">Review registrations and assign roles before granting access.</p>
            </div>
            <div class="card-body p-0">

                <?php $__empty_1 = true; $__currentLoopData = $pendingUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="p-4 border-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center text-dark fw-bold flex-shrink-0"
                             style="width:46px;height:46px;font-size:15px;">
                            <?php echo e(strtoupper(substr($user->fname ?? $user->name, 0, 1))); ?><?php echo e(strtoupper(substr($user->lname ?? '', 0, 1))); ?>

                        </div>
                        <div>
                            <div class="fw-semibold text-dark fs-6"><?php echo e($user->getFullName()); ?></div>
                            <div class="text-muted small"><?php echo e($user->email); ?></div>
                            <div class="text-muted small mt-1">
                                <span class="me-2"><i class="bi bi-calendar3 me-1"></i>DOB: <?php echo e($user->dob?->format('d M Y') ?? 'N/A'); ?></span>
                                <span class="me-2">&bull; Requested: <span class="badge bg-secondary"><?php echo e($user->getRoleLabel()); ?></span></span>
                                <span>&bull; Registered <?php echo e($user->created_at->diffForHumans()); ?></span>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="<?php echo e(route('users.approve', $user)); ?>" class="d-flex align-items-center gap-2 flex-shrink-0">
                        <?php echo csrf_field(); ?>
                        <select name="role" class="form-select form-select-sm" style="min-width:200px;" required>
                            <option value="">-- Assign Role --</option>
                            <option value="qa_officer"  <?php echo e($user->role=='qa_officer'  ? 'selected':''); ?>>QA Officer</option>
                            <option value="call_center" <?php echo e($user->role=='call_center' ? 'selected':''); ?>>Call Center</option>
                            <option value="qa_hod"      <?php echo e($user->role=='qa_hod'      ? 'selected':''); ?>>QA Head of Department</option>
                            <option value="coo"         <?php echo e($user->role=='coo'         ? 'selected':''); ?>>Chief Operating Officer</option>
                            <option value="line_manager" <?php echo e($user->role=='line_manager' ? 'selected':''); ?>>Line Manager</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm px-3">
                            <i class="bi bi-check-lg me-1"></i>Approve
                        </button>
                    </form>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle fs-1 d-block mb-3 opacity-25"></i>
                    <p class="fw-medium">No pending approvals</p>
                    <p class="small">All registered users have been reviewed.</p>
                </div>
                <?php endif; ?>

            </div>
            <?php if($pendingUsers->hasPages()): ?>
            <div class="card-footer"><?php echo e($pendingUsers->links()); ?></div>
            <?php endif; ?>
        </div>

        <a href="<?php echo e(route('users.index')); ?>" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Back to all users
        </a>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/users/pending.blade.php ENDPATH**/ ?>