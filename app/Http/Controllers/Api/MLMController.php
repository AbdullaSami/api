<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Member;
use App\Models\Wallet;
use App\Models\UserTank;
use App\Models\Commission;
use App\Models\CommissionFactor;
use App\Traits\ApiResponseTrait;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceReferralRequest;
use App\Http\Controllers\Api\WalletController;
use App\Models\Referal;

class MLMController extends Controller
{
    use ApiResponseTrait;

    // abdulla edits start here
    public function placeReferral(PlaceReferralRequest $request)
    {
        $sponsor     = auth()->user()->member;
        $referral    = Member::findOrFail($request->referral_id);

        // Validate referral before processing
        $validationError = $this->validateReferralPlacement($sponsor, $referral);
        if ($validationError) {
            return $this->failedResponse($validationError, 402);
        }

        // Get referral CV (must have subscription)
        $subscription   = $referral->subscription;
        $packageCv      = $subscription->package->cv;

        // Determine where to place referral (left or right)
        $placementNode = $this->resolvePlacementNode($sponsor, $referral, $request->placement);
        if ($placementNode instanceof JsonResponse) {
            return $placementNode; // error response
        }

        // Apply the placement
        $this->applyPlacement($sponsor, $placementNode, $referral, $request->placement, $packageCv);

        $uplines               = $referral->getAllTreeUplines();

        DB::beginTransaction();

        try {

            // Remove referral from tank if exists
            UserTank::where('member_id', $referral->id)->delete();

            // Process uplines commissions (only for first-time placement)
            if ($referral->is_first === 'yes') {
                $this->processUplinesCommission(
                    $uplines,
                    $sponsor,
                    $referral,
                    $packageCv
                );

                Referal::create([
                    'sponsor_id'  => $sponsor->id,
                    'referral_id' => $referral->id,
                    'leg'         => $request->placement,
                ]);

                $referral->is_first = 'no';
                $referral->save();
            }

            // re-save sponsor after updates
            $sponsor->save();

            // upgrade sponsor rank if eligible
            $sponsor->upgradeRank();

            DB::commit();

            return $this->successResponse(
                "Referral '{$referral->user->name}' added under sponsor '{$sponsor->user->name}' in the {$request->placement} leg.",
                'sponsor',
                $sponsor
            );
        } catch (\Exception $e) {

            DB::rollBack();
            return $this->failedResponse('Process failed: ' . $e->getMessage(), 500);
        }
    }

    private function validateReferralPlacement($sponsor, $referral)
    {
        if (!$referral->subscription) {
            return 'This referral is not subscribed to any package.';
        }

        // Block if referral is invalid or same as sponsor
        if (in_array($referral->id, [1, $sponsor->id, $sponsor->left_leg_id, $sponsor->right_leg_id])) {
            return config('consts.REFERRAL_BLOCK_MESSAGE', 'This process cannot be completed.');
        }

        // Block if already in far left/right chain
        if (
            $referral->id == $this->findFarLeft($sponsor)->id ||
            $referral->id == $this->findFarRight($sponsor)->id
        ) {
            return 'Referral already exists on far left or far right.';
        }

        // Referral must belong to current sponsor
        if ($referral->sponsor->id !== $sponsor->id) {
            return 'This referral belongs to another sponsor.';
        }

        return null;
    }
    private function resolvePlacementNode($sponsor, $referral, $placement)
    {
        if (!in_array($placement, ['left', 'right'])) {
            return $this->failedResponse('Invalid placement request.', 400);
        }

        if ($placement === 'left') {
            return $sponsor->left_leg_id
                ? $this->findFarLeft($sponsor)
                : $sponsor;
        }

        if ($placement === 'right') {
            return $sponsor->right_leg_id
                ? $this->findFarRight($sponsor)
                : $sponsor;
        }
    }
    private function applyPlacement($sponsor, $placementNode, $referral, $placement, $packageCv)
    {
        if ($placementNode->id === $sponsor->id) {

            // Place directly under sponsor
            if ($placement === 'left') {
                $sponsor->left_leg_id      = $referral->id;
                $sponsor->totla_left_volume += $packageCv;
            } else {
                $sponsor->right_leg_id      = $referral->id;
                $sponsor->totla_right_volume += $packageCv;
            }

            return;
        }

        // Place deeper in the tree
        if ($placement === 'left') {
            $placementNode->left_leg_id = $referral->id;
        } else {
            $placementNode->right_leg_id = $referral->id;
        }

        $placementNode->save();
    }
    private function processUplinesCommission($uplines, $directSponsor, $referral, $packageCv)
    {
        foreach ($uplines as $upline) {

            // Skip the direct sponsor for binary commission
            if ($upline->id === $directSponsor->id) {
                continue;
            }

            $referralId = $referral->id;

            // Check which leg the referral belongs to
            $belongsLeft  = $upline->left_leg_id == $referralId ||
                in_array($referralId, $this->getLegMembers($upline->left_leg_id));

            $belongsRight = $upline->right_leg_id == $referralId ||
                in_array($referralId, $this->getLegMembers($upline->right_leg_id));

            if ($belongsLeft) {
                $upline->totla_left_volume += $packageCv;
            }

            if ($belongsRight) {
                $upline->totla_right_volume += $packageCv;
            }

            $upline->current_cv += $packageCv;
            $upline->save();
        }
    }

    // end abdulla edits
    private function getLegMembers($legId)
    {
        if (!$legId) {
            return [];
        }

        $members = [];
        $queue = [$legId]; // Start with the root member of the leg

        while (!empty($queue)) {
            $currentMemberId = array_shift($queue);
            $members[] = $currentMemberId;

            $currentMember = Member::find($currentMemberId);
            if ($currentMember) {
                // Add left and right leg IDs to the queue
                if ($currentMember->left_leg_id) {
                    $queue[] = $currentMember->left_leg_id;
                }
                if ($currentMember->right_leg_id) {
                    $queue[] = $currentMember->right_leg_id;
                }
            }
        }

        return $members;
    }

    public function getDownlineMembers()
    {
        $user = auth()->user();

        $member = $user->member;

        $leftLegMembers = [];
        $rightLegMembers = [];

        $queue = [];

        if ($member->leftLeg) {
            $queue[] = ['member' => $member->leftLeg, 'side' => 'left'];
        }

        if ($member->rightLeg) {
            $queue[] = ['member' => $member->rightLeg, 'side' => 'right'];
        }

        while (!empty($queue)) {
            $current = array_shift($queue);
            $currentMember = $current['member'];
            $currentSide = $current['side'];

            if ($currentMember) {
                if ($currentSide === 'left') {
                    $leftLegMembers[] = $currentMember;
                } else {
                    $rightLegMembers[] = $currentMember;
                }

                if ($currentMember->leftLeg) {
                    $queue[] = ['member' => $currentMember->leftLeg, 'side' => 'left'];
                }
                if ($currentMember->rightLeg) {
                    $queue[] = ['member' => $currentMember->rightLeg, 'side' => 'right'];
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Downline members retrieved successfully.',
            'data' => [
                'leftLeg' => $leftLegMembers,
                'rightLeg' => $rightLegMembers,
            ],
        ], 200);
    }

    public function getLeftDownlineMembers()
    {
        $user = auth()->user();
        $member = $user->member;
        $leftDownlineMembers = [];
        $queue = [$member->leftLeg];
        while (!empty($queue)) {
            $currentMember = array_shift($queue);

            if ($currentMember) {
                $leftDownlineMembers[] = $currentMember;

                if ($currentMember->leftLeg) {
                    $queue[] = $currentMember->leftLeg;
                }
                if ($currentMember->rightLeg) {
                    $queue[] = $currentMember->rightLeg;
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Left downline members retrieved successfully.',
            'data' => $leftDownlineMembers,
        ], 200);
    }



    public function getRightDownlineMembers()
    {
        $user = auth()->user();
        $member = $user->member;
        $rightDownlineMembers = [];
        $queue = [$member->rightLeg];
        while (!empty($queue)) {
            $currentMember = array_shift($queue);

            if ($currentMember) {
                $rightDownlineMembers[] = $currentMember;

                if ($currentMember->rightLeg) {
                    $queue[] = $currentMember->rightLeg;
                }
                if ($currentMember->rightLeg) {
                    $queue[] = $currentMember->leftLeg;
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'right downline members retrieved successfully.',
            'data' => $rightDownlineMembers,
        ], 200);
    }

    /**
     * Returns the number of downlines on the left and right legs.
     */
    public function getDownlineCounts()
    {
        $user = auth()->user();
        $member = $user->member;
        $data = [];
        $data['left_downlines_count'] = $member->countLeftDownline();
        $data['right_downlines_count'] = $member->countRightDownline();
        return $this->successResponse('done successfully', 'count', $data);
    }

    /**
     * Returns the network volume on the left and right legs.
     */
    public function getNetworkVolume()
    {
        $user = auth()->user();
        $member = $user->member;
        $data['left_leg_volume'] = $member->totla_left_volume;
        $data['right_leg_volume'] = $member->totla_right_volume;
        return response()->json([
            'status' => true,
            'maessage' => 'all network valoum get successfully ',
            'notwork_voluum' => $data,
        ]);
    }

    /**
     * Calculates the direct commission for a member and returns it.
     */
    public function calculateDirectCommission()
    {
        $user = auth()->user();
        $member = $user->member;
        $directCommission = $member->calculateDirectCommission();
        return response()->json([
            'direct_commission' => $directCommission,
            'balance' => $member->balance,
        ]);
    }

    public function mtTank()
    {
        $user = auth()->user();

        // Assuming the user has a single member
        $member = $user->member;

        // Check if the member is not found
        if (!$member) {
            return response()->json('Member not found', 404);
        }

        // Fetch only the necessary fields
        $tanks = UserTank::where('sponsor_id', $member->id)
            ->with([
                'member.user:id,username,first_name,last_name',
                'member.subscription.package:id,name'
            ]) // Eager load the user from the member and the package from the subscription
            ->paginate(5);

        // Append the member name and package to each tank
        $tanks->getCollection()->transform(function ($tank) {
            // Use optional chaining to safely access the member's user and subscription's package
            $tank->member_username  = optional($tank->member->user)->username; // Get the member's user's name if exists
            $tank->member_firstname = optional($tank->member->user)->first_name; // Get the member's user's name if exists
            $tank->member_lastname = optional($tank->member->user)->last_name; // Get the member's user's name if exists
            $tank->member_package = optional($tank->member->subscription)->package; // Get the member's user's name if exists

            // Check if member and subscription are not null
            if ($tank->member && $tank->member->subscription) {
                $tank->member_package = $tank->member->subscription->package ? $tank->member->subscription->package->name : null; // Safely accessing package name
            } else {
                $tank->member_package = null; // Default to null if subscription is not present
            }

            unset($tank->member); // Optionally remove the full member object if you don’t want it
            return $tank;
        });

        return $this->successResponse('Tanks retrieved successfully', 'tank', $tanks);
    }

    public function getDirectDownlineMembers()
    {
        $user = auth()->user();
        $member = $user->member;
        if ($member->leftLeg && $member->rightLeg) {

            $data = [
                'left_leg_member' => [
                    'id' => $member->leftLeg->id,
                    'rank' => $member->leftLeg->rank,
                    'user_name' => $member->leftLeg->user->name,
                    'user_image' => $member->leftLeg->user->image,
                ],
                'right_leg_member' => [
                    'id' => $member->rightLeg->id,
                    'rank' => $member->rightLeg->rank,
                    'user_name' => $member->rightLeg->user->name,
                    'user_image' => $member->rightLeg->user->image,
                ]
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        } elseif ($member->leftLeg && !$member->rightLeg) {
            $data = [
                'left_leg_member' => [
                    'id' => $member->leftLeg->id,
                    'rank' => $member->leftLeg->rank,
                    'user_name' => $member->leftLeg->user->name,
                    'user_image' => $member->leftLeg->user->image,
                ],
                'right_leg_member' => [
                    'this leg is empty right now'
                ]
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        } elseif (!$member->leftLeg && $member->rightLeg) {
            $data = [
                'left_leg_member' => [
                    'this leg is empty right now'
                ],
                'right_leg_member' => [
                    'id' => $member->rightLeg->id,
                    'rank' => $member->rightLeg->rank,
                    'user_name' => $member->rightLeg->user->name,
                    'user_image' => $member->rightLeg->user->image,
                ]
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        }
        return $this->failedResponse('no downlines members to this user');
    }

    public function getDirectDownlineMembersById($id)
    {
        $user = User::findOrFail($id);
        $member = $user->member;
        if ($member->leftLeg && $member->rightLeg) {

            $data = [
                'left_leg_member' => [
                    'id'                => $member->leftLeg->id,
                    'rank_id'           => $member->leftLeg->rank->id,
                    'rank_name'         => $member->leftLeg->rank->name,
                    'user_name'         => $member->leftLeg->user->username,
                    'user_id_code'      => $member->leftLeg->user->id_code,
                    'user_first_name'   => $member->leftLeg->user->first_name,
                    'user_last_name'    => $member->leftLeg->user->last_name,
                    'user_image'        => $member->leftLeg->user->image,
                ],
                'right_leg_member' => [
                    'id'                => $member->rightLeg->id,
                    'rank_id'           => $member->rightLeg->rank->id,
                    'rank_name'         => $member->rightLeg->rank->name,
                    'user_name'         => $member->rightLeg->user->username,
                    'user_id_code'      => $member->rightLeg->user->id_code,
                    'user_first_name'   => $member->rightLeg->user->first_name,
                    'user_last_name'    => $member->rightLeg->user->last_name,
                    'user_image'        => $member->rightLeg->user->image,
                ]
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        } elseif ($member->leftLeg && !$member->rightLeg) {
            $data = [
                'left_leg_member' => [
                    'id'                => $member->leftLeg->id,
                    'rank_id'           => $member->leftLeg->rank->id,
                    'rank_name'         => $member->leftLeg->rank->name,
                    'user_name'         => $member->leftLeg->user->username,
                    'user_id_code'      => $member->leftLeg->user->id_code,
                    'user_first_name'   => $member->leftLeg->user->first_name,
                    'user_last_name'    => $member->leftLeg->user->last_name,
                    'user_image'        => $member->leftLeg->user->image,
                ],
                'right_leg_member' => null
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        } elseif (!$member->leftLeg && $member->rightLeg) {
            $data = [
                'left_leg_member' => null,
                'right_leg_member' => [
                    'id'                => $member->rightLeg->id,
                    'rank_id'           => $member->rightLeg->rank->id,
                    'rank_name'         => $member->rightLeg->rank->name,
                    'user_name'         => $member->rightLeg->user->username,
                    'user_id_code'      => $member->rightLeg->user->id_code,
                    'user_first_name'   => $member->rightLeg->user->first_name,
                    'user_last_name'    => $member->rightLeg->user->last_name,
                    'user_image'        => $member->rightLeg->user->image,
                ]
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        }
        return response()->json('no downlines members to this user');
    }

    private function findFarLeft(Member $member)
    {
        $visited = []; // Keep track of visited nodes
        while ($member->left_leg_id) {
            if (in_array($member->id, $visited)) {
                throw new \Exception('Cycle detected at member ID: ' . $member->id .
                    ', Name: ' . $member->name .
                    ', Email: ' . $member->email);
            }
            $visited[] = $member->id; // Mark this member as visited
            $member = Member::find($member->left_leg_id);
        }
        return $member;
    }

    private function findFarRight(Member $member)
    {
        $visited = []; // Keep track of visited nodes
        while ($member->right_leg_id) {
            if (in_array($member->id, $visited)) {
                throw new \Exception('Cycle detected at member ID: ' . $member->id .
                    ', Name: ' . $member->name .
                    ', Email: ' . $member->email);
            }
            $visited[] = $member->id; // Mark this member as visited
            $member = Member::find($member->right_leg_id);
        }
        return $member;
    }

    /**
     * Get the downline details for a member.
     *
     * @param int $memberId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDownlineDetails()
    {
        try {
            $user = auth()->user();
            $member = $user->member->load('rank');

            if (!$member) {
                return response()->json(['message' => 'Member not found'], 404);
            }

            $downlineDetails = $member->getDownlineDetailsByRank();

            return response()->json([
                'member_id' => $member->id,
                'downline_details' => $downlineDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while retrieving downline details.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getYearlySales()
    {
        $user = auth()->user();

        // Validate if the authenticated user exists
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $member = $user->member;

        // Validate if the user is linked to a member
        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'User does not have an associated membership'
            ], 404);
        }

        try {
            // Get all downline IDs
            $downlines = $member->getAllDownlines;

            // Call the collectDownlineIds method to get all IDs
            $downlineIds = $this->collectDownlineIds($downlines);

            // Calculate monthly and weekly sales
            $monthlySales = $this->calculateMonthlySales($downlineIds);
            $weeklySales = $this->calculateWeeklySales($downlineIds);

            // Return success response
            return response()->json([
                'status' => true,
                'message' => 'Sales data retrieved successfully.',
                'data' => [
                    'monthly_sales' => $monthlySales,
                    'weekly_sales' => $weeklySales,
                ],
            ]);
        } catch (\Exception $e) {
            // Handle errors and return response
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while retrieving sales data.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function collectDownlineIds($downlines)
    {
        $ids = [];

        foreach ($downlines as $downline) {
            $ids[] = $downline->id;

            if ($downline->getAllDownlines->isNotEmpty()) {
                $ids = array_merge($ids, $this->collectDownlineIds($downline->getAllDownlines));
            }
        }
        return array_values(array_unique($ids));
    }

    private function calculateMonthlySales(array $downlineIds)
    {
        $monthlySales = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::createFromFormat('m', $month)->format('F');

            // Calculate the total amount for the specified month and year
            $monthlySales[$monthName] = WalletTransaction::whereHas('wallet', function ($query) use ($downlineIds) {
                $query->whereIn('member_id', $downlineIds);
            })
                ->where('transaction_type', 'buy_package')
                ->where('status', 'accepted')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
        }

        return $monthlySales;
    }

    /**
     * Calculate weekly sales for a member's downline.
     */
    private function calculateWeeklySales(array $downlineIds)
    {
        $weeklySales = [];

        for ($week = 1; $week <= 52; $week++) {
            $weeklySales[$week] = WalletTransaction::whereHas('wallet', function ($query) use ($downlineIds) {
                $query->whereIn('member_id', $downlineIds);
            })
                ->where('transaction_type', 'buy_package')
                ->where('status', 'accepted')
                ->whereRaw('WEEK(created_at) = ?', [$week])
                ->whereYear('created_at', now()->year)
                ->sum('amount');
        }

        return $weeklySales;
    }
}
