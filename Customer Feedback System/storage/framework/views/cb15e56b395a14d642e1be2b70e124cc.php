<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Login'); ?> | CCBRT Feedback System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/bootstrap.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/icons.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/app.min.css')); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; }
        .auth-page-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #065321 0%, #0b6b2c 52%, #15803d 100%);
            padding: 1rem;
        }
        .auth-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .auth-card-wide { max-width: 680px; }
        .auth-header {
            background: linear-gradient(135deg, #065321, #0b6b2c);
            padding: 2rem;
            text-align: center;
            color: #fff;
        }
        .auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        .auth-brand-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            padding: 0.25rem;
            flex-shrink: 0;
        }
        .auth-brand-copy {
            text-align: left;
        }
        .auth-header .logo-text {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .auth-header .logo-sub {
            font-size: 0.78rem;
            opacity: 0.8;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .auth-body { padding: 2rem; }
        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.6rem 0.85rem;
            font-size: 0.9rem;
            border: 1px solid #d1d5db;
        }
        .form-control:focus, .form-select:focus {
            border-color: #94c83d;
            box-shadow: 0 0 0 3px rgba(148,200,61,0.18);
        }
        .btn-auth {
            background: linear-gradient(135deg, #15803d, #0b6b2c);
            color: #fff;
            border: none;
            padding: 0.65rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            transition: opacity 0.2s;
        }
        .btn-auth:hover { opacity: 0.9; color: #fff; }
        .section-divider {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.4rem;
            margin-bottom: 1rem;
            margin-top: 1.25rem;
        }
        .invalid-feedback { display: block; }
        @media (max-width: 575.98px) {
            .auth-brand {
                flex-direction: column;
                gap: 0.75rem;
            }
            .auth-brand-copy {
                text-align: center;
            }
            .auth-brand-logo {
                width: 64px;
                height: 64px;
            }
            .auth-header .logo-text {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-page-wrapper">
        <?php echo e($slot); ?>

    </div>

    <script src="<?php echo e(asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
</body>
</html>
<?php /**PATH /var/www/Customer Feedback System/resources/views/layouts/guest.blade.php ENDPATH**/ ?>