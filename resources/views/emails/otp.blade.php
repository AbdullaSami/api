<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your OTP Code</title>
</head>
<body style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0d1b2a; margin: 0; padding: 40px 20px;">
    <div style="max-width: 500px; margin: 0 auto; background-color: #1a2d42; padding: 48px; border-radius: 16px; border: 1px solid #2a3f58; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <div style="text-align: center; margin-bottom: 32px;">
            <h2 style="color: #ffffff; font-size: 24px; font-weight: 700; margin: 0; letter-spacing: -0.02em;">Verification Code</h2>
        </div>

        <p style="color: #9db5cc; font-size: 16px; text-align: center; margin-bottom: 32px; line-height: 1.6;">
            Please use the following key to verify your identity. This unique code helps us keep your account secure.
        </p>

        <div style="background: rgba(43, 108, 176, 0.1); border: 2px solid #2b6cb0; padding: 24px; text-align: center; border-radius: 12px; margin-bottom: 32px;">
            <span style="font-size: 36px; font-weight: 800; letter-spacing: 12px; color: #4b9be0; font-family: 'Montserrat', sans-serif;">{{ $otp }}</span>
        </div>

        <p style="color: #5c7a96; font-size: 14px; text-align: center; margin-bottom: 0;">
            This code will expire in <strong style="color: #9db5cc;">{{ $expirationMinutes }} minutes</strong>.
        </p>

        <div style="margin-top: 40px; padding-top: 32px; border-top: 1px solid #2a3f58; text-align: center;">
            <p style="color: #5c7a96; font-size: 13px; margin: 0;">
                If you didn't request this, you can safely ignore this email.<br>
                &copy; {{ date('Y') }} The Nova Group. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
