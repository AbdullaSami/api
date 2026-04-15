<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">

<title>Your OTP Code</title>

<style>
    body {
        margin: 0;
        padding: 40px 20px;
        background-color: #0d1b2a;
    }

    .container {
        max-width: 500px;
        margin: 0 auto;
        background-color: #1a2d42;
        padding: 48px;
        border-radius: 16px;
        border: 1px solid #2a3f58;
    }

    .title {
        color: #ffffff;
        font-size: 24px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 32px;
    }

    .text {
        color: #9db5cc;
        font-size: 16px;
        text-align: center;
        margin-bottom: 32px;
        line-height: 1.6;
    }

    .otp-box {
        background: rgba(43, 108, 176, 0.1);
        border: 2px solid #2b6cb0;
        padding: 24px;
        text-align: center;
        border-radius: 12px;
        margin-bottom: 32px;
    }

    .otp {
        font-size: 36px;
        font-weight: 800;
        letter-spacing: 12px;
        color: #4b9be0;
    }

    .footer {
        color: #5c7a96;
        font-size: 13px;
        text-align: center;
        margin-top: 40px;
        padding-top: 32px;
        border-top: 1px solid #2a3f58;
    }

    /* Light mode fallback (for clients that support it) */
    @media (prefers-color-scheme: light) {
        body {
            background-color: #f4f7fb;
        }

        .container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
        }

        .title {
            color: #1a202c;
        }

        .text {
            color: #4a5568;
        }

        .otp-box {
            background: #ebf4ff;
            border-color: #3182ce;
        }

        .otp {
            color: #2b6cb0;
        }

        .footer {
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }
    }
</style>
</head>

<body>
    <div class="container">
        <div class="title">Verification Code</div>

        <div class="text">
            Please use the following key to verify your identity. This unique code helps us keep your account secure.
        </div>

        <div class="otp-box">
            <div class="otp">{{ $otp }}</div>
        </div>

        <div class="text" style="font-size:14px;">
            This code will expire in <strong>{{ $expirationMinutes }} minutes</strong>.
        </div>

        <div class="footer">
            If you didn't request this, you can safely ignore this email.<br>
            &copy; {{ date('Y') }} The Nova Group. All rights reserved.
        </div>
    </div>
</body>
</html>
