<?php $__env->startSection('title', __('portal.meta.feedback_create_title')); ?>

<?php $__env->startSection('content'); ?>
<!-- Page Header -->
<section class="hero-section" style="padding: 3rem 0;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="hero-title" style="font-size: 2rem;"><?php echo e(__('portal.feedback_create.hero_title')); ?></h1>
                <p class="hero-subtitle mb-0">
                    <?php echo e(__('portal.feedback_create.hero_subtitle')); ?>

                </p>
            </div>
        </div>
    </div>
</section>

<!-- Feedback Form -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Info Box -->
                <div class="info-box">
                    <h6 class="mb-2" style="color: var(--ccbrt-navy);">
                        <i class="bi bi-info-circle me-2"></i><?php echo e(__('portal.feedback_create.info_title')); ?>

                    </h6>
                    <ul class="mb-0 ps-3">
                        <li><?php echo e(__('portal.feedback_create.info_items.required_fields')); ?></li>
                        <li><?php echo e(__('portal.feedback_create.info_items.anonymous')); ?></li>
                        <li><?php echo e(__('portal.feedback_create.info_items.response')); ?></li>
                        <li><?php echo e(__('portal.feedback_create.info_items.review')); ?></li>
                    </ul>
                </div>

                <!-- Form Card -->
                <div class="card card-ccbrt">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="bi bi-chat-square-text me-2"></i><?php echo e(__('portal.feedback_create.form_title')); ?></h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo e(route('feedback.store')); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>

                            <?php if($errors->any()): ?>
                                <div class="alert alert-danger alert-ccbrt mb-4">
                                    <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i><?php echo e(__('portal.feedback_create.errors_title')); ?></h6>
                                    <ul class="mb-0 mt-2">
                                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><?php echo e($error); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Contact Information -->
                            <h5 class="mb-3" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;">
                                <?php echo e(__('portal.feedback_create.sections.contact_information')); ?> <small class="text-muted fw-normal">(<?php echo e(__('portal.common.optional')); ?>)</small>
                            </h5>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="patient_name" class="form-label"><?php echo e(__('portal.feedback_create.fields.full_name')); ?></label>
                                    <input type="text" class="form-control form-control-ccbrt <?php $__errorArgs = ['patient_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="patient_name" name="patient_name" value="<?php echo e(old('patient_name')); ?>"
                                           placeholder="<?php echo e(__('portal.feedback_create.fields.full_name_placeholder')); ?>">
                                    <?php $__errorArgs = ['patient_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php echo e(__('portal.feedback_create.fields.email')); ?></label>
                                    <input type="email" class="form-control form-control-ccbrt <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="email" name="email" value="<?php echo e(old('email')); ?>"
                                           placeholder="<?php echo e(__('portal.feedback_create.fields.email_placeholder')); ?>">
                                    <div class="form-text text-muted"><?php echo e(__('portal.feedback_create.fields.email_help')); ?></div>
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label"><?php echo e(__('portal.feedback_create.fields.phone')); ?></label>
                                    <input type="tel" class="form-control form-control-ccbrt <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="phone" name="phone" value="<?php echo e(old('phone')); ?>"
                                           placeholder="<?php echo e(__('portal.feedback_create.fields.phone_placeholder')); ?>">
                                    <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="visit_date" class="form-label"><?php echo e(__('portal.feedback_create.fields.visit_date')); ?></label>
                                    <input type="date" class="form-control form-control-ccbrt <?php $__errorArgs = ['visit_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           id="visit_date" name="visit_date" value="<?php echo e(old('visit_date')); ?>">
                                    <?php $__errorArgs = ['visit_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <?php
                                $serviceUnits = \App\Models\Feedback::SERVICE_UNITS;
                                $serviceRatings = \App\Models\Feedback::SERVICE_RATINGS;
                            ?>

                            <h5 class="mb-3 mt-4" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;"><?php echo e(__('portal.feedback_create.sections.customer_experience')); ?></h5>

                            <div class="mb-4">
                                <label class="form-label fw-semibold"><?php echo e(__('portal.feedback_create.questions.service_offered')); ?></label>
                                <div class="row row-cols-1 row-cols-md-2 g-2 mt-1">
                                    <?php $__currentLoopData = $serviceUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="service_units[]" id="service_unit_<?php echo e($value); ?>"
                                                       value="<?php echo e($value); ?>" <?php echo e(in_array($value, old('service_units', [])) ? 'checked' : ''); ?>>
                                                <label class="form-check-label" for="service_unit_<?php echo e($value); ?>"><?php echo e(__('portal.options.service_units.' . $value)); ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <?php $__errorArgs = ['service_units'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <?php $__errorArgs = ['service_units.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="mb-4">
                                <label class="form-label required fw-semibold"><?php echo e(__('portal.feedback_create.questions.service_rating')); ?></label>
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    <?php $__currentLoopData = $serviceRatings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="service_rating" id="service_rating_<?php echo e($value); ?>"
                                                   value="<?php echo e($value); ?>" <?php echo e(old('service_rating') == $value ? 'checked' : ''); ?> required>
                                            <label class="form-check-label" for="service_rating_<?php echo e($value); ?>"><?php echo e(__('portal.options.service_ratings.' . $value)); ?></label>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <?php $__errorArgs = ['service_rating'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="row mb-4">
                                <div class="col-lg-6 mb-3">
                                    <label class="form-label fw-semibold"><?php echo e(__('portal.feedback_create.questions.confidentiality')); ?></label>
                                    <div class="d-flex gap-4 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="confidentiality_respected" id="confidentiality_yes"
                                                   value="1" <?php echo e(old('confidentiality_respected') === '1' ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="confidentiality_yes"><?php echo e(__('portal.common.yes')); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="confidentiality_respected" id="confidentiality_no"
                                                   value="0" <?php echo e(old('confidentiality_respected') === '0' ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="confidentiality_no"><?php echo e(__('portal.common.no')); ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label for="confidentiality_comment" class="form-label"><?php echo e(__('portal.feedback_create.fields.confidentiality_comment')); ?></label>
                                    <textarea class="form-control form-control-ccbrt <?php $__errorArgs = ['confidentiality_comment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                              id="confidentiality_comment" name="confidentiality_comment" rows="3"
                                              placeholder="<?php echo e(__('portal.feedback_create.fields.confidentiality_comment_placeholder')); ?>"><?php echo e(old('confidentiality_comment')); ?></textarea>
                                    <?php $__errorArgs = ['confidentiality_comment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="overall_experience" class="form-label required fw-semibold"><?php echo e(__('portal.feedback_create.fields.overall_experience')); ?></label>
                                <textarea class="form-control form-control-ccbrt <?php $__errorArgs = ['overall_experience'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                          id="overall_experience" name="overall_experience" rows="4" required
                                          placeholder="<?php echo e(__('portal.feedback_create.fields.overall_experience_placeholder')); ?>"><?php echo e(old('overall_experience')); ?></textarea>
                                <div id="overallExperienceHelp" class="form-text text-muted" data-template="<?php echo e(__('portal.common.character_count', ['count' => '__COUNT__', 'min' => 10])); ?>">
                                    <span id="overallExperienceCount">0</span> <?php echo e(__('portal.common.character_count', ['count' => '', 'min' => 10])); ?>

                                </div>
                                <?php $__errorArgs = ['overall_experience'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="mb-4">
                                <label for="improvement_suggestion" class="form-label fw-semibold"><?php echo e(__('portal.feedback_create.fields.improvement_suggestion')); ?></label>
                                <textarea class="form-control form-control-ccbrt <?php $__errorArgs = ['improvement_suggestion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                          id="improvement_suggestion" name="improvement_suggestion" rows="4"
                                          placeholder="<?php echo e(__('portal.feedback_create.fields.improvement_suggestion_placeholder')); ?>"><?php echo e(old('improvement_suggestion')); ?></textarea>
                                <?php $__errorArgs = ['improvement_suggestion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <h5 class="mb-3 mt-4" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;"><?php echo e(__('portal.feedback_create.sections.additional_details')); ?></h5>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required"><?php echo e(__('portal.feedback_create.fields.feedback_type')); ?></label>
                                    <div class="d-flex flex-wrap gap-3 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="feedback_type" id="type_compliment"
                                                   value="compliment" <?php echo e(old('feedback_type') == 'compliment' ? 'checked' : ''); ?> required>
                                            <label class="form-check-label" for="type_compliment"><?php echo e(__('portal.options.feedback_types.compliment')); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="feedback_type" id="type_complaint"
                                                   value="complaint" <?php echo e(old('feedback_type') == 'complaint' ? 'checked' : ''); ?> required>
                                            <label class="form-check-label" for="type_complaint"><?php echo e(__('portal.options.feedback_types.complaint')); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="feedback_type" id="type_suggestion"
                                                   value="suggestion" <?php echo e(old('feedback_type') == 'suggestion' ? 'checked' : ''); ?> required>
                                            <label class="form-check-label" for="type_suggestion"><?php echo e(__('portal.options.feedback_types.suggestion')); ?></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="feedback_type" id="type_enquiry"
                                                   value="enquiry" <?php echo e(old('feedback_type') == 'enquiry' ? 'checked' : ''); ?> required>
                                            <label class="form-check-label" for="type_enquiry"><?php echo e(__('portal.options.feedback_types.enquiry')); ?></label>
                                        </div>
                                    </div>
                                    <?php $__errorArgs = ['feedback_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="message" class="form-label"><?php echo e(__('portal.feedback_create.fields.message')); ?></label>
                                    <textarea class="form-control form-control-ccbrt <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                              id="message" name="message" rows="4"
                                              placeholder="<?php echo e(__('portal.feedback_create.fields.message_placeholder')); ?>"><?php echo e(old('message')); ?></textarea>
                                    <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="attachment" class="form-label"><?php echo e(__('portal.feedback_create.fields.attachment')); ?></label>
                                <input type="file" class="form-control form-control-ccbrt <?php $__errorArgs = ['attachment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="form-text text-muted">
                                    <?php echo e(__('portal.feedback_create.fields.attachment_help')); ?>

                                </div>
                                <?php $__errorArgs = ['attachment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <h5 class="mb-3 mt-4" style="color: var(--ccbrt-navy); border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem;"><?php echo e(__('portal.feedback_create.sections.additional_options')); ?></h5>

                            <div class="mb-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_urgent" name="is_urgent" value="1" <?php echo e(old('is_urgent') ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="is_urgent">
                                        <strong><?php echo e(__('portal.feedback_create.fields.urgent')); ?></strong> - <?php echo e(__('portal.feedback_create.fields.urgent_help')); ?>

                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input <?php $__errorArgs = ['consent_given'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" type="checkbox" 
                                           id="consent_given" name="consent_given" value="1" <?php echo e(old('consent_given') ? 'checked' : ''); ?> required>
                                    <label class="form-check-label" for="consent_given">
                                        <?php echo e(__('portal.feedback_create.fields.consent')); ?> 
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                                <?php $__errorArgs = ['consent_given'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-5">
                                <a href="<?php echo e(url('/')); ?>" class="btn btn-outline-secondary btn-lg px-4"><?php echo e(__('portal.common.cancel')); ?></a>
                                <button type="submit" class="btn btn-ccbrt-primary btn-lg px-5">
                                    <i class="bi bi-send me-2"></i><?php echo e(__('portal.feedback_create.submit_button')); ?>

                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const overallExperienceField = document.getElementById('overall_experience');
    const overallExperienceCount = document.getElementById('overallExperienceCount');
    const overallExperienceHelp = document.getElementById('overallExperienceHelp');

    const syncOverallExperienceHelp = function(length) {
        if (!overallExperienceCount || !overallExperienceHelp) {
            return;
        }

        overallExperienceCount.textContent = length;
        overallExperienceHelp.innerHTML = overallExperienceHelp.dataset.template.replace('__COUNT__', String(length)).replace('__COUNT__', String(length));
        overallExperienceHelp.prepend(overallExperienceCount);
        overallExperienceCount.insertAdjacentText('afterend', ' ');
        overallExperienceCount.style.color = length >= 10 ? 'var(--ccbrt-teal)' : '#dc3545';
    };

    if (overallExperienceField && overallExperienceCount && overallExperienceHelp) {
        overallExperienceField.addEventListener('input', function() {
            syncOverallExperienceHelp(this.value.length);
        });

        syncOverallExperienceHelp(overallExperienceField.value.length);
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.public', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/Customer Feedback System/resources/views/feedback/create.blade.php ENDPATH**/ ?>