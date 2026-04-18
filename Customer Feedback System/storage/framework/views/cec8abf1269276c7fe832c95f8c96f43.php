<?php $__env->startSection('title', 'User: ' . $user->getFullName()); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">User Profile</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('users.index')); ?>">Users</a></li>
                    <li class="breadcrumb-item active"><?php echo e($user->getFullName()); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xl-4">
        <!-- Profile Card -->
        <div class="card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center text-white fw-bold mb-3"
                     style="width:72px;height:72px;font-size:24px;">
                    <?php echo e(strtoupper(substr($user->fname ?? $user->name, 0, 1))); ?><?php echo e(strtoupper(substr($user->lname ?? '', 0, 1))); ?>

                </div>
                <h5 class="mb-1"><?php echo e($user->getFullName()); ?></h5>
                <p class="text-muted small mb-3"><?php echo e($user->email); ?></p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <?php
                        $rc = ['admin'=>'bg-primary','qa_hod'=>'bg-info','qa_officer'=>'bg-success','call_center'=>'bg-warning text-dark','coo'=>'bg-danger','line_manager'=>'bg-dark'];
                    ?>
                    <span class="badge <?php echo e($rc[$user->role] ?? 'bg-secondary'); ?>"><?php echo e($user->getRoleLabel()); ?></span>
                    <span class="badge <?php echo e($user->is_active ? 'bg-success' : 'bg-warning text-dark'); ?>">
                        <?php echo e($user->is_active ? 'Active' : 'Pending Approval'); ?>

                    </span>
                    <?php if($user->is_first_user): ?>
                        <span class="badge" style="background:#ede9fe;color:#6d28d9;">System Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row g-3">
                    <div class="col-6">
                        <p class="text-muted small text-uppercase mb-1">First Name</p>
                        <p class="fw-medium mb-0"><?php echo e($user->fname ?? '—'); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small text-uppercase mb-1">Middle Name</p>
                        <p class="fw-medium mb-0"><?php echo e($user->mname ?: '—'); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small text-uppercase mb-1">Last Name</p>
                        <p class="fw-medium mb-0"><?php echo e($user->lname ?? '—'); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small text-uppercase mb-1">Date of Birth</p>
                        <p class="fw-medium mb-0"><?php echo e($user->dob?->format('d M Y') ?? '—'); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small text-uppercase mb-1">Registered</p>
                        <p class="fw-medium mb-0"><?php echo e($user->created_at->format('d M Y')); ?></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small text-uppercase mb-1">Approved At</p>
                        <p class="fw-medium mb-0"><?php echo e($user->approved_at?->format('d M Y') ?? '—'); ?></p>
                    </div>
                    <div class="col-12">
                        <p class="text-muted small text-uppercase mb-1">Approved By</p>
                        <p class="fw-medium mb-0"><?php echo e($user->approvedBy?->getFullName() ?? ($user->is_first_user ? 'System (First User)' : '—')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">

        <?php if(session('status')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo e(session('status')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(!$user->is_first_user && auth()->user()->canManageUsers()): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-shield-lock me-2"></i>Admin Actions</h5>
            </div>
            <div class="card-body">

                
                <?php if(!$user->is_active): ?>
                <div class="mb-4">
                    <h6 class="fw-semibold mb-2">Approve &amp; Assign Role</h6>
                    <form method="POST" action="<?php echo e(route('users.approve', $user)); ?>" class="d-flex gap-2">
                        <?php echo csrf_field(); ?>
                        <select name="role" class="form-select form-select-sm" style="max-width:240px;" required>
                            <option value="">-- Assign Role --</option>
                            <option value="qa_officer"  <?php echo e($user->role=='qa_officer'  ?'selected':''); ?>>QA Officer</option>
                            <option value="call_center" <?php echo e($user->role=='call_center' ?'selected':''); ?>>Call Center</option>
                            <option value="qa_hod"      <?php echo e($user->role=='qa_hod'      ?'selected':''); ?>>QA Head of Department</option>
                            <option value="coo"         <?php echo e($user->role=='coo'         ?'selected':''); ?>>Chief Operating Officer</option>
                            <option value="line_manager" <?php echo e($user->role=='line_manager' ?'selected':''); ?>>Line Manager</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-lg me-1"></i>Approve User
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                
                <?php if($user->is_active && auth()->user()->isAdmin()): ?>
                <div class="mb-4">
                    <h6 class="fw-semibold mb-2">Change Role</h6>
                    <form method="POST" action="<?php echo e(route('users.role', $user)); ?>" class="d-flex gap-2">
                        <?php echo csrf_field(); ?>
                        <select name="role" class="form-select form-select-sm" style="max-width:240px;" required>
                            <option value="qa_officer"  <?php echo e($user->role=='qa_officer'  ?'selected':''); ?>>QA Officer</option>
                            <option value="call_center" <?php echo e($user->role=='call_center' ?'selected':''); ?>>Call Center</option>
                            <option value="qa_hod"      <?php echo e($user->role=='qa_hod'      ?'selected':''); ?>>QA Head of Department</option>
                            <option value="coo"         <?php echo e($user->role=='coo'         ?'selected':''); ?>>Chief Operating Officer</option>
                            <option value="line_manager" <?php echo e($user->role=='line_manager' ?'selected':''); ?>>Line Manager</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-arrow-repeat me-1"></i>Update Role
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                
                <div class="pt-3 border-top">
                    <h6 class="fw-semibold mb-2">Account Status</h6>
                    <?php if($user->is_active): ?>
                    <form method="POST" action="<?php echo e(route('users.deactivate', $user)); ?>" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit"
                            onclick="return confirm('Deactivate <?php echo e(addslashes($user->getFullName())); ?>? They will no longer be able to login.')"
                            class="btn btn-danger btn-sm">
                            <i class="bi bi-person-x me-1"></i>Deactivate User
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="<?php echo e(route('users.activate', $user)); ?>" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-person-check me-1"></i>Activate User
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <a href="<?php echo e(route('users.index')); ?>" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Back to all users
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/users/show.blade.php ENDPATH**/ ?>