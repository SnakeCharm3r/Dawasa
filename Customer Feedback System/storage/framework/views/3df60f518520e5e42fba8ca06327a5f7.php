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
 <div class="auth-card auth-card-wide">
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

         <?php if($isFirstUser): ?>
             <div class="alert py-2 px-3 small mb-4" style="background:#ede9fe;color:#5b21b6;border:1px solid #c4b5fd;">
                <i class="bi bi-shield-check me-2"></i>
                <strong>First-time setup:</strong> You are creating the system administrator account. This account will have full access.
            </div>
        <?php else: ?>
            <div class="alert alert-warning py-2 px-3 small mb-4">
                <i class="bi bi-clock-history me-2"></i>
                Your account will be <strong>pending approval</strong> until an administrator reviews and activates it.
            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('register')); ?>">
            <?php echo csrf_field(); ?>

            
            <div class="section-divider">Personal Information</div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="fname" class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                    <input id="fname" type="text" name="fname" value="<?php echo e(old('fname')); ?>"
                           class="form-control <?php $__errorArgs = ['fname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           required autofocus autocomplete="given-name" placeholder="First name">
                    <?php $__errorArgs = ['fname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label for="mname" class="form-label small fw-semibold">Middle Name <span class="text-muted">(optional)</span></label>
                    <input id="mname" type="text" name="mname" value="<?php echo e(old('mname')); ?>"
                           class="form-control <?php $__errorArgs = ['mname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           autocomplete="additional-name" placeholder="Middle name">
                    <?php $__errorArgs = ['mname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label for="lname" class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                    <input id="lname" type="text" name="lname" value="<?php echo e(old('lname')); ?>"
                           class="form-control <?php $__errorArgs = ['lname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           required autocomplete="family-name" placeholder="Last name">
                    <?php $__errorArgs = ['lname'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4">
                    <label for="dob" class="form-label small fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                    <input id="dob" type="date" name="dob" value="<?php echo e(old('dob')); ?>"
                           class="form-control <?php $__errorArgs = ['dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <?php $__errorArgs = ['dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            
            <div class="section-divider">Account Information</div>
            <div class="row g-3 mb-3">
                <div class="col-md-<?php echo e($isFirstUser ? '12' : '6'); ?>">
                    <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>"
                           class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           required autocomplete="username" placeholder="you@example.com">
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <?php if(!$isFirstUser): ?>
                <div class="col-md-6">
                    <label for="role" class="form-label small fw-semibold">Requested Role <span class="text-danger">*</span></label>
                     <select id="role" name="role" class="form-select <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                         <option value="">-- Select your role --</option>
                         <option value="qa_officer"  <?php echo e(old('role')=='qa_officer'  ?'selected':''); ?>>QA Officer</option>
                         <option value="call_center" <?php echo e(old('role')=='call_center' ?'selected':''); ?>>Call Center</option>
                         <option value="qa_hod"      <?php echo e(old('role')=='qa_hod'      ?'selected':''); ?>>QA Head of Department</option>
                         <option value="coo"         <?php echo e(old('role')=='coo'         ?'selected':''); ?>>Chief Operating Officer</option>
                        <option value="line_manager" <?php echo e(old('role')=='line_manager' ?'selected':''); ?>>Line Manager</option>
                     </select>
                     <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                     <div class="form-text text-muted small">Role may be adjusted by admin during approval.</div>
                 </div>
                <?php endif; ?>
            </div>

            
            <div class="section-divider">Security</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="password" class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
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
                               required autocomplete="new-password" placeholder="Min. 8 characters">
                        <button type="button"
                                class="btn btn-sm position-absolute top-50 translate-middle-y end-0 me-2 p-0 border-0 bg-transparent text-muted"
                                onclick="var p=document.getElementById('password'); p.type=p.type==='password'?'text':'password';">
                            <i class="bi bi-eye fs-5"></i>
                        </button>
                    </div>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           class="form-control" required autocomplete="new-password" placeholder="Repeat password">
                </div>
            </div>

            <button type="submit" class="btn btn-auth">
                <?php if($isFirstUser): ?>
                    <i class="bi bi-shield-check me-2"></i>Create Administrator Account
                <?php else: ?>
                    <i class="bi bi-send me-2"></i>Submit Registration for Approval
                <?php endif; ?>
            </button>
        </form>

        <div class="text-center mt-4 small text-muted">
            Already have an account?
            <a href="<?php echo e(route('login')); ?>" class="text-decoration-none fw-semibold" style="color:#0b6b2c;">Sign in here</a>
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
<?php /**PATH /var/www/Customer Feedback System/resources/views/auth/register.blade.php ENDPATH**/ ?>