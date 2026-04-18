<?php $__env->startSection('title', 'Add Manual Feedback'); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">Add Manual Feedback</h4>
                <p class="text-muted mb-0 small mt-1">Record verbal or paper feedback from clients directly into the system.</p>
            </div>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('feedback.admin.index')); ?>">Feedback</a></li>
                    <li class="breadcrumb-item active">Add Manual</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-pencil-square me-2"></i>Feedback Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('feedback.manual.store')); ?>" class="needs-validation">
                    <?php echo csrf_field(); ?>

                    
                    <div class="section-divider">Patient Information</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="patient_name" class="form-label small fw-semibold">Patient Name <span class="text-danger">*</span></label>
                            <input type="text" id="patient_name" name="patient_name" value="<?php echo e(old('patient_name')); ?>"
                                   class="form-control <?php $__errorArgs = ['patient_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                            <?php $__errorArgs = ['patient_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
                                   class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo e(old('phone')); ?>"
                                   class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="visit_date" class="form-label small fw-semibold">Visit Date</label>
                            <input type="date" id="visit_date" name="visit_date" value="<?php echo e(old('visit_date')); ?>"
                                   class="form-control <?php $__errorArgs = ['visit_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['visit_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="section-divider">Service Information</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="service_category" class="form-label small fw-semibold">Service Category <span class="text-danger">*</span></label>
                            <select id="service_category" name="service_category" class="form-select tom-select-single <?php $__errorArgs = ['service_category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="">-- Select Category --</option>
                                <?php $__currentLoopData = \App\Models\Feedback::SERVICE_CATEGORIES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php echo e(old('service_category') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['service_category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="feedback_type" class="form-label small fw-semibold">Feedback Type <span class="text-danger">*</span></label>
                            <select id="feedback_type" name="feedback_type" class="form-select tom-select-single <?php $__errorArgs = ['feedback_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="">-- Select Type --</option>
                                <?php $__currentLoopData = \App\Models\Feedback::FEEDBACK_TYPES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php echo e(old('feedback_type') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['feedback_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="service_rating" class="form-label small fw-semibold">Service Rating <span class="text-danger">*</span></label>
                            <select id="service_rating" name="service_rating" class="form-select tom-select-single <?php $__errorArgs = ['service_rating'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="">-- Select Rating --</option>
                                <?php $__currentLoopData = \App\Models\Feedback::SERVICE_RATINGS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php echo e(old('service_rating') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['service_rating'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="service_units" class="form-label small fw-semibold">Service Units</label>
                            <select id="service_units" name="service_units[]" class="form-select tom-select-multi <?php $__errorArgs = ['service_units'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" multiple>
                                <?php $__currentLoopData = \App\Models\Feedback::SERVICE_UNITS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php echo e(in_array($value, old('service_units', [])) ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['service_units'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small mt-1"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="section-divider">Feedback Content</div>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="overall_experience" class="form-label small fw-semibold">Overall Experience <span class="text-danger">*</span></label>
                            <textarea id="overall_experience" name="overall_experience" rows="4" class="form-control <?php $__errorArgs = ['overall_experience'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      placeholder="Describe the patient's overall experience..." required><?php echo e(old('overall_experience')); ?></textarea>
                            <small class="text-muted">Minimum 10 characters required.</small>
                            <?php $__errorArgs = ['overall_experience'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-12">
                            <label for="improvement_suggestion" class="form-label small fw-semibold">Suggested Improvement</label>
                            <textarea id="improvement_suggestion" name="improvement_suggestion" rows="3" class="form-control <?php $__errorArgs = ['improvement_suggestion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      placeholder="Any suggestions for improvement..."><?php echo e(old('improvement_suggestion')); ?></textarea>
                            <?php $__errorArgs = ['improvement_suggestion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label small fw-semibold">Additional Comments</label>
                            <textarea id="message" name="message" rows="3" class="form-control <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      placeholder="Any additional comments..."><?php echo e(old('message')); ?></textarea>
                            <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="section-divider">Confidentiality</div>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" id="confidentiality_respected" name="confidentiality_respected" value="1"
                                       class="form-check-input" <?php echo e(old('confidentiality_respected') ? 'checked' : ''); ?>>
                                <label class="form-check-label small" for="confidentiality_respected">
                                    Confidentiality was respected
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="confidentiality_comment" class="form-label small fw-semibold">Confidentiality Comments</label>
                            <textarea id="confidentiality_comment" name="confidentiality_comment" rows="2" class="form-control <?php $__errorArgs = ['confidentiality_comment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      placeholder="If confidentiality was not respected, explain..."><?php echo e(old('confidentiality_comment')); ?></textarea>
                            <?php $__errorArgs = ['confidentiality_comment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="section-divider">Options</div>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" id="is_urgent" name="is_urgent" value="1"
                                       class="form-check-input" <?php echo e(old('is_urgent') ? 'checked' : ''); ?>>
                                <label class="form-check-label small" for="is_urgent">
                                    Mark as urgent/high priority
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" id="consent_given" name="consent_given" value="1"
                                       class="form-check-input <?php $__errorArgs = ['consent_given'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required <?php echo e(old('consent_given') ? 'checked' : ''); ?>>
                                <label class="form-check-label small" for="consent_given">
                                    Patient has given consent to record and process this feedback <span class="text-danger">*</span>
                                </label>
                            </div>
                            <?php $__errorArgs = ['consent_given'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Manual Feedback
                        </button>
                        <a href="<?php echo e(route('feedback.admin.index')); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light-info border-0">
            <div class="card-body">
                <h6 class="card-title fw-semibold mb-3"><i class="bi bi-info-circle me-2"></i>Manual Entry Info</h6>
                <p class="small mb-2">Use this form to record feedback from:</p>
                <ul class="small mb-3 ps-3">
                    <li>Verbal feedback from patients</li>
                    <li>Paper form submissions</li>
                    <li>Phone feedback</li>
                    <li>In-person interviews</li>
                </ul>
                <p class="small mb-0 text-muted">This feedback will be marked as "Manual / Paper Form" in the system and attributed to you as the entry person.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .section-divider {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--ccbrt-brand-800);
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--ccbrt-brand-500);
    }

    .tom-select-single,
    .tom-select-multi {
        padding: 0 !important;
    }

    .ts-control {
        border-color: #dee2e6 !important;
        border-radius: 0.375rem !important;
        min-height: 38px !important;
        padding: 0.375rem 0.75rem !important;
        background: #ffffff !important;
    }

    .ts-control:focus-within {
        border-color: var(--ccbrt-brand-500) !important;
        box-shadow: 0 0 0 0.2rem rgba(148, 200, 61, 0.18) !important;
    }

    .ts-control.is-invalid {
        border-color: #dc3545 !important;
    }

    .ts-dropdown {
        border-color: #dee2e6 !important;
        box-shadow: 0 8px 24px rgba(6, 83, 33, 0.05) !important;
    }

    .ts-dropdown-content .option {
        padding: 0.5rem 0.75rem !important;
        color: #163223 !important;
    }

    .ts-dropdown-content .option.selected {
        background-color: rgba(148, 200, 61, 0.18) !important;
        color: var(--ccbrt-brand-800) !important;
    }

    .ts-dropdown-content .option:hover {
        background-color: rgba(148, 200, 61, 0.12) !important;
        color: var(--ccbrt-brand-800) !important;
    }

    .ts-dropdown-content .option.highlighted {
        background-color: var(--ccbrt-brand-500) !important;
        color: #ffffff !important;
    }

    .ts-control .item {
        background-color: var(--ccbrt-brand-800) !important;
        color: #ffffff !important;
        border-color: var(--ccbrt-brand-800) !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 0.25rem !important;
    }

    .ts-control .item.selected {
        background-color: var(--ccbrt-brand-700) !important;
    }

    .ts-control input::placeholder {
        color: #6c757d !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize single-select dropdowns
        document.querySelectorAll('.tom-select-single').forEach(function (el) {
            new TomSelect(el, {
                create: false,
                placeholder: el.getAttribute('placeholder') || '-- Select an option --',
                maxItems: 1,
                allowEmptyOption: true,
            });
        });

        // Initialize multi-select dropdowns
        document.querySelectorAll('.tom-select-multi').forEach(function (el) {
            new TomSelect(el, {
                create: false,
                placeholder: el.getAttribute('placeholder') || '-- Select options --',
                plugins: {
                    'remove_button': {
                        title: 'Remove this item',
                    }
                },
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/feedback/manual/create.blade.php ENDPATH**/ ?>