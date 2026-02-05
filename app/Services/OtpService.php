<?php

namespace App\Services;

use App\Models\Otp;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Send OTP via email
     */
    public function sendOtp(string $email, string $otpCode, string $type): bool
    {
        try {
            // Log the OTP for debugging (remove in production)
            Log::info("OTP generated", [
                'email' => $email,
                'otp' => $otpCode,
                'type' => $type
            ]);

            // Send actual email
            Mail::to($email)->send(new OtpMail($otpCode, $type, 10));

            Log::info("OTP email sent successfully", [
                'email' => $email,
                'type' => $type
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email", [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate and send OTP for signup
     */
    public function sendSignupOtp(string $email): array
    {
        try {
            $otp = Otp::createOtp($email, 'signup');
            
            if ($this->sendOtp($email, $otp->otp_code, 'signup')) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully to your email',
                    'expires_in' => 10 // minutes
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send OTP'
            ];
        } catch (\Exception $e) {
            Log::error("Signup OTP error", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to generate OTP'
            ];
        }
    }

    /**
     * Generate and send OTP for login
     */
    public function sendLoginOtp(string $email): array
    {
        try {
            $otp = Otp::createOtp($email, 'login');
            
            if ($this->sendOtp($email, $otp->otp_code, 'login')) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully to your email',
                    'expires_in' => 10 // minutes
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send OTP'
            ];
        } catch (\Exception $e) {
            Log::error("Login OTP error", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to generate OTP'
            ];
        }
    }

    /**
     * Generate and send OTP for withdrawal
     */
    public function sendWithdrawalOtp(string $email, int $userId): array
    {
        try {
            $otp = Otp::createOtp($email, 'withdrawal', $userId);
            
            if ($this->sendOtp($email, $otp->otp_code, 'withdrawal')) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully to your email',
                    'expires_in' => 10 // minutes
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send OTP'
            ];
        } catch (\Exception $e) {
            Log::error("Withdrawal OTP error", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to generate OTP'
            ];
        }
    }

    /**
     * Generate and send OTP for password reset
     */
    public function sendPasswordResetOtp(string $email, int $userId): array
    {
        try {
            $otp = Otp::createOtp($email, 'password_reset', $userId);
            
            if ($this->sendOtp($email, $otp->otp_code, 'password_reset')) {
                return [
                    'success' => true,
                    'message' => 'Password reset OTP sent successfully to your email',
                    'expires_in' => 10 // minutes
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send OTP'
            ];
        } catch (\Exception $e) {
            Log::error("Password reset OTP error", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to generate OTP'
            ];
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $email, string $otpCode, string $type): array
    {
        try {
            // Use the Otp model's static verifyOtp method
            $otp = Otp::verifyOtp($email, $otpCode, $type);
            
            if ($otp) {
                return [
                    'success' => true,
                    'message' => 'OTP verified successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ];
        } catch (\Exception $e) {
            Log::error("OTP verification error", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to verify OTP: ' . $e->getMessage()
            ];
        }
    }
}
