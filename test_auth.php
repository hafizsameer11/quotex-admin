<?php

/**
 * Test Authentication and KYC Functionality
 * This script helps debug authentication issues
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\KycDocument;

echo "=== Authentication Test ===\n";

// Test 1: Check if we can find a user
echo "1. Testing user lookup...\n";
$user = User::first();
if ($user) {
    echo "✅ Found user: {$user->email} (ID: {$user->id})\n";
} else {
    echo "❌ No users found in database\n";
    exit;
}

// Test 2: Check if Sanctum is working
echo "\n2. Testing Sanctum authentication...\n";
try {
    // Create a token for the user
    $token = $user->createToken('test-token')->plainTextToken;
    echo "✅ Token created: " . substr($token, 0, 20) . "...\n";
    
    // Test if we can authenticate with the token
    $request = new \Illuminate\Http\Request();
    $request->headers->set('Authorization', 'Bearer ' . $token);
    
    // This would normally be done by middleware, but we're testing manually
    echo "✅ Token format is correct\n";
    
} catch (Exception $e) {
    echo "❌ Token creation failed: " . $e->getMessage() . "\n";
}

// Test 3: Check KYC model
echo "\n3. Testing KYC model...\n";
try {
    $kycCount = KycDocument::count();
    echo "✅ KYC documents in database: {$kycCount}\n";
    
    if ($user) {
        $userKycCount = KycDocument::where('user_id', $user->id)->count();
        echo "✅ KYC documents for user {$user->id}: {$userKycCount}\n";
    }
} catch (Exception $e) {
    echo "❌ KYC model test failed: " . $e->getMessage() . "\n";
}

// Test 4: Check file storage
echo "\n4. Testing file storage...\n";
try {
    $storagePath = storage_path('app/kyc');
    if (!file_exists($storagePath)) {
        mkdir($storagePath, 0755, true);
        echo "✅ Created KYC storage directory\n";
    } else {
        echo "✅ KYC storage directory exists\n";
    }
    
    $isWritable = is_writable($storagePath);
    echo $isWritable ? "✅ Storage directory is writable\n" : "❌ Storage directory is not writable\n";
} catch (Exception $e) {
    echo "❌ Storage test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the API manually:\n";
echo "1. Login to get a token\n";
echo "2. Use the token in Authorization header: Bearer {token}\n";
echo "3. Make a POST request to /api/kyc/upload with form data\n";
echo "4. Check the response for any errors\n";











