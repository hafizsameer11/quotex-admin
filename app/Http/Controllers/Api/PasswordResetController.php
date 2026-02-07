<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send password reset OTP
     */
    public function sendResetOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error($validator->errors()->first(), 422);
            }

            $email = $request->email;
            
            // Check if user exists
            $user = User::where('email', $email)->first();
            if (!$user) {
                return ResponseHelper::error('No account found with this email address', 404);
            }

            // Send OTP
            $result = $this->otpService->sendPasswordResetOtp($email, $user->id);

            if ($result['success']) {
                return ResponseHelper::success($result, $result['message']);
            }

            return ResponseHelper::error($result['message'], 400);
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to send reset OTP: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify OTP and reset password
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:6|confirmed'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error($validator->errors()->first(), 422);
            }

            $email = $request->email;
            $otp = $request->otp;
            $password = $request->password;

            // Verify OTP exists and is valid (but don't mark as used yet - it was already verified in verifyResetOtp)
            $otpRecord = \App\Models\Otp::where('email', $email)
                ->where('otp_code', $otp)
                ->where('type', 'password_reset')
                ->where('used', false)
                ->first();

            if (!$otpRecord || $otpRecord->isExpired()) {
                return ResponseHelper::error('Invalid or expired OTP', 422);
            }

            // Update password
            $user = User::where('email', $email)->first();
            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }

            $user->update([
                'password' => Hash::make($password)
            ]);

            // Mark OTP as used after successful password reset
            $otpRecord->markAsUsed();

            return ResponseHelper::success(null, 'Password reset successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify OTP only (for frontend validation)
     * Note: We don't mark OTP as used here - it will be marked when password is actually reset
     */
    public function verifyResetOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error($validator->errors()->first(), 422);
            }

            // Check if OTP exists and is valid (but don't mark as used yet)
            $otpRecord = \App\Models\Otp::where('email', $request->email)
                ->where('otp_code', $request->otp)
                ->where('type', 'password_reset')
                ->where('used', false)
                ->first();

            if (!$otpRecord) {
                return ResponseHelper::error('Invalid OTP', 400);
            }

            if ($otpRecord->isExpired()) {
                return ResponseHelper::error('OTP has expired', 400);
            }

            // Don't mark as used here - will be marked when password is reset
            return ResponseHelper::success([
                'success' => true,
                'message' => 'OTP verified successfully'
            ], 'OTP verified successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to verify OTP: ' . $e->getMessage(), 500);
        }
    }
}










