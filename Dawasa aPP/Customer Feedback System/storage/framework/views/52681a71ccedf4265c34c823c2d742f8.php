<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update on your feedback - CCBRT Hospital</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f4f7f3;
            margin: 0;
            padding: 24px 0;
            color: #163223;
        }
        .container {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 35px rgba(6, 83, 33, 0.12);
        }
        .header {
            background: linear-gradient(135deg, #065321, #0b6b2c);
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .content {
            padding: 28px;
            line-height: 1.7;
            font-size: 15px;
        }
        .status-box {
            background: #eef7e8;
            border-left: 4px solid #15803d;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 20px 0;
        }
        .response-box {
            background: #f8faf8;
            border: 1px solid #d7e8d4;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 20px 0;
            white-space: pre-wrap;
        }
        .reference-box {
            text-align: center;
            background: #065321;
            color: #ffffff;
            border-radius: 14px;
            padding: 20px;
            margin: 24px 0;
        }
        .reference-box p {
            margin: 0 0 8px;
            opacity: 0.8;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .reference-number {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .button-wrap {
            text-align: center;
            margin: 28px 0 10px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #15803d, #0b6b2c);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 26px;
            border-radius: 999px;
            font-weight: 600;
        }
        .footer {
            background: #163223;
            color: rgba(255, 255, 255, 0.82);
            padding: 24px 28px;
            font-size: 13px;
        }
        .footer a {
            color: #add95a;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo e($hospitalName); ?></h1>
            <p>Feedback Response</p>
        </div>
        <div class="content">
            <p>Dear <?php echo e($patientName); ?>,</p>
            <p>We have reviewed your feedback and posted an update on your submission.</p>
            <div class="reference-box">
                <p>Reference Number</p>
                <div class="reference-number"><?php echo e($referenceNo); ?></div>
            </div>
            <div class="status-box">
                <strong>Current status:</strong> <?php echo e($statusLabel); ?>

            </div>
            <div>
                <strong>Our response</strong>
            </div>
            <div class="response-box"><?php echo e($responseContent); ?></div>
            <div class="button-wrap">
                <a href="<?php echo e($trackUrl); ?>" class="button">Track Your Feedback</a>
            </div>
            <p>Thank you for helping us improve the care and services we provide at <?php echo e($hospitalName); ?>.</p>
        </div>
        <div class="footer">
            <div><?php echo e($hospitalName); ?> Customer Feedback System</div>
            <div style="margin-top: 10px;">Email: <a href="mailto:feedback@ccbrt.org">feedback@ccbrt.org</a></div>
        </div>
    </div>
</body>
</html>
<?php /**PATH /var/www/Customer Feedback System/resources/views/emails/feedback-response.blade.php ENDPATH**/ ?>