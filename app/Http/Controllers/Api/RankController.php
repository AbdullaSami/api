<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use Illuminate\Http\Request;
use App\Services\RankService;
use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Traits\ApiResponseTrait;

class RankController extends Controller
{
    use ApiResponseTrait;
    public function evaluateRank(RankService $rankService)
    {
        $user = auth()->user();
        $member = $user->member;

        // Load rank criteria
        $rankCriteria = $rankService->getRankCriteria();

        if (!is_array($rankCriteria) || empty($rankCriteria)) {
            return response()->json([
                'error' => 'Rank criteria not available or invalid.'
            ], 400);
        }


        if ($member->rank_id == null)
            return $this->failedResponse('no rank assign');


        $currentRank = $member->rank->name;
        $nextRank = null;
        $nextRankCriteria = null;

        foreach ($rankCriteria as $rank => $criteria) {
            // Ensure the rank is higher than the current rank
            if ($criteria['id'] <= $member->rank->id) {
                continue;
            }

            // Check volume requirements
            if (
                $member->totla_left_volume < ($criteria['left_volume'] ?? 0) ||
                $member->totla_right_volume < ($criteria['right_volume'] ?? 0)
            ) {
                continue;
            }

            // Check direct referrals
            if ($member->referrals->count() < ($criteria['direct_referrals'] ?? 0)) {
                continue;
            }

            // Check downline requirements
            $downlineRequirements = $criteria['downline_requirements'] ?? null;
            if (!is_null($downlineRequirements) && !$this->meetsDownlineRequirements($member, $downlineRequirements)) {
                continue;
            }

            // Set the next rank
            $nextRank = $rank;
            $nextRankCriteria = $criteria;
            break; // Stop once the next rank is found
        }

        // Calculate progress
        $progress = $this->calculateProgress($member, $nextRankCriteria ?? []);

        return response()->json([
            'current_rank' => $currentRank,
            'current_rank_iamge' => $member->rank->image,
            'next_rank' => $nextRank ?? $currentRank,
            'progress' => [
                'left_volume' => ($progress['left_volume_progress'] ?? 0) . " (" . $member->totla_left_volume . "/" . ($nextRankCriteria['left_volume'] ?? 0) . ")",
                'right_volume' => ($progress['right_volume_progress'] ?? 0) . " (" . $member->totla_right_volume . "/" . ($nextRankCriteria['right_volume'] ?? 0) . ")",
                'direct_referrals' => ($progress['direct_referrals_progress'] ?? 0) . " (" . $member->referrals->count() . "/" . ($nextRankCriteria['direct_referrals'] ?? 0) . ")",
            ],
        ]);
    }

    public function rankHistory()
    {
        try {
            $user = auth()->user();
            $member = $user->member;

            if($member->rank){
                $userRank = $member->rank; // the user's current rank
            }else{
                $userRank = (object) ['id' => 0]; // default to rank ID 0 if no rank assigned
            }


            // Get all ranks
            $ranks = Rank::orderBy('id')->get();

            // Build response with true/false flag
            $rankStatus = $ranks->map(function ($rank) use ($userRank) {
                return [
                    'rank_id'   => $rank->id,
                    'rank_name' => $rank->name,
                    'rank_image' => $rank->image,
                    'active'    => $rank->id <= $userRank->id, // true for user's rank and all before
                ];
            });
            return response()->json([
                'status' => 'success',
                'data' => $rankStatus
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve rank history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function meetsDownlineRequirements(Member $member, $downlineRequirements)
    {
        // if (!is_array($downlineRequirements) || empty($downlineRequirements)) {
        if (!is_array($downlineRequirements)) {
            return false;
        }

        foreach ($downlineRequirements as $requirement) {
            if (preg_match('/(\d+) (\w+) per leg/', $requirement, $matches)) {
                $requiredCount = (int) $matches[1];
                $requiredRank = $matches[2];

                $leftLegCount = $member->leftLegMembers()
                    ->where('rank', $requiredRank)
                    ->count();
                $rightLegCount = $member->rightLegMembers()
                    ->where('rank', $requiredRank)
                    ->count();

                if ($leftLegCount < $requiredCount || $rightLegCount < $requiredCount) {
                    return false;
                }
            }
        }

        return true;
    }



    private function calculateProgress(Member $member, $criteria)
    {
        return [
            'left_volume_progress' => ($criteria['left_volume'] ?? 0) > 0
                ? $member->totla_left_volume / $criteria['left_volume'] * 100
                : 0,
            'right_volume_progress' => ($criteria['right_volume'] ?? 0) > 0
                ? $member->totla_right_volume / $criteria['right_volume'] * 100
                : 0,
            'direct_referrals_progress' => ($criteria['direct_referrals'] ?? 0) > 0
                ? $member->referrals->count() / $criteria['direct_referrals'] * 100
                : 0,
        ];
    }


    public function myRank()
    {
        $user = auth()->user();
        $member = $user->member;
        $rank = $member->rank;
        $subscription = $member->subscription;

        if (!$rank)
            return  response()->json([
                'status' => false,
                'message' => 'no rank asigned'
            ], 400);

        $nextRank = Rank::where('id', '>', $rank->id)->orderBy('id')->first();

        $remainingDays = null;
        if ($subscription && $subscription->expiration_date) {
            // Use Carbon::parse to ensure we have a Carbon instance and avoid magic property type issues
            $remainingDays = \Illuminate\Support\Carbon::parse(now())->diffInDays($subscription->expiration_date);
        }

        return  response()->json([
            'status' => true,
            'message' => 'rank get successfully',
            'rank' => [
                'name' => $rank->name,
                'image' => $rank->image,
                'package' => $rank->package
            ],
            'next_rank' => $nextRank,
            'remaining_days' =>  round($remainingDays)

        ], 200);
    }

    public function ranks()
    {
        return  !empty(Rank::pluck('name')->toArray()) ?
            $this->successResponse('all rankes name gets successfully', 'ranks', Rank::pluck('name')->toArray()) : $this->failedResponse('no data for ranks found');
    }
}
