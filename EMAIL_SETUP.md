# Email Configuration Guide for MineProfit

## Current Status
- ✅ OTP generation and verification working
- ✅ Email templates created
- ❌ Email sending not configured (currently using log driver)

## Email Configuration Options

### Option 1: Gmail SMTP (Recommended for Development)

Add to your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="MineProfit"
```

**Note:** For Gmail, you need to:
1. Enable 2-factor authentication
2. Generate an "App Password" (not your regular password)
3. Use the app password in MAIL_PASSWORD

### Option 2: Mailtrap (Testing)

Add to your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@investpro.com
MAIL_FROM_NAME="MineProfit"
```

### Option 3: SendGrid (Production)

Add to your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="MineProfit"
```

### Option 4: AWS SES (Production)

Add to your `.env` file:
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="MineProfit"
```

## Testing Email Configuration

### Test Command
```bash
php artisan tinker
```

Then run:
```php
Mail::raw('Test email from MineProfit', function($message) {
    $message->to('your-test-email@example.com')
            ->subject('Test Email');
});
```

### Test OTP Sending
```php
$otpService = new \App\Services\OtpService();
$result = $otpService->sendSignupOtp('test@example.com');
dd($result);
```

## Current Development Setup

For development, you can keep the current log driver to see emails in the log files:

```env
MAIL_MAILER=log
```

Emails will be logged to: `storage/logs/laravel.log`

## Production Recommendations

1. **Use a reliable email service** (SendGrid, AWS SES, or Mailgun)
2. **Set up proper SPF/DKIM records** for your domain
3. **Monitor email delivery rates**
4. **Set up email queues** for better performance
5. **Remove OTP logging** in production (security)

## Troubleshooting

### Common Issues:
1. **Authentication failed**: Check username/password
2. **Connection timeout**: Check firewall/port settings
3. **Emails not sending**: Check MAIL_MAILER setting
4. **Emails going to spam**: Set up proper DNS records

### Debug Commands:
```bash
# Check mail configuration
php artisan config:cache
php artisan config:clear

# Test mail connection
php artisan tinker
Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });
```
