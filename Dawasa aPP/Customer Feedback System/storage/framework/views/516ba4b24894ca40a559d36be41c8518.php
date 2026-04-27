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
     <div class="auth-body">
         <h5 class="fw-bold mb-1" style="color:#065321;">Welcome Back</h5>
         <p class="text-muted small mb-4">Sign in to your account to continue.</p>

        
        <?php if(session('status')): ?>
            <div class="alert alert-info alert-dismissible fade show py-2 px-3 small" role="alert">
                <i class="bi bi-info-circle me-2"></i><?php echo e(session('status')); ?>

                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        
        <?php if($errors->any()): ?>
            <div class="alert alert-danger py-2 px-3 small">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('login')); ?>">
            <?php echo csrf_field(); ?>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Email Address</label>
                <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>"
                       class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       required autofocus autocomplete="username"
                       placeholder="you@example.com">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Password</label>
                <div class="position-relative">
                    <input id="password" type="password" name="password"
                           class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           required autocomplete="current-password"
                           placeholder="Enter your password">
                    <button type="button"
                            class="btn btn-sm position-absolute top-50 translate-middle-y end-0 me-2 p-0 border-0 bg-transparent text-muted"
                            onclick="var p=document.getElementById('password'); p.type=p.type==='password'?'text':'password';">
                        <i class="bi bi-eye fs-5"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                    <label class="form-check-label small text-muted" for="remember_me">Remember me</label>
                </div>
                <?php if(Route::has('password.request')): ?>
                    <a href="<?php echo e(route('password.request')); ?>" class="small text-decoration-none" style="color:#0b6b2c;">
                        Forgot password?
                    </a>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-auth">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="text-center mt-4 small text-muted">
            Don't have an account?
            <a href="<?php echo e(route('register')); ?>" class="text-decoration-none fw-semibold" style="color:#0b6b2c;">Register here</a>
        </div>

        <div class="text-center mt-3">
            <a href="<?php echo e(route('home')); ?>" class="small text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Back to public portal
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
<?php /**PATH /var/www/Customer Feedback System/resources/views/auth/login.blade.php ENDPATH**/ ?>