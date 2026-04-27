<?php $__env->startSection('title', __('portal.meta.home_title')); ?>

<?php $__env->startSection('content'); ?>
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="hero-title"><?php echo e(__('portal.home.hero_title')); ?></h1>
                <p class="hero-subtitle">
                    <?php echo e(__('portal.home.hero_subtitle')); ?>

                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?php echo e(route('feedback.create')); ?>" class="btn btn-ccbrt-primary btn-lg">
                        <i class="bi bi-chat-square-text me-2"></i><?php echo e(__('portal.home.primary_cta')); ?>

                    </a>
                    <a href="<?php echo e(route('feedback.track')); ?>" class="btn btn-ccbrt-outline btn-lg">
                        <i class="bi bi-search me-2"></i><?php echo e(__('portal.home.secondary_cta')); ?>

                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block">
                <div class="text-center">
                    <i class="bi bi-heart-pulse" style="font-size: 10rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-3" style="color: var(--ccbrt-navy);"><?php echo e(__('portal.home.process_title')); ?></h2>
            <p class="text-muted"><?php echo e(__('portal.home.process_subtitle')); ?></p>
        </div>
        
        <div class="process-steps">
            <div class="step">
                <div class="step-number">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div class="step-title"><?php echo e(__('portal.home.process_steps.submit.title')); ?></div>
                <div class="step-description"><?php echo e(__('portal.home.process_steps.submit.description')); ?></div>
            </div>
            <div class="step">
                <div class="step-number">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="step-title"><?php echo e(__('portal.home.process_steps.receive.title')); ?></div>
                <div class="step-description"><?php echo e(__('portal.home.process_steps.receive.description')); ?></div>
            </div>
            <div class="step">
                <div class="step-number">
                    <i class="bi bi-people"></i>
                </div>
                <div class="step-title"><?php echo e(__('portal.home.process_steps.review.title')); ?></div>
                <div class="step-description"><?php echo e(__('portal.home.process_steps.review.description')); ?></div>
            </div>
            <div class="step">
                <div class="step-number">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div class="step-title"><?php echo e(__('portal.home.process_steps.respond.title')); ?></div>
                <div class="step-description"><?php echo e(__('portal.home.process_steps.respond.description')); ?></div>
            </div>
        </div>
    </div>
</section>

<!-- Service Categories -->
<section class="py-5" style="background-color: white;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="mb-3" style="color: var(--ccbrt-navy);"><?php echo e(__('portal.home.services_title')); ?></h2>
            <p class="text-muted"><?php echo e(__('portal.home.services_subtitle')); ?></p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-hospital fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.outpatient')); ?></h6>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-bed fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.inpatient')); ?></h6>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-eye fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.eye_surgery')); ?></h6>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-activity fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.rehabilitation')); ?></h6>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-capsule fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.pharmacy')); ?></h6>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-reception-4 fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.reception_admin')); ?></h6>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-cash-coin fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.billing')); ?></h6>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                    <i class="bi bi-grid-3x3-gap fs-1 mb-3" style="color: var(--ccbrt-navy);"></i>
                    <h6 class="mb-0"><?php echo e(__('portal.options.service_categories.other')); ?></h6>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5" style="background: linear-gradient(135deg, var(--ccbrt-teal) 0%, var(--ccbrt-teal-light) 100%);">
    <div class="container text-center">
        <h2 class="text-white mb-3"><?php echo e(__('portal.home.cta_title')); ?></h2>
        <p class="text-white mb-4 opacity-90">
            <?php echo e(__('portal.home.cta_subtitle')); ?>

        </p>
        <a href="<?php echo e(route('feedback.create')); ?>" class="btn btn-light btn-lg px-5">
            <i class="bi bi-send me-2"></i><?php echo e(__('portal.home.cta_button')); ?>

        </a>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/home.blade.php ENDPATH**/ ?>