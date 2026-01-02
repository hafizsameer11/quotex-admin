<?php

use App\Http\Controllers\Api\ChainController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepositeController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\InvestmentPlanController;
use App\Http\Controllers\Api\MiningController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\LoyaltyBoostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;






// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/optimize-app', function () {
    Artisan::call('optimize:clear'); // Clears cache, config, route, and view caches
    Artisan::call('cache:clear');    // Clears application cache
    Artisan::call('config:clear');   // Clears configuration cache
    Artisan::call('route:clear');    // Clears route cache
    Artisan::call('view:clear');     // Clears compiled Blade views
    Artisan::call('config:cache');   // Rebuilds configuration cache
    Artisan::call('route:cache');    // Rebuilds route cache
    Artisan::call('view:cache');     // Precompiles Blade templates
    Artisan::call('optimize');       // Optimizes class loading

    return "Application optimized and caches cleared successfully!";
});
Route::get('/migrate', function () {
    Artisan::call('migrate');
    return response()->json(['message' => 'Migration successful'], 200);
});
Route::get('/migrate/rollback', function () {
    Artisan::call('migrate:rollback');
    return response()->json(['message' => 'Migration rollback successfully'], 200);
});

Route::get('/unath', function () {
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('login.auth');
// OTP routes
Route::post('/otp/send-signup', [OtpController::class, 'sendSignupOtp']);
Route::post('/otp/send-login', [OtpController::class, 'sendLoginOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);

// Password reset routes
Route::post('/password/send-reset-otp', [PasswordResetController::class, 'sendResetOtp']);
Route::post('/password/verify-reset-otp', [PasswordResetController::class, 'verifyResetOtp']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/allUser', [UserController::class, 'allUser'])->middleware('auth:sanctum');
Route::post('/update', [UserController::class, 'update'])->middleware('auth:sanctum');
Route::post('/kyc-user/{user_id}', [UserController::class, 'kyc'])->middleware('auth:sanctum');
Route::get('/profile', [UserController::class, 'profile'])->middleware('auth:sanctum');
Route::apiResource('/investment_plan', InvestmentPlanController::class)->middleware('auth:sanctum');
// Deposit routes
Route::post('/deposits', [DepositeController::class, 'store'])->middleware('auth:sanctum'); // Just deposit money
Route::post('/activate-plan/{plan_id}', [DepositeController::class, 'activatePlan'])->middleware('auth:sanctum'); // Activate plan with existing balance
// deposite approval
Route::post('/approval-deposits/{user_id}/{depositId}', [DepositeController::class, 'update'])->middleware('auth:sanctum');

// withdrawal
Route::post('/withdrawal', [WithdrawalController::class, 'store'])->middleware('auth:sanctum');
Route::post('/withdrawal/otp', [OtpController::class, 'sendWithdrawalOtp'])->middleware('auth:sanctum');
// withdrawal approval
Route::put('/approval-withdrawal/{user_id}/{withdrawalId}', [WithdrawalController::class, 'update'])->middleware('auth:sanctum');

// News routes
Route::get('/news', [NewsController::class, 'index'])->middleware('auth:sanctum');
Route::get('/news/{type}', [NewsController::class, 'getByType'])->middleware('auth:sanctum');

// KYC routes
Route::post('/kyc/upload', [KycController::class, 'upload'])->middleware('auth:sanctum');
Route::get('/kyc/documents', [KycController::class, 'userDocuments'])->middleware('auth:sanctum');
Route::get('/kyc/download/{id}', [KycController::class, 'download'])->middleware('auth:sanctum');

Route::get('/dashboard', [DashboardController::class, 'dashboard'])->middleware('auth:sanctum');
Route::get('/about', [DashboardController::class, 'about'])->middleware('auth:sanctum');

// Test route to check current user
Route::get('/test-user', function () {
    return response()->json([
        'user_id' => Auth::id(),
        'user' => Auth::user(),
        'authenticated' => Auth::check()
    ]);
})->middleware('auth:sanctum');
Route::post('/contact', [ContactController::class, 'contact'])->middleware('auth:sanctum');

Route::get('/single-transaction', [TransactionController::class, 'userTransactions'])->middleware('auth:sanctum');
Route::get('/all-transaction', [TransactionController::class, 'allTransactions'])->middleware('auth:sanctum');

// Admin transaction management routes
Route::get('/admin/transactions', [UserController::class, 'getAllTransactions'])->middleware('auth:sanctum');
Route::get('/admin/transactions/{id}', [UserController::class, 'getTransactionDetails'])->middleware('auth:sanctum');
Route::put('/admin/transactions/{id}', [UserController::class, 'updateTransaction'])->middleware('auth:sanctum');
Route::put('/admin/transactions/bulk-update', [UserController::class, 'bulkUpdateTransactions'])->middleware('auth:sanctum');

// Admin referral amount management routes
Route::get('/admin/users/{id}/referral-transactions', [UserController::class, 'getUserReferralTransactions'])->middleware('auth:sanctum');
Route::put('/admin/users/{id}/referral-amount', [UserController::class, 'updateReferralAmount'])->middleware('auth:sanctum');
Route::put('/admin/referral-transactions/{id}', [UserController::class, 'updateReferralTransaction'])->middleware('auth:sanctum');
Route::put('/admin/referral-amounts/bulk-update', [UserController::class, 'bulkUpdateReferralAmounts'])->middleware('auth:sanctum');

Route::get('/investment', [InvestmentController::class, 'investment'])->middleware('auth:sanctum');
Route::post('/investment/create', [InvestmentController::class, 'createInvestment'])->middleware('auth:sanctum');
Route::post('/investment/{id}/cancel', [InvestmentController::class, 'cancelInvestment'])->middleware('auth:sanctum');

// User deposit and withdrawal history
Route::get('/user-deposits', [DepositeController::class, 'userDeposits'])->middleware('auth:sanctum');
Route::get('/user-withdrawals', [WithdrawalController::class, 'userWithdrawals'])->middleware('auth:sanctum');

// Chain/Wallet addresses
Route::get('/chains', [ChainController::class, 'index'])->middleware('auth:sanctum');
Route::get('/chains/{id}', [ChainController::class, 'show'])->middleware('auth:sanctum');

// Mining routes
Route::post('/mining/start', [MiningController::class, 'start'])->middleware('auth:sanctum');
Route::get('/mining/status', [MiningController::class, 'status'])->middleware('auth:sanctum');
Route::post('/mining/stop', [MiningController::class, 'stop'])->middleware('auth:sanctum');
Route::post('/mining/claim-rewards', [MiningController::class, 'claimRewards'])->middleware('auth:sanctum');

// Referral routes
Route::get('/referrals/my-referrals', [ReferralController::class, 'getMyReferrals'])->middleware('auth:sanctum');
Route::get('/referrals/network', [ReferralController::class, 'getReferralNetwork'])->middleware('auth:sanctum');
Route::get('/referrals/stats', [ReferralController::class, 'getReferralStats'])->middleware('auth:sanctum');

// Loyalty routes
Route::get('/loyalty/user-status', [LoyaltyController::class, 'getUserLoyalty'])->middleware('auth:sanctum');
Route::get('/loyalty/tiers', [LoyaltyController::class, 'getAllLoyalties'])->middleware('auth:sanctum');
Route::post('/loyalty/tiers', [LoyaltyController::class, 'store'])->middleware('auth:sanctum');
Route::put('/loyalty/tiers/{id}', [LoyaltyController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/loyalty/tiers/{id}', [LoyaltyController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/loyalty/tiers/{id}', [LoyaltyController::class, 'show'])->middleware('auth:sanctum');

// Loyalty Boost routes
Route::get('/loyalty-boost', [LoyaltyBoostController::class, 'getLoyaltyBoost'])->middleware('auth:sanctum');
