<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to EduX</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            color: #1f2937;
        }
        .wrapper {
            width: 100%;
            padding: 40px 16px;
            box-sizing: border-box;
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        }
        .header {
            padding: 36px 40px 24px;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            color: #fff;
        }
        .badge {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.14);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .content {
            padding: 40px;
            line-height: 1.7;
            font-size: 16px;
        }
        .button {
            display: inline-block;
            margin: 24px 0 10px;
            padding: 14px 24px;
            background: #1d4ed8;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
        }
        .link {
            word-break: break-all;
            font-size: 13px;
            color: #475569;
        }
        .footer {
            padding: 20px 40px 36px;
            color: #64748b;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="badge">EduX Welcome</div>
                <h1 style="margin:16px 0 0;font-size:28px;line-height:1.2;">Verify your email to start learning</h1>
            </div>
            <div class="content">
                <p>Hi {{ $userName ?? 'Student' }},</p>
                <p>Welcome to EduX. Your account has been created successfully. Click the button below to verify your email and activate your access to the platform.</p>

                <a href="{{ $verificationUrl }}" class="button">Verify Email</a>

                <p>If the button does not work, copy and paste this link into your browser:</p>
                <p class="link">{{ $verificationUrl }}</p>

                <p>Thanks,<br>The EduX Team</p>
            </div>
            <div class="footer">
                <p>This verification link expires after 24 hours for your security.</p>
            </div>
        </div>
    </div>
</body>
</html>