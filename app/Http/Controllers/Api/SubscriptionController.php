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
        $member_balance = $member->wallet->balance;
        $package = Package::find($request->package_id);
        if (empty($package)) {
            return $this->failedResponse("The selected package is invalid.");
        }
        $billing_period = $package->billing_period;
        $expiration_date = null;
        if (!empty($billing_period)) {
            $billing_period == 'Monthly' ? $expiration_date = now()->addDays(30) : null;
            $billing_period == 'Annual' ? $expiration_date = now()->addYear() : null;
            $billing_period == 'Quarterly' ? $expiration_date = now()->addQuarter() : null;
            $billing_period == 'Biannual' ? $expiration_date = now()->addMonths(6) : null;
            $billing_period == 'Lifelong' ? $expiration_date = now()->addYears(100) : null;
        }
        $package_price = $package->price;
        $packageCv = $package->cv;

        // Check if member balance is sufficient  
        if ($member_balance < $package_price) {
            return $this->failedResponse('Insufficient balance to subscribe to this package.');
        }

        // Check if the member is already subscribed  
        if ($member->subscription) {
            return $this->failedResponse('You are currently subscribed to a package. Please unsubscribe from the current package first');
        }

        try {
            DB::beginTransaction();

            $subscription = Subscription::create([
                'member_id' => $member->id,
                'package_id' => $request->package_id,
                'subscribed_at' => now(),
                'expiration_date' =>  $expiration_date,
            ]);

            // $member->update(['current_cv' => $package->cv]);
            // Update the member's wallet balance  
            $newBalance = $this->updateMemberWallatBallnce($member, $package_price, $package->name);

            // Calculate commission

            $commissionFactor = CommissionFactor::first();
            if (empty($commissionFactor)) {
                return $this->failedResponse('something went wrong , There is no commission calculation plan.');
            }
            $directRate =  $commissionFactor->direct_rate;
            $binaryRate =  $commissionFactor->binary_rate;
            $directCommissionValue = ($packageCv * $directRate) / 100;
            $binaryCommissionValue = ($packageCv * $binaryRate) / 100;
            $member->total_commision += $directCommissionValue;

            $uplines = $member->getAllTreeUplines();

            foreach ($uplines as $upline) {
                // Skip the direct upline for binary commission
                if ($upline->id == $member->sponsor_id) {
                    continue; // Skip to the next upline
                }

                // Check if the current member belongs to the left leg of the upline
                // if ($upline->left_leg_id == $member->id || in_array($member->id, $this->getLegMembers($upline->left_leg_id))) {
                Commission::create([
                    'sponsor_id'        => $upline->id,
                    'commission_value'  => $binaryCommissionValue,
                    'commission_type'   => 'binary',
                ]);
                // }
                // Check if the current member belongs to the right leg of the upline
                // elseif ($upline->right_leg_id == $member->id || in_array($member->id, $this->getLegMembers($upline->right_leg_id))) {
                // Commission::create([
                //     'sponsor_id'        => $upline->id,
                //     'commission_value'  => $binaryCommissionValue,
                //     'commission_type'   => 'binary',
                // ]);
                // }

                // Update current CV for all uplines
                $upline->current_cv += $packageCv;
                $upline->save();
            }

            // Create commission record
            Commission::create([
                'sponsor_id'        => $member->sponsor->id,
                'commission_value'  => $directCommissionValue,
                'commission_type'   => 'direct',
            ]);

            if ($member->is_first == 'no') {
                foreach ($uplines as $upline) {
                    // Check if the current member belongs to the left leg of the upline
                    if ($upline->left_leg_id == $member->id || in_array($member->id, $this->getLegMembers($upline->left_leg_id))) {
                        $upline->totla_left_volume += $packageCv;
                    }
                    // Check if the current member belongs to the right leg of the upline
                    elseif ($upline->right_leg_id == $member->id || in_array($member->id, $this->getLegMembers($upline->right_leg_id))) {
                        $upline->totla_right_volume += $packageCv;
                    }

                    // Update current CV for all uplines
                    $upline->current_cv += $packageCv;
                    $upline->save();
                }
            }


            DB::commit();

            return $this->successResponse(
                'You have successfully subscribed. Current balance is ' . $newBalance,
                'subscription',
                array_merge($subscription->toArray(), [
                    'member_name' => $member->user->name,
                    'sponsor' => $member->sponsor_id == null ? 'main_acount' : $member->sponsor->user->name,
                    'package_name' => $package->name
                ])
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse('An error occurred while processing your subscription. ' . $e);
        }
    }

    private function updateMemberWallatBallnce(Member $member, $value, $packageName)
    {
        $wallet = $member->wallet;
        $wallet->update([
            'balance' => $wallet->balance - $value
        ]);

        $wallet->tarnsactions()->create([
            'transaction_type' => 'buy_package',
            'amount' =>  $value,
            'status' => 'accepted',
            'package_name' => $packageName,
        ]);
        return $wallet->balance;
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
