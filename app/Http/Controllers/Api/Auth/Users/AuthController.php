<?php

namespace App\Http\Controllers\Api\Auth\Users;

use App\Models\User;
use App\Models\Member;
use App\Models\Wallet;
use App\Models\UserTank;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\TokenWallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;


class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {

            $oldToken = $user->tokens();
            if ($oldToken)
                $oldToken->delete();

            return response()->json([
                'status' => true,
                'message' => 'login successfully ',
                'token' => ($user->createToken('user token'))->plainTextToken,
                'user' => $user
            ]);
        }
        return response()->json([
            'status' => false,
            'message' => 'The provided credentials are incorrect.'
        ], 400);
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'unique:users,username', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed'],
            'image' => ['nullable', 'image'],
            'sponsor_id' => ['required'],
            'pin_code' => ['required', 'digits:4'],
            'phone' => ['nullable', 'string', 'unique:users,phone'],
            'country' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Step 1: Create the user
            $userData = $validator->validated();
            $sponser_id = $userData['sponsor_id'];
            $userData['sponsor_id'] = User::where('id_code', $sponser_id)->whereHas('member')->pluck('id')->first();
            if (empty($userData['sponsor_id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Registration failed , incorrect sponsor id',
                ], 404);
            }
            $userData;
            $userData['password'] = bcrypt($userData['password']); // Hash password

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('image', 'public');
                $userData['image'] = URL::to(Storage::url($imagePath));
            }

            // create user
            $user = User::create($userData);

            // create hash and save pin for user
            $user->pin()->create([
                'pin_hash' => Hash::make($userData['pin_code']),
            ]);

            // Step 2: Add the user to the members table
            $member = Member::create([
                'user_id'       => $user->id,
                'sponsor_id'    =>  $userData['sponsor_id'],
                'rank_id'       => null, // Set default rank or handle as needed
            ]);

            // Step 3: Add the new member to the UserTank
            UserTank::create([
                'member_id' => $member->id,
                'sponsor_id' =>  $userData['sponsor_id'],
            ]);

            // Step 4: Create an empty wallet for the member
            Wallet::create([
                'member_id' => $member->id,
                'balance' => 0.00, // Initial balance is zero
            ]);
            TokenWallet::create([
                'member_id' => $member->id,
                'token_balance' => 0.00, // Initial balance is zero
            ]);

            DB::commit();
            // Return user data with access token and member info
            $user = array_merge($user->toArray(), [
                'token' => $user->createToken('user token')->plainTextToken,
                'member' => $member,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User registered successfully',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        return response()->json([
            'status' => true,
            'message' => 'user loged out successfuly'
        ]);
    }


    public function sponsorData($id)
    {
        $validator = Validator::make(['sponsor_id' => $id], [
            'sponsor_id' => ['required', 'exists:users,id_code'],
        ], [
            'sponsor_id.exists' => 'incorrect sponsor id'
        ]);
        if ($validator->fails())
            return $this->failedResponse($validator->errors(), 422);

        $sponser = User::where('id_code', $id)->whereHas('member')->pluck('id')->first();
        if (empty($sponser)) {
            return $this->failedResponse('Sponsor information is incorrect, or this user does not have a membership', 422);
        }
        $sponser = Member::where('user_id', $sponser)->first();
        $sponser_name = $sponser->user->username;
        return $this->successResponse('sponsor data get successfully', 'sponsor name', $sponser_name);
    }


    public function userProfile()
    {
        $user = (auth()->user());
        $user->load('member');
        $member = $user->member;
        $sponsor = $member ? $member->sponsor : null;
        $sponsorUser = $sponsor ? $sponsor->user : null;
        return response()->json([
            'status' => true,
            'message' => 'user data get successfully',
            'user data' => $user,
            'subscription' => $user->member?->subscription?->package?->name ?? 'no subscription',
            'sponsor' => $user->member->sponsor,
            'profile' => [
                'user_first_name'     => $user->first_name,
                'user_last_name'      => $user->last_name,
                'sponsor_name'        => $sponsorUser ? $sponsorUser->username : null,
                'sponsor_id_code'     => $sponsorUser ? $sponsorUser->id_code : null,
                'subscription'        => $member && $member->subscription ? $member->subscription->package->name : 'no subscription',
                'id_code'             => $user->id_code,
                'current_cv'          => $member ? $member->current_cv : 0,
                'total_left_leg_cv'   => $member ? $member->totla_left_volume : 0,
                'total_right_leg_cv'  => $member ? $member->totla_right_volume : 0,
                'status'              => $user->status,
                'phone'               => $user->phone
            ]
        ]);
    }

    public function profileById($id_code)
    {
        $user = auth()->user();
        $member = $user->member;

        $profileUser = User::where('id_code', $id_code)
            ->with('member')
            ->first();

        // ✅ FIRST: check if user exists
        if (!$profileUser) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        $user_id = $profileUser->id;

        // Check if self
        if ($id_code == $user->id_code) {
            return response()->json([
                'status' => false,
                'message' => 'It is your profile, you can view it from the profile tab'
            ]);
        }

        // Check if downline
        if (!$member->getAllDownlinesNetwork()
            ->contains('user_id', $user_id) && !$user->id_code == $id_code) {

            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to view this profile'
            ]);
        }


        $profileMember = $profileUser->member;
        $sponsorUser = $profileMember && $profileMember->sponsor
            ? $profileMember->sponsor->user
            : null;
        return response()->json([
            'status' => true,
            'message' => 'User data fetched successfully',
            'user' => $profileMember
        ]);
    }

    public function editUserProfile(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'username' => ['nullable', 'string', 'max:100'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'nullable',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'phone' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone')->ignore($user->id)
            ],
        ]);
        if ($validator->fails()) {
            return $this->failedResponse($validator->errors(), 422);
        }
        try {
            $request->username ? $user->username = $request->username : $user->username;
            $request->first_name ? $user->first_name = $request->first_name : $user->first_name;
            $request->last_name ? $user->last_name = $request->last_name : $user->last_name;
            $request->email ? $user->email = $request->email : $user->email;
            $request->phone ? $user->phone = $request->phone : $user->phone;
            $user->save();
            return $this->successResponse('user data updated successfully ', 'user', $user);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function deleteMyUser()
    {
        $user = auth()->user();
        try {
            $user->delete();
            return response()->json([
                'status' => true,
                'message' => 'user deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function activeUser()
    {
        $user = auth()->user();
        try {
            $user->status = 'active';
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'user status activated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function inactiveUser()
    {
        $user = auth()->user();
        try {
            $user->status = 'inactive';
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'user status inactivated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }




    public function generateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'email',
            'exists:users,email',
            'password' => 'required',
            'string',
        ]);
        if ($validator->fails()) {
            return $this->failedResponse($validator->errors(), 422);
        }
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $oldToken = $user->tokens();
            if ($oldToken)
                $oldToken->delete();
            $token = $user->createToken('SSO Token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'token generated successfully',
                'token' => $token,
                // 'userinfo'=> $userData,
                'test' => $user
            ]);
        }

        return $this->failedResponse('Invalid credentials', 422);
    }



    public function getUser()
    {
        $user = auth()->user();
        $user->load('member.subscription.package');
        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'package_name' => $user->member->subscription->package->name ?? null, //Handle potential null values
            'subscribed_at' => $user->member->subscription->subscribed_at,
            'expiration_date' => $user->member->subscription->expiration_date ?? null, // Assuming you add this field to your database
        ];

        return $this->successResponse(
            'user get succesffuky',
            'user',
            $userData
        );
    }




    public function syncUserToTradingSociety()
    {
        $user = Auth::user();
        $member = $user->member;

        $userData = [
            'name'                   => $user->username,
            'first_name'             => $user->first_name,
            'last_name'              => $user->last_name,
            'email'                  => $user->email,
            'phone_number'           => $user->phone ?? fake()->phoneNumber(),
            'package'                => $member->subscription->package->name ?? null,
            'subscripition_start_at' => $member->subscription->subscribed_at ?? null,
            'subscripition_end_at'   => $member->subscription->expiration_date ?? null,
        ];

        if ($userData['package'] === null) {
            return $this->failedResponse('The operation cannot be completed, Subscription data is incomplete');
        }
        if ($userData['subscripition_start_at'] === null) {
            return $this->failedResponse('The operation cannot be completed, Subscription data is incomplete');
        }
        if ($userData['subscripition_end_at'] === null) {
            return $this->failedResponse('The operation cannot be completed, Subscription data is incomplete');
        }

        return $response = Http::withHeaders(['Accept' => 'application/json'])
            ->post('https://laravelapi.tradingsociety.net/api/v1/sync/user', $userData);
        // ->post('http://127.0.0.1:5005/api/v1/sync/user', $userData);

        if ($response->status() === 422) {
            $errors = $response->json('message');
            return back()->withErrors($errors)->withInput();
        }


        if ($response->successful()) {
            $data = $response->json();

            return response()->json([
                'status' => true,
                'message' => 'User synced successfully',
                'token' => $data['token'] ?? null
            ], 200);
        }

        // If not successful, return an error message
        return back()->with('error', 'Failed to sync user. Please try again.');
    }
}
