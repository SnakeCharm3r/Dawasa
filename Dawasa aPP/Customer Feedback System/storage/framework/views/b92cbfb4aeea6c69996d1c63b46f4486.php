<?php $__env->startSection('title', 'Feedback Submissions'); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Feedback Submissions</h4>
            <div class="page-title-right d-flex gap-2">
                <a href="<?php echo e(route('feedback.manual.create')); ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Add Manual Feedback
                </a>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Feedback</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <?php
        $statusCards = [
            ['label' => 'New',          'key' => 'new',          'icon' => 'bi-inbox',          'color' => 'danger'],
            ['label' => 'Under Review', 'key' => 'under_review', 'icon' => 'bi-hourglass-split', 'color' => 'warning'],
            ['label' => 'Responded',    'key' => 'responded',    'icon' => 'bi-check2-circle',   'color' => 'success'],
            ['label' => 'Closed',       'key' => 'closed',       'icon' => 'bi-archive',         'color' => 'secondary'],
        ];
    ?>
    <?php $__currentLoopData = $statusCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-6 col-md-3">
        <a href="<?php echo e(route('feedback.admin.index')); ?>?status=<?php echo e($card['key']); ?>"
           class="card text-decoration-none <?php echo e(request('status') == $card['key'] ? 'border-primary' : ''); ?>">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-<?php echo e($card['color']); ?> bg-<?php echo e($card['color']); ?>-subtle"
                     style="width:44px;height:44px;font-size:20px;flex-shrink:0;">
                    <i class="bi <?php echo e($card['icon']); ?>"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 text-dark lh-1"><?php echo e($counts[$card['key']]); ?></div>
                    <div class="text-muted small"><?php echo e($card['label']); ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <i class="bi bi-chat-left-text me-2"></i>
            <?php if(request('status')): ?>
                <?php echo e(ucfirst(str_replace('_', ' ', request('status')))); ?> Submissions
            <?php else: ?>
                All Submissions
            <?php endif; ?>
            <span class="badge bg-secondary ms-2"><?php echo e($feedbacks->total()); ?></span>
        </h5>
        <div class="d-flex gap-2">
            <?php if(request('status')): ?>
                <a href="<?php echo e(route('feedback.admin.index')); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Clear Filter
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Ref #</th>
                        <th>Patient</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Submitted</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $feedbacks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feedback): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="ps-3">
                            <span class="fw-semibold text-primary font-monospace small"><?php echo e($feedback->reference_number); ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold small"><?php echo e($feedback->patient_name); ?></div>
                            <div class="text-muted" style="font-size:11px;"><?php echo e($feedback->patient_email ?: '—'); ?></div>
                        </td>
                        <td>
                            <div class="text-muted small"><?php echo e($feedback->service_units_summary ?: $feedback->getServiceCategoryLabel()); ?></div>
                            <div class="text-muted" style="font-size:11px;"><?php echo e($feedback->getServiceCategoryLabel()); ?></div>
                        </td>
                        <td>
                            <?php
                                $typeClass = ['complaint'=>'bg-danger','compliment'=>'bg-success','suggestion'=>'bg-info','enquiry'=>'bg-secondary'][$feedback->feedback_type] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?php echo e($typeClass); ?>"><?php echo e(ucfirst($feedback->feedback_type)); ?></span>
                            <?php if($feedback->service_rating): ?>
                                <div class="text-muted mt-1" style="font-size:11px;">Rating: <?php echo e($feedback->getServiceRatingLabel()); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($feedback->is_priority): ?>
                                <span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>High</span>
                            <?php else: ?>
                                <span class="text-muted small">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $feedback->getStatusBadge(); ?>

                        </td>
                        <td class="text-muted small"><?php echo e($feedback->assignedTo?->getFullName() ?? '—'); ?></td>
                        <td class="text-muted small"><?php echo e($feedback->created_at->format('d M Y')); ?></td>
                        <td class="text-end pe-3">
                            <a href="<?php echo e(route('feedback.admin.show', $feedback)); ?>"
                               class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                            No feedback submissions found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($feedbacks->hasPages()): ?>
    <div class="card-footer">
        <?php echo e($feedbacks->links()); ?>

    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/feedback/admin/index.blade.php ENDPATH**/ ?>