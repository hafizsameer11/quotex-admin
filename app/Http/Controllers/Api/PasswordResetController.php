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

            // Verify OTP
            $otpResult = $this->otpService->verifyOtp($email, $otp, 'password_reset');
            if (!$otpResult['success']) {
                return ResponseHelper::error($otpResult['message'], 422);
            }

            // Update password
            $user = User::where('email', $email)->first();
            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }

            $user->update([
                'password' => Hash::make($password)
            ]);

            return ResponseHelper::success(null, 'Password reset successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify OTP only (for frontend validation)
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

            $result = $this->otpService->verifyOtp(
                $request->email,
                $request->otp,
                'password_reset'
            );

            if ($result['success']) {
                return ResponseHelper::success($result, $result['message']);
            }

            return ResponseHelper::error($result['message'], 400);
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to verify OTP: ' . $e->getMessage(), 500);
        }
    }
}










