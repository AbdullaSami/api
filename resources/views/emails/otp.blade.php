<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your OTP Code</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color: #333; text-align: center; margin-bottom: 30px;">Your Verification Code</h2>
        
        <p style="color: #666; font-size: 16px; text-align: center; margin-bottom: 20px;">
            Please use the following code to verify your request:
        </p>
        
        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #333;">{{ $otp }}</span>
        </div>
        
        <p style="color: #999; font-size: 14px; text-align: center; margin-bottom: 10px;">
            This code will expire in <strong>{{ $expirationMinutes }} minutes</strong>.
        </p>
        
        <p style="color: #999; font-size: 14px; text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            If you did not request this code, please ignore this email.
        </p>
    </div>
</body>
</html>
