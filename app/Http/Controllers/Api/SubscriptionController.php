<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\Package;
use App\Models\Commission;
use App\Models\Subscription;
use App\Models\CommissionFactor;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreSubscriptionRequest;


class SubscriptionController extends Controller
{
    use ApiResponseTrait;



    /*
    *
    *'Monthly','Annual','Quarterly','Biannual','Lifelong'
    *
    */

    public function store(StoreSubscriptionRequest $request)
    {
        $user = Auth::user();
        $member = $user->member;

        // Safety Checks
        if (!$member || !$member->tokenWallet) {
            return $this->failedResponse("Member or wallet not found.");
        }

        $member_balance = $member->tokenWallet->token_balance;
        $package = Package::find($request->package_id);

        if (!$package) {
            return $this->failedResponse("The selected package is invalid.");
        }

        // Billing Period Logic
        $expiration_date = match ($package->billing_period) {
            'Monthly'   => now()->addDays(30),
            'Annual'    => now()->addYear(),
            'Quarterly' => now()->addQuarter(),
            'Biannual'  => now()->addMonths(6),
            'Lifelong'  => now()->addYears(100),
            default     => null,
        };

        // Package Details
        $package_price = $package->price;
        $packageCv     = $package->cv;

        // Balance Check
        if ($member_balance < $package_price) {
            return $this->failedResponse('Insufficient balance to subscribe to this package.');
        }

        // Already Subscribed?
        if ($member->subscription) {
            return $this->failedResponse('You are currently subscribed. Please unsubscribe first.');
        }

        // Commission Factors
        $commissionFactor = CommissionFactor::first();
        if (!$commissionFactor) {
            return $this->failedResponse('There is no commission calculation plan.');
        }

        $directRate = $commissionFactor->direct_rate;
        $binaryRate = $commissionFactor->binary_rate;

        $directCommissionValue = ($packageCv * $directRate) / 100;
        $binaryCommissionValue = ($packageCv * $binaryRate) / 100;

        DB::beginTransaction();
        try {

            // Create Subscription
            $subscription = Subscription::create([
                'member_id'       => $member->id,
                'package_id'      => $package->id,
                'subscribed_at'   => now(),
                'expiration_date' => $expiration_date,
            ]);

            // Update Member Wallet Balance
            $newBalance = $this->updateMemberWallatBallnce($member, $package_price, $package->name);

            // Add Direct Commission to member
            $sponsorWallet = $member->sponsor->wallet;
            $sponsorWallet->balance += $directCommissionValue;
            $sponsorWallet->save();

            // Uplines
            // Create direct commission
            if ($member->sponsor_id) {
                Commission::create([
                    'sponsor_id'       => $member->sponsor->id,
                    'commission_value' => $directCommissionValue,
                    'commission_type'  => 'direct',
                ]);
            }

            $currentMember = $member; // current member who purchased the package
            $packageCv = $package->cv; // CV of the purchased package

            while ($currentMember->sponsor_id) { // traverse up until the top
                $upline = $currentMember->sponsor; // assume you have a 'sponsor' relation

                // Determine which leg this member is on
                if ($upline->left_leg_id == $currentMember->id) {
                    $upline->totla_left_volume += $packageCv;
                } elseif ($upline->right_leg_id == $currentMember->id) {
                    $upline->totla_right_volume += $packageCv;
                }

                // Update current CV for upline
                $upline->current_cv += $packageCv;
                $upline->save();

                // Create binary commission for this upline (if needed)
                if ($upline->id != $member->sponsor_id) { // skip direct sponsor if already handled
                    Commission::create([
                        'sponsor_id'       => $upline->id,
                        'commission_value' => $binaryCommissionValue,
                        'commission_type'  => 'binary',
                    ]);
                }

                // Move one level up
                $currentMember = $upline;
            }


            DB::commit();

            return $this->successResponse(
                'You have successfully subscribed. Current balance is ' . $newBalance,
                'subscription',
                [
                    ...$subscription->toArray(),
                    'member_name'  => $member->user->name,
                    'sponsor'      => $member->sponsor_id ? $member->sponsor->user->name : 'main_account',
                    'package_name' => $package->name
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse('An error occurred while processing your subscription. ' . $e->getMessage());
        }
    }


    private function updateMemberWallatBallnce(Member $member, $value, $packageName)
    {
        $wallet = $member->tokenWallet;
        $wallet->update([
            'token_balance' => $wallet->token_balance - $value
        ]);

        $wallet->transaction()->create([
            'transaction_type' => 'receive',
            'sender_member_id' => $member->id,
            'receive_member_id' => $member->id,
            'amount' =>  $value,
            'status' => 'sent',
        ]);
        return $wallet->token_balance;
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
}
