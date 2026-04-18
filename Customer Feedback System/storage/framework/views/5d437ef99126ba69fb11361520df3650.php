<?php $__env->startSection('title', 'Manage Users'); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">User Management</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
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
        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(auth()->user()->canManageUsers() && $pendingCount > 0): ?>
            <div class="card border-warning-subtle shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-warning text-dark"><?php echo e($pendingCount); ?></span>
                                <h5 class="mb-0">Pending user registrations</h5>
                            </div>
                            <p class="text-muted small mb-0">New staff accounts are waiting for review and approval.</p>
                        </div>
                        <a href="<?php echo e(route('users.pending')); ?>" class="btn btn-warning btn-sm flex-shrink-0">
                            <i class="bi bi-person-check me-1"></i>Review pending users
                        </a>
                    </div>
                    <div class="row g-3 mt-1">
                        <?php $__currentLoopData = $pendingUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pendingUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                    <div class="fw-semibold text-dark"><?php echo e($pendingUser->getFullName()); ?></div>
                                    <div class="text-muted small"><?php echo e($pendingUser->email); ?></div>
                                    <div class="text-muted small mt-2">
                                        <span class="badge bg-secondary"><?php echo e($pendingUser->getRoleLabel()); ?></span>
                                        <span class="ms-2"><?php echo e($pendingUser->created_at->diffForHumans()); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="bi bi-people me-2"></i>All System Users
                    <span class="badge bg-secondary ms-2"><?php echo e($users->total()); ?></span>
                </h5>
                <?php if(auth()->user()->canManageUsers()): ?>
                <a href="<?php echo e(route('users.pending')); ?>" class="btn btn-warning btn-sm">
                    <i class="bi bi-person-check me-1"></i> Pending Approvals
                    <?php if($pendingCount > 0): ?>
                        <span class="badge bg-dark ms-1"><?php echo e($pendingCount); ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Joined</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                             style="width:36px;height:36px;font-size:13px;">
                                            <?php echo e(strtoupper(substr($user->fname ?? $user->name, 0, 1))); ?><?php echo e(strtoupper(substr($user->lname ?? '', 0, 1))); ?>

                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">
                                                <?php echo e($user->getFullName()); ?>

                                                <?php if($user->is_first_user): ?>
                                                    <span class="badge bg-purple-subtle text-purple ms-1" style="background:#ede9fe;color:#6d28d9;">System Admin</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if($user->dob): ?>
                                                <small class="text-muted">DOB: <?php echo e($user->dob->format('d M Y')); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted"><?php echo e($user->email); ?></td>
                                <td>
                                     <?php
                                         $roleClass = [
                                             'admin'       => 'bg-primary',
                                             'qa_hod'      => 'bg-info',
                                             'qa_officer'  => 'bg-success',
                                             'call_center' => 'bg-warning text-dark',
                                             'coo'         => 'bg-danger',
                                            'line_manager'=> 'bg-dark',
                                         ][$user->role] ?? 'bg-secondary';
                                     ?>
                                     <span class="badge <?php echo e($roleClass); ?>"><?php echo e($user->getRoleLabel()); ?></span>
                                 </td>
                                <td>
                                    <span class="badge <?php echo e($user->is_active ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'); ?>">
                                        <i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>
                                        <?php echo e($user->is_active ? 'Active' : 'Pending'); ?>

                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo e($user->approvedBy?->getFullName() ?? ($user->is_first_user ? 'System' : '—')); ?>

                                </td>
                                <td class="text-muted small"><?php echo e($user->created_at->format('d M Y')); ?></td>
                                <td class="text-end pe-3">
                                    <a href="<?php echo e(route('users.show', $user)); ?>"
                                       class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <?php if(!$user->is_first_user && $user->id !== auth()->id() && auth()->user()->canManageUsers()): ?>
                                        <?php if($user->is_active): ?>
                                            <form method="POST" action="<?php echo e(route('users.deactivate', $user)); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit"
                                                    onclick="return confirm('Deactivate <?php echo e(addslashes($user->getFullName())); ?>?')"
                                                    class="btn btn-sm btn-outline-danger">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?php echo e(route('users.show', $user)); ?>"
                                               class="btn btn-sm btn-outline-success">Approve</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                                    No users found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if($users->hasPages()): ?>
            <div class="card-footer">
                <?php echo e($users->links()); ?>

            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/users/index.blade.php ENDPATH**/ ?>