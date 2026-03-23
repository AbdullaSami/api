<?php

namespace App\Services;

use App\Mail\SendOtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    protected int $otpLength = 6;
    protected int $expirationMinutes;
    protected int $maxAttempts;

    public function __construct()
    {
        $this->expirationMinutes = config('security.otp_expiration', 5);
        $this->maxAttempts = config('security.otp_max_attempts', 5);
    }

    /**
     * Generate a new OTP for the user with operation binding.
     *
     * @param User $user
     * @param string|null $operationId
     * @return int The plain text OTP (for sending to user)
     */
    public function generate(User $user, ?string $operationId = null): int
    {
        // Invalidate previous OTPs for this user
        $this->invalidatePreviousOtps($user);

        // Generate 6-digit OTP
        $plainOtp = $this->generateRandomOtp();

        // Hash and store OTP with operation binding
        Otp::create([
            'user_id' => $user->id,
            'operation_id' => $operationId,
            'code' => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes($this->expirationMinutes),
            'is_used' => false,
            'attempts' => 0,
        ]);

        // Send OTP via email
        $this->sendOtp($user, $plainOtp);

        // Log without sensitive data
        Log::info('OTP generated', [
            'user_id' => $user->id,
            'operation_id' => $operationId,
        ]);

        // Debug log for local environment
        if (app()->environment('local')) {
            Log::debug('OTP Debug', ['otp' => $plainOtp]);
        }

        return $plainOtp;
    }

    /**
     * Verify an OTP for a user with operation binding.
     *
     * @param User $user
     * @param string $code
     * @param string|null $operationId
     * @return array ['success' => bool, 'message' => string]
     */
    public function verify(User $user, string $code, ?string $operationId = null): array
    {
        // Find the latest valid OTP for this user and operation
        $query = Otp::where('user_id', $user->id)
            ->where('is_used', false)
            ->where('expires_at', '>', now());

        if ($operationId) {
            $query->where('operation_id', $operationId);
        }

        $otp = $query->latest()->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'No valid OTP found. Please request a new one.',
            ];
        }

        // Check if max attempts exceeded
        if ($otp->attempts >= $this->maxAttempts) {
            return [
                'success' => false,
                'message' => 'Maximum attempts exceeded. Please request a new OTP.',
            ];
        }

        // Verify the OTP code
        if (!Hash::check($code, $otp->code)) {
            // Increment failed attempts
            $otp->increment('attempts');

            $remainingAttempts = $this->maxAttempts - $otp->attempts;

            if ($remainingAttempts <= 0) {
                return [
                    'success' => false,
                    'message' => 'Maximum attempts exceeded. Please request a new OTP.',
                ];
            }

            return [
                'success' => false,
                'message' => "Invalid OTP. {$remainingAttempts} attempt(s) remaining.",
            ];
        }

        // Mark OTP as used
        $otp->update(['is_used' => true]);

        return [
            'success' => true,
            'message' => 'OTP verified successfully.',
        ];
    }

    /**
     * Invalidate all previous OTPs for a user.
     *
     * @param User $user
     * @return void
     */
    protected function invalidatePreviousOtps(User $user): void
    {
        Otp::where('user_id', $user->id)
            ->where('is_used', false)
            ->update(['is_used' => true]);
    }

    /**
     * Generate a random 6-digit OTP.
     *
     * @return int
     */
    protected function generateRandomOtp(): int
    {
        return random_int(100000, 999999);
    }

    /**
     * Send OTP to user via email.
     *
     * @param User $user
     * @param int $plainOtp
     * @return void
     * @throws \Exception
     */
    protected function sendOtp(User $user, int $plainOtp): void
    {
        try {
            Mail::to($user->email)->send(new SendOtpMail($plainOtp, $this->expirationMinutes));
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to send OTP email. Please try again.');
        }
    }
}
