<?php

namespace App\Http\Controllers\Api\Auth\Users;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Services\OtpService;

class ResetPasswordController extends Controller
{
    use ApiResponseTrait;
    // Look for User by email, phone, username or id_code and send OTP
    public function findAccount(Request $request)
    {
        $validatedData = $request->validate([
            'identifier' => ['required', 'string']
        ]);
        $user = User::where('email', $validatedData['identifier'])
            ->orWhere('phone', $validatedData['identifier'])
            ->orWhere('username', $validatedData['identifier'])
            ->orWhere('id_code', $validatedData['identifier'])
            ->first();

        if (!$user) {
            return $this->failedResponse(['identifier' => ['User not found']]);
        }

        $operationId = Str::uuid()->toString();
        // Generate OTP with operation binding
        $otpService = new OtpService();
        $otpService->generate($user, $operationId);
        return response()->json([
            'status'  => true,
            'message' => 'User found.',
            'operation_id' => $operationId
        ]);
    }


    // verify otp
    public function verifyOtp(Request $request)
{
    $request->validate([
        'operation_id' => 'required',
        'otp' => 'required'
    ]);

    $otpService = new OtpService();

    $user = $otpService->getUserByOperationId($request->operation_id);
    if (!$user) {
        return $this->failedResponse(['operation_id' => ['Invalid operation ID']]);
    }
    $result = $otpService->verify($user, $request->otp, $request->operation_id);
    if (!$result['success']) {
        return $this->failedResponse(['otp' => ['Invalid or expired OTP']]);
    }

    return response()->json([
        'status' => true,
        'message' => 'OTP verified'
    ]);
}
    // reset password
    public function resetPassword(Request $request)
    {
        $validatedData = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'operation_id' => ['required']
        ]);

        $otpService = new OtpService();
        $user = $otpService->getUserByOperationId($validatedData['operation_id']);
        if (!$user) {
            return $this->failedResponse(['operation_id' => ['Invalid operation ID']]);
        }
        $user->password = Hash::make($validatedData['password']);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully',
        ]);
    }




    // Handle password reset
    public function reset(Request $request)
    {
        $user = Auth::user();

        // Validate fields
        $validator = Validator::make($request->all(), [
            'old_password' => ['required', 'string'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->failedResponse($validator->errors());
        }

        // Check if old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->failedResponse(['old_password' => ['Old password is incorrect']]);
        }

        // Update user password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
