<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RQW - Royal Quotex Walefar Verification Code</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #374151;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .otp-container {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            border: 2px dashed #e5e7eb;
        }
        .otp-code {
            font-size: 48px;
            font-weight: 700;
            color: #0ea5e9;
            letter-spacing: 8px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }
        .otp-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .expiry-info {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .expiry-info p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .warning {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .warning p {
            margin: 0;
            color: #991b1b;
            font-size: 14px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .type-badge {
            display: inline-block;
            background-color: #0ea5e9;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>RQW</h1>
            <p>Royal Quotex Walefar - Copy Trading Platform</p>
        </div>
        
        <div class="content">
            <div class="type-badge">
                @switch($type)
                    @case('signup')
                        Account Registration
                        @break
                    @case('login')
                        Login Verification
                        @break
                    @case('withdrawal')
                        Withdrawal Verification
                        @break
                    @default
                        Verification
                @endswitch
            </div>
            
            <h2 style="margin: 0 0 20px 0; color: #1f2937;">
                @switch($type)
                    @case('signup')
                        Complete Your Registration
                        @break
                    @case('login')
                        Secure Login Verification
                        @break
                    @case('withdrawal')
                        Confirm Your Withdrawal
                        @break
                    @default
                        Verification Required
                @endswitch
            </h2>
            
            <p style="margin: 0 0 30px 0; color: #6b7280; line-height: 1.6;">
                @switch($type)
                    @case('signup')
                        Thank you for choosing RQW - Royal Quotex Walefar! To complete your account registration, please use the verification code below.
                        @break
                    @case('login')
                        For your security, please enter the verification code below to complete your login.
                        @break
                    @case('withdrawal')
                        To confirm your withdrawal request, please enter the verification code below.
                        @break
                    @default
                        Please use the verification code below to complete your action.
                @endswitch
            </p>
            
            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otpCode }}</div>
                <div class="otp-label">Enter this code in the app</div>
            </div>
            
            <div class="expiry-info">
                <p>⏰ This code will expire in {{ $expiresIn }} minutes</p>
            </div>
            
            <div class="warning">
                <p>🔒 <strong>Security Notice:</strong> Never share this code with anyone. RQW staff will never ask for your verification code.</p>
            </div>
            
            <p style="margin: 30px 0 0 0; color: #6b7280; font-size: 14px;">
                If you didn't request this code, please ignore this email or contact our support team immediately.
            </p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} RQW - Royal Quotex Walefar. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
