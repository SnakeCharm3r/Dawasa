<?php if (isset($component)) { $__componentOriginal69dc84650370d1d4dc1b42d016d7226b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b = $attributes; } ?>
<?php $component = App\View\Components\GuestLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('guest-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\GuestLayout::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<div class="auth-card">
    <div class="auth-header">
        <div class="auth-brand">
            <img src="<?php echo e(asset('assets/images/ccbrt-logo.svg')); ?>" alt="CCBRT Logo" class="auth-brand-logo">
            <div class="auth-brand-copy">
                <div class="logo-text">CCBRT</div>
                <div class="logo-sub">Feedback Management System</div>
            </div>
        </div>
    </div>
    <div class="auth-body text-center">
        <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                 style="width:72px;height:72px;background:#eef7e8;color:#0b6b2c;">
                <i class="bi bi-person-check fs-1"></i>
            </div>
            <h5 class="fw-bold mb-2" style="color:#065321;">Registration submitted</h5>
            <p class="text-muted small mb-0">
                <?php echo e(session('status', 'Your account request has been received and is awaiting administrator approval.')); ?>

            </p>
        </div>

        <div class="alert alert-warning text-start py-3 px-3 small mb-4">
            <i class="bi bi-clock-history me-2"></i>
            New staff accounts stay <strong>pending approval</strong> until an administrator reviews the registration and assigns access.
        </div>

        <div class="d-grid gap-2">
            <a href="<?php echo e(route('login')); ?>" class="btn btn-auth">
                <i class="bi bi-box-arrow-in-right me-2"></i>Go to Sign In
            </a>
            <a href="<?php echo e(route('home')); ?>" class="btn btn-light border">
                <i class="bi bi-house-door me-2"></i>Return to Home
            </a>
        </div>
    </div>
</div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $attributes = $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $component = $__componentOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?>
<?php /**PATH /var/www/Customer Feedback System/resources/views/auth/register-complete.blade.php ENDPATH**/ ?>