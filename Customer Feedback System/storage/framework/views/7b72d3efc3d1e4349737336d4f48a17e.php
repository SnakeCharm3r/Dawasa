<?php $__env->startSection('title', __('portal.meta.feedback_track_title')); ?>

<?php $__env->startSection('content'); ?>
<!-- Page Header -->
<section class="hero-section" style="padding: 3rem 0;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="hero-title" style="font-size: 2rem;">
                    <i class="bi bi-search me-2"></i><?php echo e(__('portal.feedback_track.hero_title')); ?>

                </h1>
                <p class="hero-subtitle mb-0">
                    <?php echo e(__('portal.feedback_track.hero_subtitle')); ?>

                </p>
            </div>
        </div>
    </div>
</section>

<!-- Track Form Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card card-ccbrt">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-input-cursor me-2"></i><?php echo e(__('portal.feedback_track.card_title')); ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo e(route('feedback.track.submit')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            
                            <?php if(!empty($referenceLookupError)): ?>
                                <div class="alert alert-danger mb-3">
                                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo e($referenceLookupError); ?>

                                </div>
                            <?php elseif($errors->has('reference_no')): ?>
                                <div class="alert alert-danger mb-3">
                                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo e($errors->first('reference_no')); ?>

                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <label for="reference_no" class="form-label required"><?php echo e(__('portal.feedback_track.reference_label')); ?></label>
                                <div class="input-group input-group-lg mb-3">
                                    <span class="input-group-text" style="background-color: var(--ccbrt-navy); color: white; border-color: var(--ccbrt-navy);">
                                        <i class="bi bi-ticket-detailed"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-ccbrt <?php $__errorArgs = ['reference_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="reference_no" name="reference_no" 
                                           value="<?php echo e(old('reference_no', request('reference_no'))); ?>"
                                           placeholder="<?php echo e(__('portal.feedback_track.reference_placeholder')); ?>" required
                                           style="text-transform: uppercase;">
                                </div>
                                <div class="form-text text-muted">
                                    <?php echo e(__('portal.feedback_track.reference_help')); ?>

                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-ccbrt-primary btn-lg">
                                    <i class="bi bi-search me-2"></i><?php echo e(__('portal.feedback_track.submit_button')); ?>

                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Results Section (shown when feedback is found) -->
<?php if(isset($feedback)): ?>
<section class="pb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-ccbrt">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i><?php echo e(__('portal.feedback_track.details_title')); ?></h5>
                        <span class="badge bg-<?php echo e($feedback->status == 'new' ? 'primary' : ($feedback->status == 'under_review' ? 'warning' : ($feedback->status == 'responded' ? 'info' : 'secondary'))); ?> fs-6">
                            <?php echo e($feedback->getStatusLabel()); ?>

                        </span>
                    </div>
                    <div class="card-body">
                        <!-- Reference Number -->
                        <div class="reference-box" style="padding: 1.5rem;">
                            <p class="mb-1 opacity-75"><?php echo e(__('portal.feedback_track.reference_box_label')); ?></p>
                            <div class="reference-number" style="font-size: 1.5rem;"><?php echo e($feedback->reference_no); ?></div>
                        </div>

                        <!-- Status Timeline -->
                        <h6 class="mb-3 mt-4" style="color: var(--ccbrt-navy);"><?php echo e(__('portal.feedback_track.status_timeline_title')); ?></h6>
                        <div class="status-timeline mb-4">
                            <div class="status-step <?php echo e(in_array($feedback->status, ['new', 'under_review', 'responded', 'closed']) ? 'completed' : ''); ?>

                                        <?php echo e($feedback->status == 'new' ? 'active' : ''); ?>">
                                <div class="status-dot">
                                    <i class="bi bi-inbox"></i>
                                </div>
                                <div class="status-label"><?php echo e(__('portal.options.statuses.new')); ?></div>
                            </div>
                            <div class="status-step <?php echo e(in_array($feedback->status, ['under_review', 'responded', 'closed']) ? 'completed' : ''); ?>

                                        <?php echo e($feedback->status == 'under_review' ? 'active' : ''); ?>">
                                <div class="status-dot">
                                    <i class="bi bi-search"></i>
                                </div>
                                <div class="status-label"><?php echo e(__('portal.options.statuses.under_review')); ?></div>
                            </div>
                            <div class="status-step <?php echo e(in_array($feedback->status, ['responded', 'closed']) ? 'completed' : ''); ?>

                                        <?php echo e($feedback->status == 'responded' ? 'active' : ''); ?>">
                                <div class="status-dot">
                                    <i class="bi bi-chat-left-text"></i>
                                </div>
                                <div class="status-label"><?php echo e(__('portal.options.statuses.responded')); ?></div>
                            </div>
                            <div class="status-step <?php echo e($feedback->status == 'closed' ? 'completed' : ''); ?>

                                        <?php echo e($feedback->status == 'closed' ? 'active' : ''); ?>">
                                <div class="status-dot">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                <div class="status-label"><?php echo e(__('portal.options.statuses.closed')); ?></div>
                            </div>
                        </div>

                        <!-- Feedback Details -->
                        <h6 class="mb-3" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;">
                            <?php echo e(__('portal.feedback_track.submission_info')); ?>

                        </h6>
                        <div class="row mb-4">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted"><?php echo e(__('portal.feedback_track.submitted_on')); ?></small>
                                <p class="mb-0 fw-medium"><?php echo e($feedback->created_at->format('F j, Y \a\t g:i A')); ?></p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted"><?php echo e(__('portal.feedback_track.service_category')); ?></small>
                                <p class="mb-0 fw-medium"><?php echo e($feedback->getServiceCategoryLabel()); ?></p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted"><?php echo e(__('portal.feedback_track.feedback_type')); ?></small>
                                <p class="mb-0 fw-medium"><?php echo e($feedback->getFeedbackTypeLabel()); ?></p>
                            </div>
                            <?php if($feedback->service_units_summary): ?>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted"><?php echo e(__('portal.feedback_track.service_offered')); ?></small>
                                <p class="mb-0 fw-medium"><?php echo e($feedback->service_units_summary); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if($feedback->service_rating): ?>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted"><?php echo e(__('portal.feedback_track.service_rating')); ?></small>
                                <p class="mb-0 fw-medium"><?php echo e($feedback->getServiceRatingLabel()); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if(!is_null($feedback->confidentiality_respected)): ?>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted"><?php echo e(__('portal.feedback_track.confidentiality_kept')); ?></small>
                                <p class="mb-0 fw-medium"><?php echo e($feedback->getConfidentialityLabel()); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if($feedback->visit_date): ?>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted"><?php echo e(__('portal.feedback_track.date_of_visit')); ?></small>
                                <p class="mb-0 fw-medium"><?php echo e($feedback->visit_date->format('F j, Y')); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <h6 class="mb-3" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;">
                            <?php echo e(__('portal.feedback_track.overall_experience')); ?>

                        </h6>
                        <div class="p-3 rounded-3 mb-4" style="background-color: #f8f9fa;">
                            <p class="mb-0"><?php echo e($feedback->overall_experience ?: $feedback->message); ?></p>
                        </div>

                        <?php if($feedback->improvement_suggestion): ?>
                        <h6 class="mb-3" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;">
                            <?php echo e(__('portal.feedback_track.suggested_improvements')); ?>

                        </h6>
                        <div class="p-3 rounded-3 mb-4" style="background-color: #f8f9fa;">
                            <p class="mb-0"><?php echo e($feedback->improvement_suggestion); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if($feedback->confidentiality_comment): ?>
                        <h6 class="mb-3" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;">
                            <?php echo e(__('portal.feedback_track.confidentiality_explanation')); ?>

                        </h6>
                        <div class="p-3 rounded-3 mb-4" style="background-color: #fff8e1;">
                            <p class="mb-0"><?php echo e($feedback->confidentiality_comment); ?></p>
                        </div>
                        <?php endif; ?>

                        <h6 class="mb-3" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;">
                            <?php echo e(__('portal.feedback_track.additional_comments')); ?>

                        </h6>
                        <div class="p-3 rounded-3 mb-4" style="background-color: #f8f9fa;">
                            <p class="mb-0"><?php echo e($feedback->message ?: __('portal.feedback_track.no_additional_comments')); ?></p>
                        </div>

                        <!-- Public Response (if available) -->
                        <?php if($publicResponse): ?>
                        <h6 class="mb-3" style="color: var(--ccbrt-teal); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;">
                            <i class="bi bi-reply me-2"></i><?php echo e(__('portal.feedback_track.response_title')); ?>

                        </h6>
                        <div class="p-3 rounded-3 mb-4" style="background-color: #e8f5e9; border-left: 4px solid var(--ccbrt-teal);">
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-person-circle me-1"></i><?php echo e(__('portal.common.quality_assurance_team')); ?>

                                </small>
                                <small class="text-muted"><?php echo e($publicResponse->created_at->format('F j, Y')); ?></small>
                            </div>
                            <p class="mb-0"><?php echo e($publicResponse->content); ?></p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <?php echo e(__('portal.feedback_track.no_response')); ?>

                        </div>
                        <?php endif; ?>

                        <?php if($feedback->is_urgent): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong><?php echo e(__('portal.feedback_create.fields.urgent')); ?>.</strong> <?php echo e(__('portal.feedback_track.urgent_alert')); ?>

                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="text-center mt-4">
                    <a href="<?php echo e(route('feedback.track')); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i><?php echo e(__('portal.feedback_track.track_another')); ?>

                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/feedback/track.blade.php ENDPATH**/ ?>