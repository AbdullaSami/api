<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use App\Models\CommissionFactor;
use App\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    use ApiResponseTrait;



    public function index()
    {
        $users = User::paginate(5);
        return $this->successResponse('all users get successfully', 'users', $users);
    }



    public function usersWithMembership()
    {
        $users = User::paginate(5);
        $users->load('member');
        return $this->successResponse('all users get successfully', 'users', $users);
    }


    public function editUser(Request $request, $user_id)
    {
        $user = User::find($user_id);
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'unique:users,phone,' . $user->id],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }
        try {
            $user->update([
                'name' => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
                'phone' => $request->phone ?? $user->phone
            ]);
            return $this->successResponse('user data updated successfully', 'user', $user);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }
    public function activeUser(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);
        $validator = Validator::make($request->all(), [
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }
        try {
            $user->update([
                'status' => $request->status ?? $user->status
            ]);
            return $this->successResponse('user status updated successfully', 'user', $user);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function deleteUser($user_id)
    {
        $user = User::find($user_id);

        try {
            $user->delete();
            return response()->json([
                'status' => true,
                "message" => 'user deleted  successfully'
            ]);
        } catch (\Exception $e) {
            return $this->failedResponse($e);
        }
    }

    public function generateDownlineReport(Request $request, $memberId)
    {
        // Validate input for the date range
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>  $validator->errors()
            ], 422);
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Fetch the main member
        $member = Member::findOrFail($memberId);

        // Fetch all direct referrals
        $directReferrals = $member->downlines()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Fetch all downlines (direct + indirect)
        $allDownlines = $this->getAllDownlines($member);

        // Filter indirect referrals
        $indirectReferrals = $allDownlines->filter(function ($downline) use ($directReferrals) {
            return !$directReferrals->contains($downline);
        });

        // Calculate totals for direct referrals
        $directTotalCV = $directReferrals->sum('current_cv');
        $rate = (CommissionFactor::where('package_id', $member->subscription->package_id)->first())->direct_rate;
        $totalDirectCommissionCv = ($directTotalCV * $rate) / 100;
        $directCount = $directReferrals->count();
        $highestDirectCV = $directReferrals->max('current_cv');

        // Calculate totals for indirect referrals
        $indirectTotalCV = $indirectReferrals->sum('current_cv');
        $rate = (CommissionFactor::where('package_id', $member->subscription->package_id)->first())->binary_rate;
        $totalINdirectCommissionCv = ($indirectTotalCV * $rate) / 100;
        $indirectCount = $indirectReferrals->count();
        $highestIndirectCV = $indirectReferrals->max('current_cv');

        // Prepare the report data
        $reportData = [
            'member_name' => $member->user->name,
            'direct_referrals' => [
                'count' => $directCount,
                'total_referrals _cv' => $directTotalCV,
                'total_commissions_cv' => $totalDirectCommissionCv,
                'highest_cv' => $highestDirectCV,
            ],
            'indirect_referrals' => [
                'count' => $indirectCount,
                'total_referrals _cv' => $indirectTotalCV,
                'total_commissions_cv' => $totalINdirectCommissionCv,
                'highest_cv' => $highestIndirectCV,
            ],
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $reportData,
        ]);
    }

    /**
     * Recursive function to fetch all downlines (direct and indirect).
     */
    private function getAllDownlines(Member $member, $downlines = null)
    {
        if ($downlines === null) {
            $downlines = collect();
        }

        // Get direct downlines for the current member
        $directDownlines = $member->downlines()->get();

        // Add them to the collection
        $downlines = $downlines->merge($directDownlines);

        // Recursively get indirect downlines
        foreach ($directDownlines as $downline) {
            $downlines = $this->getAllDownlines($downline, $downlines);
        }

        return $downlines;
    }
}
