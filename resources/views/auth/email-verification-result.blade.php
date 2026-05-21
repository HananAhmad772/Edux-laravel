<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Email Verification' }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: Arial, sans-serif;
            color: #0f172a;
        }
        .panel {
            width: min(640px, calc(100vw - 32px));
            background: #fff;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.14);
            text-align: center;
        }
        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 18px;
        }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .button {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 22px;
            border-radius: 12px;
            background: #1d4ed8;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="panel">
        <div class="status {{ $status ?? 'success' }}">{{ $status ?? 'success' }}</div>
        <h1 style="margin:0 0 12px;font-size:30px;">{{ $title ?? 'Email Verification' }}</h1>
        <p style="margin:0;font-size:16px;line-height:1.7;">{{ $message ?? 'Your email has been verified.' }}</p>
        <a href="/" class="button">Go to Home</a>
    </div>
</body>
</html>