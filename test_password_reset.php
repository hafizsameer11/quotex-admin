<?php

/**
 * Test Password Reset Functionality
 * This script helps debug password reset issues
 */

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Otp;
use App\Services\OtpService;

echo "=== Password Reset Test ===\n";

// Test 1: Check if we can find a user
echo "1. Testing user lookup...\n";
$user = User::first();
if ($user) {
    echo "✅ Found user: {$user->email} (ID: {$user->id})\n";
} else {
    echo "❌ No users found in database\n";
    exit;
}

// Test 2: Test OTP creation for password reset
echo "\n2. Testing OTP creation for password reset...\n";
try {
    $otpService = new OtpService();
    $result = $otpService->sendPasswordResetOtp($user->email, $user->id);
    
    if ($result['success']) {
        echo "✅ OTP created and sent successfully\n";
        echo "   Message: {$result['message']}\n";
        echo "   Expires in: {$result['expires_in']} minutes\n";
    } else {
        echo "❌ OTP creation failed: {$result['message']}\n";
    }
} catch (Exception $e) {
    echo "❌ OTP creation error: " . $e->getMessage() . "\n";
}

// Test 3: Check OTP in database
echo "\n3. Testing OTP database record...\n";
try {
    $otp = Otp::where('email', $user->email)
        ->where('type', 'password_reset')
        ->where('used', false)
        ->latest()
        ->first();
    
    if ($otp) {
        echo "✅ OTP found in database\n";
        echo "   OTP Code: {$otp->otp_code}\n";
        echo "   Expires at: {$otp->expires_at}\n";
        echo "   Is expired: " . ($otp->isExpired() ? 'Yes' : 'No') . "\n";
        echo "   Is valid: " . ($otp->isValid() ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ No OTP found in database\n";
    }
} catch (Exception $e) {
    echo "❌ Database check error: " . $e->getMessage() . "\n";
}

// Test 4: Test OTP verification
echo "\n4. Testing OTP verification...\n";
if (isset($otp) && $otp) {
    try {
        $otpService = new OtpService();
        $result = $otpService->verifyOtp($user->email, $otp->otp_code, 'password_reset');
        
        if ($result['success']) {
            echo "✅ OTP verification successful\n";
            echo "   Message: {$result['message']}\n";
        } else {
            echo "❌ OTP verification failed: {$result['message']}\n";
        }
    } catch (Exception $e) {
        echo "❌ OTP verification error: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  Skipping OTP verification test (no OTP found)\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the API manually:\n";
echo "1. Send POST to /api/password/send-reset-otp with email\n";
echo "2. Check email for OTP\n";
echo "3. Send POST to /api/password/verify-reset-otp with email and OTP\n";
echo "4. Send POST to /api/password/reset with email, OTP, password, and password_confirmation\n";











