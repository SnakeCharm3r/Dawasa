<?php $__env->startSection('title', 'Feedback: ' . $feedback->reference_number); ?>

<?php $__env->startSection('content'); ?>
<style>
    .feedback-detail-sticky-card {
        top: 90px;
        z-index: 10;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Feedback Detail</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('feedback.admin.index')); ?>">Feedback</a></li>
                    <li class="breadcrumb-item active"><?php echo e($feedback->reference_number); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="row g-4">

    
    <div class="col-xl-8">

        <?php if(session('status')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo e(session('status')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <span class="font-monospace text-primary"><?php echo e($feedback->reference_number); ?></span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <?php if($feedback->is_priority): ?>
                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>High Priority</span>
                    <?php endif; ?>
                    <?php echo $feedback->getStatusBadge(); ?>

                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Patient Name</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->patient_name); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Email</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->patient_email ?: '—'); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Phone</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->patient_phone ?: '—'); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Visit Date</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->visit_date ? \Carbon\Carbon::parse($feedback->visit_date)->format('d M Y') : '—'); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Category</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->getServiceCategoryLabel()); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Type</p>
                        <?php $tc = ['complaint'=>'bg-danger','compliment'=>'bg-success','suggestion'=>'bg-info','enquiry'=>'bg-secondary'][$feedback->feedback_type] ?? 'bg-secondary'; ?>
                        <span class="badge <?php echo e($tc); ?>"><?php echo e(ucfirst($feedback->feedback_type)); ?></span>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Service Rating</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->service_rating ? $feedback->getServiceRatingLabel() : '—'); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Confidentiality Kept</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->getConfidentialityLabel() ?: '—'); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Submitted</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->created_at->format('d M Y, H:i')); ?></p>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <p class="text-muted small text-uppercase mb-1">Assigned To</p>
                        <p class="fw-semibold mb-0"><?php echo e($feedback->assignedTo?->getFullName() ?? 'Unassigned'); ?></p>
                    </div>
                </div>

                <?php if($feedback->service_units_summary): ?>
                    <div class="mb-4">
                        <p class="text-muted small text-uppercase mb-2">Service Offered Today</p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php $__currentLoopData = $feedback->service_units_labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceUnitLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="badge bg-primary-subtle text-primary"><?php echo e($serviceUnitLabel); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <p class="text-muted small text-uppercase mb-2">Overall Experience</p>
                    <div class="p-3 rounded" style="background:#f8f9fa;border-left:4px solid #065321;">
                        <p class="mb-0" style="white-space:pre-wrap;"><?php echo e($feedback->overall_experience ?: $feedback->message ?: '—'); ?></p>
                    </div>
                </div>

                <?php if($feedback->improvement_suggestion): ?>
                    <div class="mb-4">
                        <p class="text-muted small text-uppercase mb-2">Suggested Improvement</p>
                        <div class="p-3 rounded" style="background:#f8f9fa;border-left:4px solid #198754;">
                            <p class="mb-0" style="white-space:pre-wrap;"><?php echo e($feedback->improvement_suggestion); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if($feedback->confidentiality_comment): ?>
                    <div class="mb-4">
                        <p class="text-muted small text-uppercase mb-2">Confidentiality Explanation</p>
                        <div class="p-3 rounded" style="background:#fff8e1;border-left:4px solid #ffc107;">
                            <p class="mb-0" style="white-space:pre-wrap;"><?php echo e($feedback->confidentiality_comment); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-0">
                    <p class="text-muted small text-uppercase mb-2">Additional Comments</p>
                    <div class="p-3 rounded" style="background:#f8f9fa;border-left:4px solid #065321;">
                        <p class="mb-0" style="white-space:pre-wrap;"><?php echo e($feedback->message ?: 'No additional comments provided.'); ?></p>
                    </div>
                </div>

                <?php if($feedback->attachment): ?>
                    <div class="mt-3">
                        <p class="text-muted small text-uppercase mb-1">Attachment</p>
                        <a href="<?php echo e(asset('storage/' . $feedback->attachment)); ?>" target="_blank"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-paperclip me-1"></i>View Attachment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-sticky me-2"></i>Internal Notes
                    <span class="badge bg-secondary ms-1"><?php echo e($feedback->internalNotes->count()); ?></span>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php $__empty_1 = true; $__currentLoopData = $feedback->internalNotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="fw-semibold small text-dark"><?php echo e($note->author?->getFullName() ?? 'System'); ?></span>
                        <span class="text-muted" style="font-size:11px;"><?php echo e($note->created_at->format('d M Y, H:i')); ?></span>
                    </div>
                    <p class="mb-0 small text-muted"><?php echo e($note->content); ?></p>
                    <?php if($note->is_coo_comment): ?>
                        <span class="badge bg-danger-subtle text-danger mt-1"><i class="bi bi-eye me-1"></i>COO Comment</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-sticky d-block fs-4 mb-1 opacity-25"></i>No internal notes yet.
                </div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-chat-dots me-2"></i>Patient Responses
                    <span class="badge bg-secondary ms-1"><?php echo e($feedback->patientResponses->count()); ?></span>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php $__empty_1 = true; $__currentLoopData = $feedback->patientResponses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $response): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="fw-semibold small"><?php echo e($response->sender?->getFullName() ?? 'Staff'); ?></span>
                        <div class="d-flex align-items-center gap-2">
                            <?php if($response->is_public): ?>
                                <span class="badge bg-success-subtle text-success" style="font-size:10px;">Public</span>
                            <?php endif; ?>
                            <span class="text-muted" style="font-size:11px;"><?php echo e($response->created_at->format('d M Y, H:i')); ?></span>
                        </div>
                    </div>
                    <p class="mb-0 small"><?php echo e($response->content); ?></p>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-chat-dots d-block fs-4 mb-1 opacity-25"></i>No responses sent yet.
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    
    <div class="col-xl-4">
        <div class="card sticky-top feedback-detail-sticky-card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-gear me-2"></i>Actions</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Manage status, assignment, and responses for this submission.</p>

                
                <?php if($feedback->reviewedBy || $feedback->reviewed_at): ?>
                <div class="mb-3 p-2 rounded" style="background:#eef7e8;border-left:3px solid #0b6b2c;">
                    <p class="text-muted small text-uppercase mb-1">Last Reviewed</p>
                    <p class="fw-semibold small mb-0"><?php echo e($feedback->reviewedBy?->getFullName() ?? 'Not reviewed'); ?></p>
                    <p class="text-muted small mb-0"><?php echo e($feedback->reviewed_at?->format('d M Y, H:i') ?? '—'); ?></p>
                </div>
                <?php endif; ?>

                
                <div class="mb-3">
                    <label for="assigned_to" class="form-label small fw-semibold">Assign To</label>
                    <form method="POST" action="<?php echo e(route('feedback.admin.assignment', $feedback)); ?>" class="d-flex gap-2">
                        <?php echo csrf_field(); ?>
                        <select id="assigned_to" name="assigned_to" class="form-select form-select-sm">
                            <option value="">-- Unassigned --</option>
                            <?php $__currentLoopData = $assignableUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($user->id); ?>" <?php echo e($feedback->assigned_to === $user->id ? 'selected' : ''); ?>>
                                    <?php echo e($user->getFullName()); ?> (<?php echo e($user->getRoleLabel()); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                    </form>
                    <?php $__errorArgs = ['assigned_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger small mt-2"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Update Status</label>
                    <form method="POST" action="<?php echo e(route('feedback.admin.status', $feedback)); ?>" class="d-flex gap-2">
                        <?php echo csrf_field(); ?>
                        <select name="status" class="form-select form-select-sm">
                            <option value="new"          <?php echo e($feedback->status=='new'          ?'selected':''); ?>>New</option>
                            <option value="under_review" <?php echo e($feedback->status=='under_review' ?'selected':''); ?>>Under Review</option>
                            <option value="responded"    <?php echo e($feedback->status=='responded'    ?'selected':''); ?>>Responded</option>
                            <option value="closed"       <?php echo e($feedback->status=='closed'       ?'selected':''); ?>>Closed</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                    <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="text-danger small mt-2"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3">
                    <label for="note_content" class="form-label small fw-semibold">Internal Comment</label>
                    <form method="POST" action="<?php echo e(route('feedback.admin.note', $feedback)); ?>">
                        <?php echo csrf_field(); ?>
                        <textarea id="note_content" name="note_content" rows="4" class="form-control form-control-sm <?php $__errorArgs = ['note_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Add an internal review note for the team..."><?php echo e(old('note_content')); ?></textarea>
                        <?php $__errorArgs = ['note_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm mt-2 w-100">
                            <i class="bi bi-sticky me-1"></i>Save Internal Comment
                        </button>
                    </form>
                </div>

                <div class="mb-3">
                    <label for="response_content" class="form-label small fw-semibold">Client Response</label>
                    <form method="POST" action="<?php echo e(route('feedback.admin.response', $feedback)); ?>">
                        <?php echo csrf_field(); ?>
                        <textarea id="response_content" name="response_content" rows="5" class="form-control form-control-sm <?php $__errorArgs = ['response_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Write the response that should appear on the tracking portal and be emailed to the client..."><?php echo e(old('response_content')); ?></textarea>
                        <?php $__errorArgs = ['response_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <div class="form-text small">This response will appear on the feedback tracking portal. If the client provided an email address, it will also be emailed to them.</div>
                        <button type="submit" class="btn btn-success btn-sm mt-2 w-100">
                            <i class="bi bi-send me-1"></i>Send Response to Client
                        </button>
                    </form>
                </div>

                <hr>
                <div class="text-center">
                    <a href="<?php echo e(route('feedback.admin.index')); ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Back to All
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/feedback/admin/show.blade.php ENDPATH**/ ?>