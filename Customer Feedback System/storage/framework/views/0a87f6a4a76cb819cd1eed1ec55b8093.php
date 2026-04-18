<?php $__env->startSection('title', __('portal.meta.feedback_confirmation_title')); ?>

<?php $__env->startSection('content'); ?>
<!-- Page Header -->
<section class="hero-section" style="padding: 3rem 0;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="hero-title" style="font-size: 2rem;">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo e(__('portal.feedback_confirmation.hero_title')); ?>

                </h1>
                <p class="hero-subtitle mb-0">
                    <?php echo e(__('portal.feedback_confirmation.hero_subtitle')); ?>

                </p>
            </div>
        </div>
    </div>
</section>

<!-- Confirmation Content -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Success Alert -->
                <div class="alert alert-ccbrt-success mb-4">
                    <h5 class="alert-heading">
                        <i class="bi bi-check-circle me-2"></i><?php echo e(__('portal.feedback_confirmation.success_title')); ?>

                    </h5>
                    <p class="mb-0">
                        <?php echo e(__('portal.feedback_confirmation.success_message')); ?>

                        <?php if($feedback->email): ?>
                            <?php echo e(__('portal.feedback_confirmation.success_email', ['email' => $feedback->email])); ?>

                        <?php endif; ?>
                    </p>
                </div>

                <!-- Reference Number Box -->
                <div class="reference-box">
                    <p class="mb-2 opacity-75"><?php echo e(__('portal.feedback_confirmation.reference_title')); ?></p>
                    <div class="reference-number"><?php echo e($feedback->reference_no); ?></div>
                    <p class="mt-3 mb-0 opacity-75">
                        <?php echo e(__('portal.feedback_confirmation.reference_help')); ?>

                    </p>
                </div>

                <div class="card card-ccbrt mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i><?php echo e(__('portal.feedback_confirmation.summary_title')); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block"><?php echo e(__('portal.feedback_track.feedback_type')); ?></small>
                                <span class="fw-medium"><?php echo e($feedback->getFeedbackTypeLabel()); ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block"><?php echo e(__('portal.feedback_track.service_rating')); ?></small>
                                <span class="fw-medium"><?php echo e($feedback->service_rating ? $feedback->getServiceRatingLabel() : __('portal.common.not_specified')); ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block"><?php echo e(__('portal.feedback_track.service_category')); ?></small>
                                <span class="fw-medium"><?php echo e($feedback->getServiceCategoryLabel()); ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block"><?php echo e(__('portal.feedback_track.service_offered')); ?></small>
                                <span class="fw-medium"><?php echo e($feedback->service_units_summary ?: __('portal.common.not_specified')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps Card -->
                <div class="card card-ccbrt mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i><?php echo e(__('portal.feedback_confirmation.next_steps_title')); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-inbox fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo e(__('portal.feedback_confirmation.review_title')); ?></h6>
                                        <p class="text-muted small mb-0"><?php echo e(__('portal.feedback_confirmation.review_description')); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-search fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo e(__('portal.feedback_confirmation.investigation_title')); ?></h6>
                                        <p class="text-muted small mb-0"><?php echo e(__('portal.feedback_confirmation.investigation_description')); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-chat-dots fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo e(__('portal.feedback_confirmation.response_title')); ?></h6>
                                        <p class="text-muted small mb-0">
                                            <?php if($feedback->email): ?>
                                                <?php echo e(__('portal.feedback_confirmation.response_email')); ?>

                                            <?php else: ?>
                                                <?php echo e(__('portal.feedback_confirmation.response_track')); ?>

                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-check2-all fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo e(__('portal.feedback_confirmation.resolution_title')); ?></h6>
                                        <p class="text-muted small mb-0"><?php echo e(__('portal.feedback_confirmation.resolution_description')); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="<?php echo e(route('feedback.track')); ?>?reference_no=<?php echo e($feedback->reference_no); ?>" class="btn btn-outline-primary w-100 py-3">
                            <i class="bi bi-search me-2"></i><?php echo e(__('portal.feedback_confirmation.track_button')); ?>

                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo e(route('feedback.create')); ?>" class="btn btn-ccbrt-primary w-100 py-3">
                            <i class="bi bi-plus-circle me-2"></i><?php echo e(__('portal.feedback_confirmation.submit_another')); ?>

                        </a>
                    </div>
                </div>

                <!-- Back to Home -->
                <div class="text-center mt-4">
                    <a href="<?php echo e(url('/')); ?>" class="text-decoration-none" style="color: var(--ccbrt-navy);">
                        <i class="bi bi-arrow-left me-1"></i><?php echo e(__('portal.feedback_confirmation.back_home')); ?>

                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/feedback/confirmation.blade.php ENDPATH**/ ?>