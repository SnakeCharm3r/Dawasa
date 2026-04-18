<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo e(__('portal.meta.description')); ?>">
    <title><?php echo $__env->yieldContent('title', __('portal.meta.default_title')); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --ccbrt-navy: #065321;
            --ccbrt-navy-light: #0b6b2c;
            --ccbrt-teal: #15803d;
            --ccbrt-teal-light: #2b9348;
            --ccbrt-lime: #94c83d;
            --ccbrt-lime-light: #add95a;
            --ccbrt-white: #ffffff;
            --ccbrt-gray: #f4f8f1;
            --ccbrt-text: #1f2d1f;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: var(--ccbrt-gray);
            color: var(--ccbrt-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Navbar Styles */
        .navbar-ccbrt {
            background: linear-gradient(135deg, var(--ccbrt-navy) 0%, var(--ccbrt-navy-light) 100%);
            padding: 1rem 0;
            box-shadow: 0 10px 24px rgba(6,83,33,0.16);
            border-top: 6px solid var(--ccbrt-lime);
        }
        
        .navbar-ccbrt .navbar-brand {
            color: var(--ccbrt-white);
            font-weight: 600;
            font-size: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
        }

        .public-brand-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            flex-shrink: 0;
            background: rgba(255,255,255,0.12);
            border-radius: 50%;
            padding: 0.18rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
        }

        .public-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .public-brand-name {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .public-brand-subtitle {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            opacity: 0.86;
        }
        
        .navbar-ccbrt .navbar-brand:hover {
            color: var(--ccbrt-white);
        }
        
        .navbar-ccbrt .nav-link {
            color: rgba(255,255,255,0.9);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        
        .navbar-ccbrt .nav-link:hover,
        .navbar-ccbrt .nav-link.active {
            color: var(--ccbrt-white);
            background-color: rgba(148,200,61,0.18);
        }
        
        .language-switcher {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            margin-left: 1rem;
            padding: 0.35rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
        }

        .language-switcher-label {
            color: rgba(255,255,255,0.78);
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding-left: 0.35rem;
        }

        .language-switcher-options {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,0.08);
            border-radius: 999px;
            padding: 0.15rem;
        }

        .btn-language-toggle {
            border: none;
            border-radius: 999px;
            background: transparent;
            color: rgba(255,255,255,0.86);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            padding: 0.35rem 0.75rem;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .btn-check:checked + .btn-language-toggle {
            background: var(--ccbrt-lime);
            color: #163223;
            box-shadow: 0 4px 12px rgba(148,200,61,0.28);
        }

        .btn-language-toggle:hover {
            color: var(--ccbrt-white);
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--ccbrt-navy) 0%, var(--ccbrt-navy-light) 58%, #107531 100%);
            color: var(--ccbrt-white);
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
            border-top: 4px solid rgba(148,200,61,0.5);
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(148,200,61,0.28) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--ccbrt-lime-light) 0%, var(--ccbrt-lime) 50%, #6ba82d 100%);
            opacity: 0.95;
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .btn-ccbrt-primary {
            background: linear-gradient(135deg, var(--ccbrt-teal) 0%, var(--ccbrt-navy-light) 100%);
            border-color: var(--ccbrt-teal);
            color: var(--ccbrt-white);
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-ccbrt-primary:hover {
            background: linear-gradient(135deg, var(--ccbrt-navy-light) 0%, var(--ccbrt-teal) 100%);
            border-color: var(--ccbrt-navy-light);
            color: var(--ccbrt-white);
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(11,107,44,0.24);
        }
        
        .btn-ccbrt-outline {
            background-color: transparent;
            border: 2px solid var(--ccbrt-white);
            color: var(--ccbrt-white);
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-ccbrt-outline:hover {
            background-color: var(--ccbrt-lime);
            border-color: var(--ccbrt-lime);
            color: var(--ccbrt-brand-text, #163223);
        }
        
        /* Card Styles */
        .card-ccbrt {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 12px 30px rgba(6,83,33,0.08);
            background: var(--ccbrt-white);
        }
        
        .card-ccbrt .card-header {
            background: linear-gradient(135deg, var(--ccbrt-navy) 0%, var(--ccbrt-navy-light) 100%);
            color: var(--ccbrt-white);
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem;
            border: none;
        }
        
        .card-ccbrt .card-body {
            padding: 2rem;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 500;
            color: var(--ccbrt-navy);
            margin-bottom: 0.5rem;
        }
        
        .form-label .required::after {
            content: ' *';
            color: #dc3545;
        }
        
        .form-control-ccbrt {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control-ccbrt:focus {
            border-color: var(--ccbrt-lime);
            box-shadow: 0 0 0 0.2rem rgba(148,200,61,0.18);
        }
        
        .form-check-input:checked {
            background-color: var(--ccbrt-teal);
            border-color: var(--ccbrt-teal);
        }
        
        .form-check-input:focus {
            border-color: var(--ccbrt-lime);
            box-shadow: 0 0 0 0.2rem rgba(148,200,61,0.18);
        }
        
        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #eef7e8 0%, #f9fcf5 100%);
            border-left: 4px solid var(--ccbrt-lime);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        /* Steps */
        .process-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 3rem 0;
        }
        
        .process-steps::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: rgba(148,200,61,0.35);
            z-index: 0;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--ccbrt-navy) 0%, var(--ccbrt-navy-light) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }
        
        .step-title {
            font-weight: 600;
            color: var(--ccbrt-navy);
            margin-bottom: 0.5rem;
        }
        
        .step-description {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Footer */
        footer {
            background: linear-gradient(180deg, var(--ccbrt-navy-light) 0%, var(--ccbrt-navy) 100%);
            color: var(--ccbrt-white);
            padding: 3rem 0 2rem;
            margin-top: auto;
            border-top: 8px solid var(--ccbrt-lime);
        }
        
        footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        footer a:hover {
            color: var(--ccbrt-white);
        }
        
        /* Alert Styles */
        .alert-ccbrt {
            border-radius: 0.5rem;
            padding: 1rem 1.5rem;
        }
        
        .alert-ccbrt-success {
            background-color: #eef7e8;
            border-color: var(--ccbrt-lime);
            color: #184423;
        }
        
        /* Reference Number Display */
        .reference-box {
            background: linear-gradient(135deg, var(--ccbrt-navy) 0%, var(--ccbrt-navy-light) 70%, #107531 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            margin: 2rem 0;
            border-top: 6px solid var(--ccbrt-lime);
        }
        
        .reference-number {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-top: 0.5rem;
        }
        
        /* Status Timeline */
        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }
        
        .status-timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(148,200,61,0.35);
            z-index: 0;
        }
        
        .status-step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        
        .status-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .status-step.active .status-dot {
            background: var(--ccbrt-lime);
            color: #163223;
        }
        
        .status-step.active .status-dot i {
            color: white;
        }
        
        .status-step.completed .status-dot {
            background: var(--ccbrt-navy);
            color: white;
        }
        
        .status-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #6c757d;
        }
        
        .status-step.active .status-label,
        .status-step.completed .status-label {
            color: var(--ccbrt-navy);
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 1.75rem;
            }
            
            .process-steps {
                flex-direction: column;
            }
            
            .process-steps::before {
                display: none;
            }
            
            .step {
                margin-bottom: 2rem;
            }
            
            .status-timeline {
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 991.98px) {
            .navbar-ccbrt .navbar-brand {
                font-size: 1.2rem;
                gap: 0.65rem;
            }

            .language-switcher {
                width: fit-content;
                margin-top: 1rem;
                margin-left: auto;
            }

            .public-brand-logo {
                width: 48px;
                height: 48px;
            }

            .public-brand-name {
                font-size: 1.05rem;
            }

            .public-brand-subtitle {
                font-size: 0.62rem;
            }
        }
        
        .footer-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.9rem;
        }

        .footer-brand .public-brand-logo {
            width: 68px;
            height: 68px;
            background: rgba(255,255,255,0.14);
        }

        .footer-brand-title {
            margin-bottom: 0.3rem;
            font-weight: 700;
        }
    </style>
    
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-ccbrt">
        <div class="container">
            <a class="navbar-brand" href="<?php echo e(url('/')); ?>">
                <img src="<?php echo e(asset('assets/images/ccbrt-logo.svg')); ?>" alt="CCBRT Logo" class="public-brand-logo">
                <span class="public-brand-text">
                    <span class="public-brand-name"><?php echo e(__('portal.brand.hospital')); ?></span>
                    <span class="public-brand-subtitle"><?php echo e(__('portal.brand.portal')); ?></span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('/') ? 'active' : ''); ?>" href="<?php echo e(url('/')); ?>"><?php echo e(__('portal.nav.home')); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('feedback*') ? 'active' : ''); ?>" href="<?php echo e(route('feedback.create')); ?>"><?php echo e(__('portal.nav.submit_feedback')); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('track*') ? 'active' : ''); ?>" href="<?php echo e(route('feedback.track')); ?>"><?php echo e(__('portal.nav.track_feedback')); ?></a>
                    </li>
                </ul>
                <form method="POST" action="<?php echo e(route('locale.switch')); ?>" class="language-switcher">
                    <?php echo csrf_field(); ?>
                    <span class="language-switcher-label"><?php echo e(__('portal.locale.label')); ?></span>
                    <div class="language-switcher-options" role="radiogroup" aria-label="<?php echo e(__('portal.locale.label')); ?>">
                        <input type="radio" class="btn-check" name="locale" id="locale-en" value="en" autocomplete="off" onchange="this.form.submit()" <?php echo e(app()->getLocale() === 'en' ? 'checked' : ''); ?>>
                        <label class="btn btn-language-toggle" for="locale-en"><?php echo e(__('portal.locale.english')); ?></label>
                        <input type="radio" class="btn-check" name="locale" id="locale-sw" value="sw" autocomplete="off" onchange="this.form.submit()" <?php echo e(app()->getLocale() === 'sw' ? 'checked' : ''); ?>>
                        <label class="btn btn-language-toggle" for="locale-sw"><?php echo e(__('portal.locale.swahili')); ?></label>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="footer-brand mb-3">
                        <img src="<?php echo e(asset('assets/images/ccbrt-logo.svg')); ?>" alt="CCBRT Logo" class="public-brand-logo">
                        <div>
                            <h5 class="footer-brand-title"><?php echo e(__('portal.brand.hospital')); ?></h5>
                            <p class="mb-0"><?php echo e(__('portal.brand.about')); ?></p>
                        </div>
                    </div>
                    <p class="mb-0"><i class="bi bi-geo-alt me-2"></i><?php echo e(__('portal.footer.location')); ?></p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5 class="mb-3"><?php echo e(__('portal.footer.contact_us')); ?></h5>
                    <p class="mb-2"><i class="bi bi-telephone me-2"></i>+255 22 277 5000</p>
                    <p class="mb-2"><i class="bi bi-envelope me-2"></i>feedback@ccbrt.org</p>
                    <p class="mb-0"><i class="bi bi-clock me-2"></i><?php echo e(__('portal.footer.hours')); ?></p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5 class="mb-3"><?php echo e(__('portal.footer.privacy_terms')); ?></h5>
                    <p class="mb-2"><?php echo e(__('portal.footer.privacy_copy')); ?></p>
                    <p class="mb-0"><a href="#"><?php echo e(__('portal.footer.privacy_policy')); ?></a> | <a href="#"><?php echo e(__('portal.footer.terms_of_use')); ?></a></p>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo e(date('Y')); ?> <?php echo e(__('portal.brand.hospital')); ?>. <?php echo e(__('portal.footer.rights_reserved')); ?></p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/Customer Feedback System/resources/views/layouts/public.blade.php ENDPATH**/ ?>