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
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceReferralRequest;

class MLMController extends Controller
{
    use ApiResponseTrait;

    public function placeReferral(PlaceReferralRequest $request)
    {


        $user = auth()->user();
        $sponsor = $user->member;
        $referral = Member::findOrFail($request->referral_id);
        $packageCv = $referral->subscription->package->cv;

        if (empty($referral->id)) {
            return response()->json('Sorry, this referral not belongs to any sponsor.', 402);
        }
        if (!$referral->subscription)
            return response()->json('Sorry, this referral is not subscribed to any packages.', 402);

        if (
            $referral->id == 1 ||
            $referral->id == $sponsor->id ||
            $referral->id == $sponsor->left_leg_id ||
            $referral->id == $sponsor->right_leg_id
        )
            return response()->json(config('consts.REFERRAL_BLOCK_MESSAGE', 'This process cannot be completed.'));

        if ($referral->id == $this->findFarLeft($sponsor)->id || $referral->id == $this->findFarRight($sponsor)->id)
            return response()->json('referral is already present on the far right or on the far left, it cannot be added twice');

        if ($referral->sponsor->id !== $sponsor->id)
            return response()->json('Sorry, this referral belongs to another sponsor.');

        // Determine placement: left, right, or traverse tree
        if ($request->placement == 'left') {
            if (!$sponsor->left_leg_id) {
                $sponsor->left_leg_id = $referral->id;
                $sponsor->totla_left_volume += $referral->current_cv;
            } else {
                $placementNode = $this->findFarLeft($sponsor);
                $placementNode->left_leg_id = $referral->id;
                $placementNode->save();
            }
        } elseif ($request->placement == 'right') {
            if (!$sponsor->right_leg_id) {
                $sponsor->right_leg_id = $referral->id;
                $sponsor->totla_right_volume += $referral->current_cv;
            } else {
                $placementNode = $this->findFarRight($sponsor);
                $placementNode->right_leg_id = $referral->id;
                $placementNode->save();
            }
        } else {
            return $this->failedResponse('something went wrong , Invalid placement request.', 400);
        }
        $commissionFactor = CommissionFactor::first();
        if (empty($commissionFactor)) {
            return $this->failedResponse('something went wrong , There is no commission calculation plan.');
        }
        $binaryRate =  $commissionFactor->binary_rate;
        $binaryCommissionValue = ($packageCv * $binaryRate) / 100;

        $uplines = $referral->getAllTreeUplines();
        try {
            DB::beginTransaction();
            $sponsor->save();

            // Remove referral from tank
            $tank = UserTank::where('member_id', $request->referral_id)->first();
            if ($tank) $tank->delete();


            if ($referral->is_first == 'yes') {

                if ($request->placement == 'right')
                    $sponsor->totla_right_volume = $packageCv;
                if ($request->placement == 'left')
                    $sponsor->totla_left_volume = $packageCv;

                foreach ($uplines as $upline) {
                    // Skip the direct upline for binary commission
                    if ($upline->id == $sponsor->id) {
                        continue; // Skip to the next upline
                    }

                    // Check if the current member belongs to the left leg of the upline
                    if ($upline->left_leg_id == $referral->id || in_array($referral->id, $this->getLegMembers($upline->left_leg_id))) {
                        $upline->totla_left_volume += $packageCv;
                        Commission::create([
                            'sponsor_id'        => $upline->id,
                            'commission_value'  => $binaryCommissionValue,
                            'commission_type'   => 'binary',
                        ]);
                    }
                    // Check if the current member belongs to the right leg of the upline
                    elseif ($upline->right_leg_id == $referral->id || in_array($referral->id, $this->getLegMembers($upline->right_leg_id))) {
                        $upline->totla_right_volume += $packageCv;
                        Commission::create([
                            'sponsor_id'        => $upline->id,
                            'commission_value'  => $binaryCommissionValue,
                            'commission_type'   => 'binary',
                        ]);
                    }

                    // Update current CV for all uplines
                    $upline->current_cv += $packageCv;
                    $upline->save();
                }

                $referral->is_first = 'no';
                $referral->save();
                $sponsor->save();
            }

            DB::commit();

            return $this->successResponse(
                "The referral: " . $referral->user->name . " has been successfully added to Sponsor: " . $sponsor->user->name . " in " . $request->placement . " leg",
                'sponsor',
                $sponsor
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse('Sorry, this process cannot be completed.' . $e, 500);
        }
    }

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

    // public function mtTank()
    // {
    //     $user = auth()->user();

    //     // Assuming the user has a single member  
    //     $member = $user->member;

    //     // Check if the member is not found  
    //     if (!$member) {
    //         return $this->failedResponse('Member not found');
    //     }


    //     // Fetch only the necessary fields  
    //     $tanks = UserTank::where('sponsor_id', $member->id)
    //         ->with([
    //             'member.user:id,name',
    //             'member.subscription.package:id,name'
    //         ]) // Eager load the user from the member and the package from the subscription  
    //         ->paginate(5);

    //     // Append the member name and package to each tank  
    //     $tanks->getCollection()->transform(function ($tank) {
    //         $tank->member_name = optional($tank->member->user)->name; // Get the member's user's name if exists  
    //         $tank->member_package = optional($tank->member->subscription->package)->name; // Safely accessing package name  

    //         unset($tank->member); // Optionally remove the full member object if you don’t want it  
    //         return $tank;
    //     });

    //     return $this->successResponse('Tanks retrieved successfully', 'tank', $tanks);
    // }




    public function mtTank()
    {
        $user = auth()->user();

        // Assuming the user has a single member  
        $member = $user->member;

        // Check if the member is not found  
        if (!$member) {
            return $this->failedResponse('Member not found');
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
    }


    // public function getYearlySales()
    // {
    //     $user = auth()->user();

    //     // Validate if the authenticated user exists
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not authenticated'
    //         ], 401);
    //     }

    //     $member = $user->member;

    //     // Validate if the user is linked to a member
    //     if (!$member) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User does not have an associated member'
    //         ], 404);
    //     }

    //     // Load all downlines recursively
    //     $downlines = $member->getAllDownlines();

    //     // Check if downlines exist
    //     if (empty($downlines)) {
    //         return response()->json([
    //             'monthly_sales' => [],
    //             'weekly_sales' => [],
    //             'message' => 'No downlines found for this member',
    //         ]);
    //     }

    //     // Collect all wallets of the downlines
    //     $wallets = Wallet::whereIn('member_id', $downlines->pluck('id'))->get();

    //     // Initialize arrays for monthly and weekly sales
    //     $monthlySales = [];
    //     $weeklySales = [];

    //     // Iterate through wallets and process transactions
    //     foreach ($wallets as $wallet) {
    //         $transactions = $wallet->tarnsactions()
    //             ->where('transaction_type', 'buy_package')
    //             ->where('status', 'accepted')
    //             ->get();

    //         // Skip if no transactions exist
    //         if (empty($transactions)) {
    //             continue;
    //         }

    //         foreach ($transactions as $transaction) {
    //             // Ensure transaction data is valid
    //             $amount = $transaction->amount ?? 0; // Default to 0 if null
    //             $createdAt = $transaction->created_at ?? null;

    //             // Skip transactions with invalid dates
    //             if (!$createdAt) {
    //                 continue;
    //             }

    //             $transactionDate = Carbon::parse($createdAt);

    //             // Calculate monthly sales
    //             $monthKey = $transactionDate->format('Y-m'); // e.g., "2024-01"
    //             if (!isset($monthlySales[$monthKey])) {
    //                 $monthlySales[$monthKey] = 0;
    //             }
    //             $monthlySales[$monthKey] += $amount;

    //             // Calculate weekly sales
    //             $weekKey = $transactionDate->format('Y-W'); // e.g., "2024-02"
    //             if (!isset($weeklySales[$weekKey])) {
    //                 $weeklySales[$weekKey] = 0;
    //             }
    //             $weeklySales[$weekKey] += $amount;
    //         }
    //     }

    //     // Handle cases where no transactions were found
    //     if (empty($monthlySales) && empty($weeklySales)) {
    //         return response()->json([
    //             'status'    => true,
    //             'message' => 'No transactions found for this member’s network',
    //             'monthly_sales' => [],
    //             'weekly_sales' => [],
    //         ]);
    //     }

    //     // Format and return the response
    //     $response = [
    //         'monthly_sales' => $monthlySales,
    //         'weekly_sales' => $weeklySales,
    //     ];

    //     return response()->json([
    //         'status'    => true,
    //         'message'   => 'the report extracted successfully',
    //         'data'      => $response
    //     ]);
    // }



    // public function getYearlySales()
    // {
    //     $user = auth()->user();

    //     // Validate if the authenticated user exists
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not authenticated'
    //         ], 401);
    //     }

    //     $member = $user->member;

    //     // Validate if the user is linked to a member
    //     if (!$member) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User does not have an associated member'
    //         ], 404);
    //     }

    //     // Include the current member and all their downlines
    //     $downlines = collect([$member])->merge($member->getAllDownlines());

    //     // Ensure downlines exist
    //     if ($downlines->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No downlines found for this member',
    //             'monthly_sales' => [],
    //             'weekly_sales' => [],
    //         ]);
    //     }

    //     // Fetch all wallets belonging to the current member and their downlines
    //     $wallets = Wallet::whereIn('member_id', $downlines->pluck('id'))->get();

    //     // Initialize arrays for monthly and weekly sales
    //     $monthlySales = array_fill_keys(range(1, 12), 0); // Initialize months with 0 sales
    //     $weeklySales = [];

    //     // Iterate through wallets and process transactions
    //     foreach ($wallets as $wallet) {
    //         $transactions = $wallet->tarnsactions()
    //             ->where('transaction_type', 'buy_package')
    //             ->where('status', 'accepted')
    //             ->get();

    //         // Skip if no transactions exist
    //         if ($transactions->isEmpty()) {
    //             continue;
    //         }

    //         foreach ($transactions as $transaction) {
    //             // Ensure transaction data is valid
    //             $amount = $transaction->amount ?? 0; // Default to 0 if null
    //             $createdAt = $transaction->created_at ?? null;

    //             // Skip transactions with invalid dates
    //             if (!$createdAt) {
    //                 continue;
    //             }

    //             $transactionDate = Carbon::parse($createdAt);

    //             // Calculate monthly sales
    //             $monthKey = $transactionDate->month; // e.g., "1" for January
    //             $monthlySales[$monthKey] += $amount;

    //             // Calculate weekly sales
    //             $weekKey = $transactionDate->format('Y-W'); // e.g., "2024-02"
    //             if (!isset($weeklySales[$weekKey])) {
    //                 $weeklySales[$weekKey] = 0;
    //             }
    //             $weeklySales[$weekKey] += $amount;
    //         }
    //     }

    //     // Format the monthly sales with month names
    //     $formattedMonthlySales = [];
    //     foreach ($monthlySales as $month => $total) {
    //         $formattedMonthlySales[Carbon::create(null, $month)->format('F')] = $total; // "January" => 200
    //     }

    //     // Handle cases where no transactions were found
    //     if (empty($formattedMonthlySales) && empty($weeklySales)) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'No transactions found for this member’s network',
    //             'monthly_sales' => [],
    //             'weekly_sales' => [],
    //         ]);
    //     }

    //     // Format and return the response
    //     return response()->json([
    //         'monthly_sales' => $formattedMonthlySales,
    //         'weekly_sales' => $weeklySales,
    //     ]);
    // }


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
