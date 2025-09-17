<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 40px 0;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .email-header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 1px solid #dddddd;
        }
        .email-header h1 {
            margin: 10px 0 0 0;
            color: #1a73e8;
            font-size: 24px;
        }
        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px 0;
            line-height: 1.6;
        }
        .email-body p {
            margin-bottom: 20px;
        }
        .otp-code {
            font-size: 36px;
            letter-spacing: 12px;
            font-weight: bold;
            text-align: center;
            margin: 30px 0;
            color: #1a73e8;
        }
        .email-footer {
            font-size: 12px;
            color: #999999;
            text-align: center;
            border-top: 1px solid #dddddd;
            padding-top: 30px;
        }
        .email-warning {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 5px solid #ffeeba;
            margin-top: 30px;
            border-radius: 4px;
        }
        .email-signature {
            margin-top: 30px;
        }
        .tagline {
            font-size: 14px;
            color: #6c757d;
            font-weight: 400;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <!-- Logo Placeholder -->
                <img src="{{ asset('images/logo.png') }}" alt="EduX Logo" class="logo">
                <h1>EduX</h1>
                <div class="tagline">Educational Management Platform</div>
            </div>
            <div class="email-body">
                <p>Hi {{ $userName ?? 'User' }},</p>
                <p>We received a request to reset your password. Please use the OTP code below to proceed. This OTP will expire in <strong>10 minutes</strong>.</p>

                <div class="otp-code">{{ $otp }}</div>

                <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>

                <div class="email-warning">
                    <strong>Security Tip:</strong> Never share your OTP with anyone. EduX will never ask you for your OTP.
                </div>

                <div class="email-signature">
                    <p>Thanks,<br>The EduX Team</p>
                </div>
            </div>
            <div class="email-footer">
                <p>&copy; {{ date('Y') }} EduX. All rights reserved.</p>
                <p>Need help? Contact support at <a href="mailto:support@edux.com">support@edux.com</a></p>
            </div>
        </div>
    </div>
</body>
</html>
