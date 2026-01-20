<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sponsor_id',
        'left_leg_id',
        'right_leg_id',
        'current_cv',
        'totla_left_volume',
        'totla_right_volume',
        'rank_id',
        'total_commision',
        'is_first',
    ];


    protected $casts = [
        'created_at' => 'datetime',
    ];


    /**
     * Abdulla updates
     */

    public function tokenWallet()
    {
        return $this->hasOne(TokenWallet::class);
    }
    public function tokenTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            TokenTransaction::class,
            TokenWallet::class,
            'member_id', // Foreign key on TokenWallet table
            'token_wallet_id', // Foreign key on TokenTransaction table
            'id', // Local key on Member table
            'id'  // Local key on TokenWallet table
        );
    }

    public function commission(): HasMany
    {
        return $this->hasMany(Commission::class, 'sponsor_id', 'id');
    }

    // Members that this member has referred
    public function directReferrals(): HasMany
    {
        return $this->hasMany(Referal::class, 'sponsor_id', 'id');
    }

    // Who referred this member (his sponsor)
    public function directSponsor()
    {
        return $this->belongsTo(Referal::class, 'id', 'referral_id');
    }
    public function leftLegCount()
    {
        $memberId = $this->id;
        return Referal::where('sponsor_id', $memberId)
            ->where('commission_type', 'direct')
            ->where('leg', 'left')
            ->count();
    }
    public function rightLegCount()
    {
        $memberId = $this->id;
        return Referal::where('sponsor_id', $memberId)
            ->where('commission_type', 'direct')
            ->where('leg', 'right')
            ->count();
    }

    public function upgradeRank()
    {
        // Get current rank
        $currentRank = $this->rank_id;


        // Get next rank
        $nextRank = Rank::where('id', '>', $currentRank)
            ->orderBy('id')
            ->first();

        if (! $nextRank) {
            return false; // Already at top rank
        }

        // Example requirement checks
        $leftSum = $this->totla_left_volume;        // adjust to your structure
        $rightSum = $this->totla_right_volume;
        $directRefs = $this->directReferrals()->count();

        if ($leftSum >= $nextRank->left_volume && $rightSum >= $nextRank->right_volume && $directRefs >= $nextRank->direct_referrals) {
            $this->rank_id = $nextRank->id;
            $this->save();

            return true; // Rank updated
        }

        return false; // Requirements not yet met
    }

    /**
     * End of Abdulla updates
     */


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }

    public function commissionReferral(): HasMany
    {
        return $this->hasMany(Commission::class, 'referral_id', 'id');
    }

    /**
     * Relationship with Subscription model
     * A Member may have one active subscription after purchasing a package.
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }


    public function sponsor()
    {
        return $this->belongsTo(Member::class, 'sponsor_id');
    }


    public function leftLeg()
    {
        return $this->belongsTo(Member::class, 'left_leg_id');
    }


    public function rightLeg()
    {
        return $this->belongsTo(Member::class, 'right_leg_id');
    }

    /**
     * Relationship with UserTank model
     * A Member may be in the UserTank table if they haven't purchased a package.
     */
    public function userTank()
    {
        return $this->hasOne(UserTank::class);
    }

    /**
     * Relationship with Wallet model
     * Each Member has a wallet.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }



    public function getAllDownlines()
    {

        return $this->hasMany(Member::class, 'sponsor_id')->with('getAllDownlines');
    }



    public function getAllDownlinesNetwork()
    {
        $allDownlines = collect();

        // Get direct downlines
        $directDownlines = $this->downlines; // Assuming `downlines` is a relationship

        foreach ($directDownlines as $downline) {
            // Add the current downline
            $allDownlines->push($downline);

            // Recursively get all nested downlines
            $nestedDownlines = $downline->getAllDownlinesNetwork();
            $allDownlines = $allDownlines->merge($nestedDownlines);
        }

        return $allDownlines;
    }


    public function referrals()
    {
        return $this->hasMany(Member::class, 'sponsor_id');
    }


    public function downlines()
    {
        return $this->hasMany(Member::class, 'sponsor_id', 'id');
    }
    /**
     * Calculates the count of downline members on the left leg.
     */
    public function countLeftDownline()
    {
        return $this->countDownline($this->left_leg_id);
    }

    /**
     * Calculates the count of downline members on the right leg.
     */
    public function countRightDownline()
    {
        return $this->countDownline($this->right_leg_id);
    }

    /**
     * Recursive function to calculate downline count.
     */
    private function countDownline($legId)
    {
        if (!$legId) {
            return 0;
        }
        $member = self::find($legId);
        return 1 + $member->countLeftDownline() + $member->countRightDownline();
    }



    /**
     * Calculates the total network volume for the left leg.
     */
    public function calculateLeftVolume()
    {
        return $this->calculateVolume($this->left_leg_id);
    }

    /**
     * Calculates the total network volume for the right leg.
     */
    public function calculateRightVolume()
    {
        return $this->calculateVolume($this->right_leg_id);
    }

    /**
     * Recursive function to calculate network volume for a leg.
     */
    private function calculateVolume($legId)
    {
        if (!$legId) {
            return 0;
        }
        $member = self::find($legId);
        $volume = $member->current_cv;
        return $volume + $member->calculateLeftVolume() + $member->calculateRightVolume();
    }

    public function subscriptionPrice()
    {
        return $this->subscription->package->price;
    }

    public function subscriptionCV()
    {
        return $this->subscription->package->cv;
    }


    public function getAllTreeUplines()
    {
        $uplines = [];
        $currentMember = $this;

        // Loop to traverse the binary MLM tree
        while ($currentMember) {
            $directSponsor = $currentMember->sponsor; // Fetch the direct sponsor

            if ($directSponsor) {
                $uplines[] = $directSponsor; // Add the direct sponsor to the uplines list

                // Check if the current member is in the direct sponsor's legs (left or right)
                if ($directSponsor->left_leg_id == $currentMember->id || $directSponsor->right_leg_id == $currentMember->id) {
                    $currentMember = $directSponsor; // Move up the tree to the direct sponsor
                    continue;
                }
            }

            // Check for indirect uplines based on left or right leg placement
            $indirectSponsor = Member::where('left_leg_id', $currentMember->id)
                ->orWhere('right_leg_id', $currentMember->id)
                ->first();

            if ($indirectSponsor) {
                $uplines[] = $indirectSponsor; // Add the indirect upline to the list
                $currentMember = $indirectSponsor; // Move up the tree to the indirect sponsor
            } else {
                break; // Exit the loop if no further uplines are found
            }
        }

        return $uplines;
    }


    // Recursive method to fetch all uplines
    public function getAllUplines()
    {
        $uplines = [];
        $sponsor = $this->sponsor;

        while ($sponsor) {
            $uplines[] = $sponsor;
            $sponsor = $sponsor->sponsor;
        }

        return $uplines;
    }

    // Method to get the count of all uplines
    public function getUplineCount()
    {
        return count($this->getAllUplines());
    }


    /**
     * Get rank-based downline counts for both legs (left and right).
     *
     * @return array
     */
    public function getDownlineDetailsByRank()
    {
        $leftRanks = $this->getRankBasedDownlineCount($this->left_leg_id);
        $rightRanks = $this->getRankBasedDownlineCount($this->right_leg_id);

        // Retrieve all available ranks
        $ranks = Rank::all();

        $data = [];
        $totalLeft = 0;
        $totalRight = 0;

        foreach ($ranks as $rank) {
            $leftCount = $leftRanks[$rank->id] ?? 0;
            $rightCount = $rightRanks[$rank->id] ?? 0;

            $data[] = [
                'rank' => $rank->name,
                'left' => $leftCount,
                'right' => $rightCount,
            ];

            // Add to totals
            $totalLeft += $leftCount;
            $totalRight += $rightCount;
        }

        // Add totals row if needed
        $data[] = [
            'rank' => 'Total',
            'left' => $totalLeft,
            'right' => $totalRight,
        ];

        return $data;
    }



    /**
     * Get rank-based downline count for the specified leg (left or right).
     *
     * @param int|null $legId
     * @return array
     */
    public function getRankBasedDownlineCount($legId)
    {
        if (!$legId) {
            return [];
        }

        // Recursive function to calculate downline members by rank
        $members = $this->getAllMembersInLeg($legId);

        // Group members by rank_id and count them
        $rankCounts = $members->groupBy('rank_id')
            ->map(fn($group) => $group->count())
            ->toArray();

        return $rankCounts;
    }

    private function getAllMembersInLeg($legId, $visited = [])
    {
        $members = collect();

        $member = self::find($legId);

        // Check if the member has already been visited
        if ($member && !in_array($member->id, $visited)) {
            // Mark this member as visited
            $visited[] = $member->id;

            // Add this member to the list
            $members->push($member);

            // Recursively add members from both legs
            $members = $members->merge($member->getAllMembersInLeg($member->left_leg_id, $visited));
            $members = $members->merge($member->getAllMembersInLeg($member->right_leg_id, $visited));
        }

        return $members;
    }

































    /**
     * Validate a specific node for potential issues.
     *
     * @return array
     */
    public function validateNode()
    {
        $issues = [];

        // Check if the member has a valid rank
        if (!$this->rank_id) {
            $issues[] = 'Missing rank.';
        }

        // Check if the node's left and right legs exist
        if (!$this->left_leg_id && !$this->right_leg_id) {
            $issues[] = 'Both left and right legs are missing.';
        }

        // Check if there's a circular reference
        if ($this->id == $this->left_leg_id || $this->id == $this->right_leg_id) {
            $issues[] = 'Circular reference detected.';
        }

        return $issues;
    }

    /**
     * Validate the entire downline tree recursively for this member.
     *
     * @return array
     */
    public function validateTree()
    {
        $problems = [];
        $this->validateNodeInTree($this, $problems);

        return $problems;
    }

    /**
     * Recursive helper to validate nodes in the tree.
     *
     * @param Member $member
     * @param array $problems
     * @param array $visited
     */
    private function validateNodeInTree($member, &$problems, &$visited = [])
    {
        // Avoid infinite loops with circular references
        if (in_array($member->id, $visited)) {
            return;
        }

        $visited[] = $member->id;

        // Validate the current node
        $issues = $member->validateNode();
        if (!empty($issues)) {
            $problems[$member->id] = $issues;
        }

        // Validate the left leg
        if ($member->left_leg_id) {
            $leftLeg = self::find($member->left_leg_id);
            if ($leftLeg) {
                $this->validateNodeInTree($leftLeg, $problems, $visited);
            }
        }

        // Validate the right leg
        if ($member->right_leg_id) {
            $rightLeg = self::find($member->right_leg_id);
            if ($rightLeg) {
                $this->validateNodeInTree($rightLeg, $problems, $visited);
            }
        }
    }
}
