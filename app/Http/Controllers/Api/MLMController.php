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
use App\Models\CvCommission;
use App\Models\Referal;
use App\Models\Rank;

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
        $packageId      = $subscription->package->id;

        // Determine where to place referral (left or right)
        $placementNode = $this->resolvePlacementNode($sponsor, $referral, $request->placement);
        if ($placementNode instanceof JsonResponse) {
            return $placementNode; // error response
        }

        // Apply the placement
        $this->applyPlacement($sponsor, $packageId, $placementNode, $referral, $request->placement, $packageCv);


        DB::beginTransaction();

        $uplines = $referral->getAllTreeUplines();

        try {
            // Remove referral from tank if exists
            UserTank::where('member_id', $referral->id)->delete();

            // Process uplines commissions (only for first-time placement)
            if ($referral->is_first === 'yes') {
                $this->processUplinesCommission(
                    $uplines,
                    $sponsor,
                    $packageId,
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
            } else {
                $this->applyIndirectCV($sponsor->id);
            }

            $sponsor->current_cv += $packageCv;
            // re-save sponsor after updates
            $sponsor->save();

            // upgrade sponsor rank if eligible
            $sponsor->upgradeRank();

            // Apply indirect CV to all uplines
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
    private function applyPlacement($sponsor, $packageId, $placementNode, $referral, $placement, $packageCv)
    {
        $commissionFactor  = CommissionFactor::first();
        $binaryRate = $commissionFactor->binary_rate;

        \Log::info("//////////////////////////////////////////////////////////////");
        \Log::info("at applyPlacement function");

        if ($placementNode->id === $sponsor->id) {

            if ($placement === 'left') {
                $sponsor->left_leg_id = $referral->id;
                $sponsor->totla_left_volume += $packageCv;

                CvCommission::create([
                    'member_id' => $sponsor->id,
                    'package_id' => $packageId,
                    'side' => 'left',
                    'amount' => $packageCv,
                ]);
            } else {

                $sponsor->right_leg_id = $referral->id;
                $sponsor->totla_right_volume += $packageCv;

                CvCommission::create([
                    'member_id' => $sponsor->id,
                    'package_id' => $packageId,
                    'side' => 'right',
                    'amount' => $packageCv,
                ]);
            }

            // -------- Binary Commission Logic --------

            $left  = $sponsor->totla_left_volume;
            $right = $sponsor->totla_right_volume;

            $matchedVolume = min($left, $right);

            if ($matchedVolume > 0) {

                $commissionValue = ($matchedVolume * $binaryRate) / 100;

                Commission::create([
                    'sponsor_id'        => $sponsor->id,
                    'referral_id'       => $referral->id,
                    'commission_value'  => $commissionValue,
                    'commission_type'   => 'binary',
                ]);

                \Log::info("Binary commission created for sponsor {$sponsor->id}. Matched CV: {$matchedVolume}");

                // Deduct used CV
                $sponsor->totla_left_volume  -= $matchedVolume;
                $sponsor->totla_right_volume -= $matchedVolume;
                \Log::info("After binary commission deduction for sponsor {$sponsor->id} → Left: {$sponsor->totla_left_volume}, Right: {$sponsor->totla_right_volume}");
            }

            $sponsor->save();
        }

        if ($placement === 'left') {
            $placementNode->left_leg_id = $referral->id;
        } else {
            $placementNode->right_leg_id = $referral->id;
        }

        $placementNode->save();
    }
    private function applyIndirectCV($memberId)
    {
        try {
            $member = Member::find($memberId);
            $package = $member->subscription->package;
            while ($member->sponsor) {
                $referal = $member->directSponsor;
                $sponsor = $referal->sponsorMember;
                // \Log::info(" before: current cv: {$sponsor->current_cv}, left leg: {$sponsor->totla_left_volume}, right leg: {$sponsor->totla_right_volume}");
                if ($referal->leg == 'left') {
                    $sponsor->totla_left_volume += $package->cv;
                    CvCommission::create([
                        'member_id' => $sponsor->id,
                        'package_id' => $package->id,
                        'side' => 'left',
                        'amount' => $package->cv,
                    ]);
                } else {
                    $sponsor->totla_right_volume += $package->cv;
                    CvCommission::create([
                        'member_id' => $sponsor->id,
                        'package_id' => $package->id,
                        'side' => 'right',
                        'amount' => $package->cv,
                    ]);
                }
                $sponsor->current_cv += $package->cv;
                $sponsor->save();
                // \Log::info(" after: current cv: {$sponsor->current_cv}, left leg: {$sponsor->totla_left_volume}, right leg: {$sponsor->totla_right_volume}");
                $member = $sponsor;
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error applying indirect CV: ' . $e->getMessage());
        }
    }
    private function processUplinesCommission($uplines, $directSponsor, $packageId, $referral, $packageCv)
    {
        \Log::info("//////////////////////////////////////////////////////////////");
        \Log::info("at processUplinesCommission function");

        // Remove duplicate uplines
        $uplines = collect($uplines)->unique('id')->values();

        foreach ($uplines as $upline) {

            $referralId = $referral->id;

            // Detect which leg the referral belongs to
            $belongsLeft  = $upline->left_leg_id == $referralId ||
                in_array($referralId, $this->getLegMembers($upline->left_leg_id));

            $belongsRight = $upline->right_leg_id == $referralId ||
                in_array($referralId, $this->getLegMembers($upline->right_leg_id));

            // LEFT LEG
            if ($belongsLeft) {

                \Log::info("Adding {$packageCv} CV to LEFT leg of upline ID: {$upline->id}");

                CvCommission::create([
                    'member_id' => $upline->id,
                    'package_id' => $packageId,
                    'side'       => 'left',
                    'amount'     => $packageCv,
                ]);

                \Log::info("Indirect CvCommission created for upline ID: {$upline->id} on LEFT leg");

                // Run binary commission logic
                $this->processBinaryCommission(
                    $upline,
                    $packageCv,
                    'left',
                    $referralId
                );
            }

            // RIGHT LEG
            if ($belongsRight) {

                \Log::info("Adding {$packageCv} CV to RIGHT leg of upline ID: {$upline->id}");

                CvCommission::create([
                    'member_id' => $upline->id,
                    'package_id' => $packageId,
                    'side'       => 'right',
                    'amount'     => $packageCv,
                ]);

                \Log::info("Indirect CvCommission created for upline ID: {$upline->id} on RIGHT leg");

                // Run binary commission logic
                $this->processBinaryCommission(
                    $upline,
                    $packageCv,
                    'right',
                    $referralId
                );
            }

            // Track total CV for ranking / stats
            $upline->current_cv += $packageCv;
            $upline->save();
        }
    }

    //8-march-2026
    private function processBinaryCommission($member, $packageCv, $leg, $referralId)
    {
        $commissionFactor = CommissionFactor::first();
        $binaryRate = $commissionFactor->binary_rate;

        // 1️⃣ Add CV to the correct leg
        if ($leg === 'left') {
            $member->totla_left_volume += $packageCv;
        } else {
            $member->totla_right_volume += $packageCv;
        }

        if ($member->totla_left_volume == 0 || $member->totla_right_volume == 0) {
            \Log::info("No CV in either leg for member {$member->id}. Skipping binary commission.");
            return;
        }

        \Log::info("Added {$packageCv} CV to {$leg} leg of member {$member->id}");

        // 2️⃣ Calculate matched volume (weaker leg)
        $left  = $member->totla_left_volume;
        $right = $member->totla_right_volume;

        $matchedVolume = min($left, $right);

        if ($matchedVolume <= 0) {
            $member->save();
            return;
        }

        // 3️⃣ Calculate commission
        $commissionValue = ($matchedVolume * $binaryRate) / 100;

        Commission::create([
            'sponsor_id'       => $member->id,
            'referral_id'      => $referralId,
            'commission_value' => $commissionValue,
            'commission_type'  => 'binary',
        ]);

        \Log::info("Binary commission {$commissionValue} created for member {$member->id} using {$matchedVolume} CV");

        // 4️⃣ Deduct matched CV from both legs
        $member->totla_left_volume  -= $matchedVolume;
        $member->totla_right_volume -= $matchedVolume;

        \Log::info("After deduction for {$member->id} → Left: {$member->totla_left_volume}, Right: {$member->totla_right_volume}");

        $member->save();
    }

    // New function to get all member's dashboard data in one call (17-march-2026)
    public function dashboardData()
    {
        $user = auth()->user();
        $member = $user->member->load('rank');

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        // get downline details by rank
        $down_lineDetails = $member->getDownlineDetailsByRank();

        // get downline counts for left and right legs
        $downline = [];
        $downline['left_downlines_count'] = $member->countLeftDownline();
        $downline['right_downlines_count'] = $member->countRightDownline();
        // filter cv commissions based on time period (weekly, monthly, yearly)
        // get current cv counts for left and right legs
        $nowCvCounts = [];
        $nowCvCounts['left_cv_count'] = $member->leftSideCvCommissions()->sum('amount');
        $nowCvCounts['right_cv_count'] = $member->rightSideCvCommissions()->sum('amount');

        // get cv counts for left and right legs in last 7 days
        $last7DaysCvCounts = [];
        $last7DaysCvCounts['left_cv_count'] = $member->leftSideCvCommissions()->where('created_at', '>=', Carbon::now()->subDays(7))->sum('amount');
        $last7DaysCvCounts['right_cv_count'] = $member->rightSideCvCommissions()->where('created_at', '>=', Carbon::now()->subDays(7))->sum('amount');

        // get cv counts for left and right legs in last 30 days
        $last30DaysCvCounts = [];
        $last30DaysCvCounts['left_cv_count'] = $member->leftSideCvCommissions()->where('created_at', '>=', Carbon::now()->subDays(30))->sum('amount');
        $last30DaysCvCounts['right_cv_count'] = $member->rightSideCvCommissions()->where('created_at', '>=', Carbon::now()->subDays(30))->sum('amount');

        // get cv counts for left and right legs in last year
        $lastYearCvCounts = [];
        $lastYearCvCounts['left_cv_count'] = $member->leftSideCvCommissions()->where('created_at', '>=', Carbon::now()->subDays(365))->sum('amount');
        $lastYearCvCounts['right_cv_count'] = $member->rightSideCvCommissions()->where('created_at', '>=', Carbon::now()->subDays(365))->sum('amount');

        // total cv counts of left and right legs
        $totalCvCounts = $member->leftSideCvCommissions()->sum('amount') + $member->rightSideCvCommissions()->sum('amount');

        //get rank details
        $rank = $member->rank;

        if($rank){
            $nextRank = Rank::where('id', '>', $rank->id)->orderBy('id')->first();
        }else{
            $nextRank = Rank::orderBy('id')->first();
        }

        $remainingDays = null;
        $memberSubscription = $member->subscription;
        if ($memberSubscription && $memberSubscription->expiration_date) {
            // Use Carbon::parse to ensure we have a Carbon instance and avoid magic property type issues
            $remainingDays = \Illuminate\Support\Carbon::parse(now())->diffInDays($memberSubscription->expiration_date);
        }

        // get yearly sales in weeks
        $currentYear = now()->year;

        // Weekly Earnings
        $weeklyEarnings = $member->commission()
            ->whereYear('created_at', $currentYear)
            ->selectRaw('WEEK(created_at) as week, SUM(commission_value) as total')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Generate full 52-week report
        $fullWeeklyEarnings = collect(range(1, 52))->map(function ($week) use ($weeklyEarnings) {
            $weekData = $weeklyEarnings->firstWhere('week', $week);
            return [
                'week' => $week,
                'total' => $weekData ? $weekData->total : 0,
            ];
        });

        // Monthly Earnings (by month number -> total)
        $rawMonthly = $member->commission()
            ->whereYear('created_at', $currentYear)
            ->selectRaw('MONTH(created_at) as month, SUM(commission_value) as total')
            ->groupBy('month')
            ->pluck('total', 'month'); // [1 => 100, 3 => 250, ...]

        // Generate full 12-month report with names
        $monthlyEarnings = collect(range(1, 12))->map(function ($monthNumber) use ($rawMonthly) {
            return [
                'month' => \Carbon\Carbon::create()->month($monthNumber)->format('F'),
                'total' => $rawMonthly[$monthNumber] ?? 0,
            ];
        });

        // member subscription package details
        $packageDetails = $member->subscription()->with('package:id,name,pack_card')->first();
        // total commissions earned
        $totalEarnings = $member->commission->sum('commission_value');
        return response()->json([
            'status' => true,
            'message' => 'Dashboard data retrieved successfully.',
            'data' => [
                "status" => true,
                'down_lineDetails' => $down_lineDetails ?? null,
                'downline_counts' => $downline ?? null,
                'total_cv_counts' => $totalCvCounts ?? null,
                'nowCvCounts' => $nowCvCounts ?? null,
                'last7DaysCvCounts' => $last7DaysCvCounts ?? null,
                'last30DaysCvCounts' => $last30DaysCvCounts ?? null,
                'lastYearCvCounts' => $lastYearCvCounts ?? null,
                'user_package' => $packageDetails ? [
                    'name' => $packageDetails->package->name,
                    'pack_card' => $packageDetails->package->pack_card,
                ] : null,
                'rank' => [
                    'name' => $rank->name ?? null,
                    'image' => $rank->image ?? null,
                    'package' => $rank->package ?? null
                ] ?? null,
                'next_rank' => [
                    'name' => $nextRank ? $nextRank->name : null,
                    'image' => $nextRank ? $nextRank->image : null,
                    'left_volume' => $nextRank ? $nextRank->left_volume : null,
                    'user_left_volume' => $last30DaysCvCounts['left_cv_count'] ?? null,
                    'right_volume' => $nextRank ? $nextRank->right_volume : null,
                    'user_right_volume' => $last30DaysCvCounts['right_cv_count'] ?? null,
                    'left_referrals' => $nextRank ? $nextRank->direct_referrals / 2 : null,
                    'user_left_referrals' => $member->leftLegCount() ?? null,
                    'right_referrals' => $nextRank ? $nextRank->direct_referrals / 2 : null,
                    'user_right_referrals' => $member->rightLegCount() ?? null,
                ] ?? null,
                'remaining_days' =>  round($remainingDays) ?? null,
                'weekly_earnings'  => $fullWeeklyEarnings ?? null,
                'monthly_earnings' => $monthlyEarnings ?? null,
                'total_commissions' => $totalEarnings ?? null, // placeholder for future target metrics
            ],
        ], 200);
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
        $data['left_leg_volume'] = $member->leftSideCvCommissions()->sum('amount');
        $data['right_leg_volume'] = $member->rightSideCvCommissions()->sum('amount');
        return response()->json([
            'status' => true,
            'message' => 'all network volume get successfully ',
            'network_volume' => $data,
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
            $tank->member_id_code = optional($tank->member->user)->id_code; // Get the member's user's id_code if exists
            $tank->member_username  = optional($tank->member->user)->username; // Get the member's user's username if exists
            $tank->member_firstname = optional($tank->member->user)->first_name; // Get the member's user's first_name if exists
            $tank->member_lastname = optional($tank->member->user)->last_name; // Get the member's user's last_name if exists
            $tank->member_package = optional($tank->member->subscription)->package; // Get the member's user's name if exists

            // Check if member and subscription are not null
            if ($tank->member && $tank->member->subscription) {
                $tank->member_package = $tank->member->subscription->package ? $tank->member->subscription->package->name : null; // Safely accessing package name
                $tank->member_package_cv = $tank->member->subscription->package ? $tank->member->subscription->package->cv : null; // Safely accessing package cv
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


        $profileUser = User::where('id', $id)
            ->with('member')
            ->first();
        $profileMember = $profileUser->member;
        $sponsorUser = $profileMember && $profileMember->sponsor
            ? $profileMember->sponsor->user
            : null;
        if ($member->leftLeg && $member->rightLeg) {

            $data = [
                'left_leg_member' => [
                    'id'                => $member->leftLeg->id,
                    'rank_id'           => $member->leftLeg?->rank?->id,
                    'rank_name'         => $member->leftLeg?->rank?->name,
                    'user_name'         => $member->leftLeg->user->username,
                    'user_id_code'      => $member->leftLeg->user->id_code,
                    'user_first_name'   => $member->leftLeg->user->first_name,
                    'user_last_name'    => $member->leftLeg->user->last_name,
                    'user_image'        => $member->leftLeg->user->image,
                ],
                'right_leg_member' => [
                    'id'                => $member->rightLeg->id,
                    'rank_id'           => $member->rightLeg?->rank?->id,
                    'rank_name'         => $member->rightLeg?->rank?->name,
                    'user_name'         => $member->rightLeg->user->username,
                    'user_id_code'      => $member->rightLeg->user->id_code,
                    'user_first_name'   => $member->rightLeg->user->first_name,
                    'user_last_name'    => $member->rightLeg->user->last_name,
                    'user_image'        => $member->rightLeg->user->image,
                ],
                'user' => $profileUser
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        } elseif ($member->leftLeg && !$member->rightLeg) {
            $data = [
                'left_leg_member' => [
                    'id'                => $member->leftLeg->id,
                    'rank_id'           => $member->leftLeg?->rank?->id,
                    'rank_name'         => $member->leftLeg?->rank?->name,
                    'user_name'         => $member->leftLeg->user->username,
                    'user_id_code'      => $member->leftLeg->user->id_code,
                    'user_first_name'   => $member->leftLeg->user->first_name,
                    'user_last_name'    => $member->leftLeg->user->last_name,
                    'user_image'        => $member->leftLeg->user->image,
                ],
                'right_leg_member' => null,
                'user' => $profileUser
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        } elseif (!$member->leftLeg && $member->rightLeg) {
            $data = [
                'left_leg_member' => null,
                'right_leg_member' => [
                    'id'                => $member->rightLeg->id,
                    'rank_id'           => $member->rightLeg?->rank?->id,
                    'rank_name'         => $member->rightLeg?->rank?->name,
                    'user_name'         => $member->rightLeg->user->username,
                    'user_id_code'      => $member->rightLeg->user->id_code,
                    'user_first_name'   => $member->rightLeg->user->first_name,
                    'user_last_name'    => $member->rightLeg->user->last_name,
                    'user_image'        => $member->rightLeg->user->image,
                ],
                'user' => $profileUser
            ];
            return $this->successResponse('all direct members get successfully', 'members', $data);
        } else {
            $data = [
                'left_leg_member' => null,
                'right_leg_member' => null,
                'user' => $profileUser
            ];
            return $this->successResponse('no downlines members to this user', 'members', $data);
        }
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
