<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you for your feedback - CCBRT Hospital</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #065321 0%, #0b6b2c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .reference-box {
            background: linear-gradient(135deg, #065321 0%, #0b6b2c 70%, #15803d 100%);
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 8px;
            margin: 30px 0;
        }
        .reference-box p {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .reference-number {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 0;
        }
        .steps {
            background-color: #f4f8f1;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .steps h3 {
            color: #065321;
            margin-top: 0;
            font-size: 18px;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            background-color: #15803d;
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            background-color: #065321;
            color: white;
            padding: 30px;
            text-align: center;
            font-size: 12px;
        }
        .footer a {
            color: white;
            text-decoration: underline;
        }
        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .reference-number {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo e($hospitalName); ?></h1>
            <p>Customer Feedback System</p>
        </div>
        
        <div class="content">
            <p>Dear <?php echo e($patientName); ?>,</p>
            
            <p>Thank you for taking the time to provide feedback to <?php echo e($hospitalName); ?>. We have received your submission and it has been forwarded to our Quality Assurance team for review.</p>
            
            <div class="reference-box">
                <p>Your Unique Reference Number</p>
                <div class="reference-number"><?php echo e($referenceNo); ?></div>
            </div>
            
            <p style="text-align: center; color: #666;">Please save this reference number to track your feedback status</p>
            
            <div class="steps">
                <h3>What happens next?</h3>
                <ol>
                    <li><strong>Review:</strong> Our QA team will review your feedback within 2-3 business days</li>
                    <li><strong>Investigation:</strong> We may investigate the matter with relevant departments</li>
                    <li><strong>Response:</strong> We may contact you if additional information is needed</li>
                    <li><strong>Resolution:</strong> We take action based on your feedback to improve our services</li>
                </ol>
            </div>
            
            <p style="text-align: center;">
                <a href="<?php echo e($appUrl); ?>/track?reference_no=<?php echo e($referenceNo); ?>" class="button">Track Your Feedback</a>
            </p>
            
            <p>We value your input as it helps us improve our services to better serve our patients and community.</p>
            
            <p>Best regards,<br>
            <strong>Quality Assurance Team</strong><br>
            <?php echo e($hospitalName); ?></p>
        </div>
        
        <div class="footer">
            <p><strong><?php echo e($hospitalName); ?></strong><br>
            Comprehensive Community Based Rehabilitation in Tanzania</p>
            
            <div class="contact-info">
                <p>
                    Dar es Salaam, Tanzania<br>
                    Phone: +255 22 277 5000<br>
                    Email: <a href="mailto:feedback@ccbrt.org">feedback@ccbrt.org</a>
                </p>
            </div>
            
            <p style="margin-top: 20px; font-size: 11px; opacity: 0.8;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
<?php /**PATH /var/www/Customer Feedback System/resources/views/emails/feedback-acknowledgement.blade.php ENDPATH**/ ?>