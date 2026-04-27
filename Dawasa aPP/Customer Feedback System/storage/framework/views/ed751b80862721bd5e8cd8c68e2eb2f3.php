<?php $__env->startSection('title', 'Feedback Reports'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $summaryCards = [
        ['label' => 'Total Feedback', 'value' => $summary['total'], 'icon' => 'bi-bar-chart-line', 'class' => 'primary'],
        ['label' => 'Portal', 'value' => $summary['portal'], 'icon' => 'bi-globe2', 'class' => 'success'],
        ['label' => 'Manual / Paper', 'value' => $summary['manual'], 'icon' => 'bi-file-earmark-text', 'class' => 'warning'],
        ['label' => 'Other Sources', 'value' => $summary['other'], 'icon' => 'bi-diagram-3', 'class' => 'info'],
        ['label' => 'Reviewed', 'value' => $summary['reviewed'], 'icon' => 'bi-person-check', 'class' => 'secondary'],
        ['label' => 'Pending Review', 'value' => $summary['pending_review'], 'icon' => 'bi-hourglass-split', 'class' => 'danger'],
    ];
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Feedback Reports</h4>
                <p class="text-muted mb-0 small mt-1">Review all submitted feedback, response ownership, reviewer activity, and source breakdowns.</p>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Feedback Reports</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php $__currentLoopData = $summaryCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-xl-2 col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-<?php echo e($card['class']); ?> bg-<?php echo e($card['class']); ?>-subtle"
                         style="width:48px;height:48px;font-size:20px;flex-shrink:0;">
                        <i class="bi <?php echo e($card['icon']); ?>"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 lh-1"><?php echo e($card['value']); ?></div>
                        <div class="text-muted small"><?php echo e($card['label']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Report Filters</h5>
        <a href="<?php echo e(route('reports.feedback.export', request()->query())); ?>" class="btn btn-success btn-sm">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('reports.feedback.index')); ?>" class="row g-3">
            <div class="col-md-4 col-lg-3">
                <label for="search" class="form-label small fw-semibold">Search</label>
                <input type="text" id="search" name="search" value="<?php echo e($filters['search'] ?? ''); ?>" class="form-control"
                       placeholder="Reference, patient, or report text">
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="source" class="form-label small fw-semibold">Source</label>
                <select id="source" name="source" class="form-select">
                    <option value="">All Sources</option>
                    <?php $__currentLoopData = \App\Models\Feedback::SOURCES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php echo e(($filters['source'] ?? '') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php $__currentLoopData = \App\Models\Feedback::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php echo e(($filters['status'] ?? '') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label for="reviewed_by" class="form-label small fw-semibold">Reviewer</label>
                <select id="reviewed_by" name="reviewed_by" class="form-select">
                    <option value="">All Reviewers</option>
                    <?php $__currentLoopData = $reviewers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reviewer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($reviewer->id); ?>" <?php echo e((string) ($filters['reviewed_by'] ?? '') === (string) $reviewer->id ? 'selected' : ''); ?>><?php echo e($reviewer->getFullName()); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label for="assigned_to" class="form-label small fw-semibold">Assigned User</label>
                <select id="assigned_to" name="assigned_to" class="form-select">
                    <option value="">All Assignees</option>
                    <?php $__currentLoopData = $assignableUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignableUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($assignableUser->id); ?>" <?php echo e((string) ($filters['assigned_to'] ?? '') === (string) $assignableUser->id ? 'selected' : ''); ?>><?php echo e($assignableUser->getFullName()); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-lg-1 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
                    <a href="<?php echo e(route('reports.feedback.index')); ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="bi bi-table me-2"></i>Feedback Report Table</h5>
        <span class="badge bg-secondary"><?php echo e($reports->total()); ?> records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Ref #</th>
                        <th>Source</th>
                        <th>Submitter Role</th>
                        <th>Report</th>
                        <th>Reviewer</th>
                        <th>Date Reviewed</th>
                        <th>Reviewer Response</th>
                        <th>Assigned User</th>
                        <th>Created</th>
                        <th>Reviewed</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $latestResponse = $report->latest_response;
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold text-primary font-monospace small"><?php echo e($report->reference_no); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo e($report->getStatusLabel()); ?> • <?php echo e($report->getFeedbackTypeLabel()); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo e($report->getSourceLabel()); ?></span>
                                <div class="text-muted mt-1" style="font-size:11px;"><?php echo e($report->getServiceCategoryLabel()); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo e($report->getSubmitterRoleLabel()); ?></span>
                            </td>
                            <td style="min-width:280px;">
                                <div class="fw-semibold small"><?php echo e($report->patient_name ?: 'Anonymous / Not Provided'); ?></div>
                                <div class="text-muted small"><?php echo e(\Illuminate\Support\Str::limit($report->report_excerpt, 140) ?: 'No report text available.'); ?></div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?php echo e($report->reviewedBy?->getFullName() ?? 'Not yet reviewed'); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo e($report->reviewedBy?->getRoleLabel() ?? '—'); ?></div>
                            </td>
                            <td class="text-muted small"><?php echo e($report->reviewed_at?->format('d M Y, H:i') ?? '—'); ?></td>
                            <td style="min-width:240px;">
                                <div class="small text-muted"><?php echo e(\Illuminate\Support\Str::limit($latestResponse?->content ?? 'No reviewer response recorded.', 120)); ?></div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?php echo e($report->assignedTo?->getFullName() ?? 'Unassigned'); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo e($report->assignedTo?->getRoleLabel() ?? '—'); ?></div>
                            </td>
                            <td class="text-muted small"><?php echo e($report->created_at?->format('d M Y, H:i') ?? '—'); ?></td>
                            <td class="text-muted small"><?php echo e($report->reviewed_at?->format('d M Y, H:i') ?? '—'); ?></td>
                            <td class="text-end pe-3">
                                <a href="<?php echo e(route('feedback.admin.show', $report)); ?>" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                <p class="fw-medium mb-1">No feedback report records found</p>
                                <p class="small mb-0">Try adjusting your filters or export the current empty result set.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($reports->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($reports->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/reports/feedback.blade.php ENDPATH**/ ?>