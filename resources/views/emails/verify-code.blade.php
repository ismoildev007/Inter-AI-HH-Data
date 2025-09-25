<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: #4f46e5;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .content {
            padding: 30px 25px;
        }
        .content h2 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #111827;
        }
        .code-box {
            background: #f3f4f6;
            border: 2px dashed #4f46e5;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            color: #4f46e5;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: #4f46e5;
            color: #fff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 15px;
        }
        .footer {
            background: #f9fafb;
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ config('app.name') }} – Verify Your Email</h1>
    </div>
    <div class="content">
        <h2>Hello {{ $user->first_name ?? 'User' }} 👋</h2>
        <p>Thank you for registering. Please use the following verification code to confirm your email:</p>

        <div class="code-box">
            {{ $code }}
        </div>

        <p>This code will expire soon. If you didn’t request this, please ignore this email.</p>

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}" class="btn">Go to Website</a>
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
</div>
</body>
</html>
