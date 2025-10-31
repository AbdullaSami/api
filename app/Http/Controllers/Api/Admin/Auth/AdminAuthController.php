<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{



    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:admins,email'],
            'password' => ['required', 'string']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }

        $admin = Admin::where('email', $request->email)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {

            $oldToken = $admin->tokens();
            if ($oldToken)
                $oldToken->delete();

            return response()->json([
                'status' => true,
                'message' => 'login successfully ',
                'token' => ($admin->createToken('Admin token'))->plainTextToken,
                'admin' => $admin
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'The provided credentials are incorrect.'
        ], 400);
    }




    public function logout(Request $request)
    {
        $admin = $request->user();
        $admin->tokens()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Admin loged out successfuly'
        ]);
    }
}
