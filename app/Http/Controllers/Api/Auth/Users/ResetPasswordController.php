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

class ResetPasswordController extends Controller
{
    use ApiResponseTrait;
    // Send password reset link
    public function findAccount(Request $request)
    {
        $user = User::where('email', $request->email)
                      ->orWhere('phone', $request->phone)
                      ->orWhere('username', $request->username)
                      ->orWhere('id_code', $request->id_code)
                      ->first();

        if (!$user) {
            return $this->failedResponse(['identifier' => ['User not found']]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'User found.',
            'user' => $user
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
