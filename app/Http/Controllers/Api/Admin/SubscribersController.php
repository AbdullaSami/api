<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Member;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubscribersController extends Controller
{
    /**
     * GET /subscriptions
     */
    public function index(Request $request)
    {
        $query = Subscription::query()->with(['member.user', 'package']);

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        if ($request->boolean('active_only')) {
            $query->where(function ($q) {
                $q->whereNull('expiration_date')
                  ->orWhere('expiration_date', '>=', now());
            });
        }

        $subscriptions = $query->latest('subscribed_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($subscriptions);
    }

    /**
     * GET /subscriptions/{subscription}
     */
    public function show(Subscription $subscription)
    {
        return response()->json($subscription->load(['member.user', 'package.commissionFactors']));
    }

    /**
     * GET /members/{member}/subscription
     * Current subscription for a member.
     */
    public function forMember(Member $member)
    {
        $subscription = $member->subscription()->with('package')->first();

        if (! $subscription) {
            return response()->json(['message' => 'This member has no active subscription.'], 404);
        }

        return response()->json($subscription);
    }

    /**
     * POST /subscriptions
     * Subscribe a member to a package (new subscription or upgrade/renewal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'payment_method' => ['required', 'string', 'max:100'],
        ]);

        $member = Member::findOrFail($validated['member_id']);
        $package = Package::where('is_published', true)->findOrFail($validated['package_id']);

        $subscription = DB::transaction(function () use ($member, $package, $validated) {
            // Expire any existing subscription rather than deleting it,
            // so history is preserved (see audit note on hasOne).
            $existing = $member->subscription()->first();
            if ($existing) {
                $existing->update(['expiration_date' => now()]);
            }

            $subscription = Subscription::create([
                'member_id' => $member->id,
                'package_id' => $package->id,
                'subscribed_at' => now(),
                'expiration_date' => $this->calculateExpiration($package->billing_period),
                'subscription_price' => $package->price,
                'payment_method' => $validated['payment_method'],
                'code' => $this->generateUniqueCode(),
            ]);

            // Credit the member's CV from the package and evaluate rank.
            $member->increment('current_cv', $package->cv ?? 0);
            $this->applyCommissions($member, $package);
            $member->upgradeRank();

            return $subscription;
        });

        return response()->json($subscription->load(['member.user', 'package']), 201);
    }

    /**
     * PUT/PATCH /subscriptions/{subscription}
     * Update payment method / manually extend expiration. Package changes go through store() (new record).
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'payment_method' => ['sometimes', 'string', 'max:100'],
            'expiration_date' => ['sometimes', 'date'],
        ]);

        $subscription->update($validated);

        return response()->json($subscription->fresh(['member.user', 'package']));
    }

    /**
     * DELETE /subscriptions/{subscription}
     * Cancel a subscription (expires it immediately rather than hard-deleting).
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->update(['expiration_date' => now()]);

        return response()->json(['message' => 'Subscription cancelled.']);
    }

    /**
     * Calculate expiration based on the package's billing period.
     */
    protected function calculateExpiration(?string $billingPeriod): ?Carbon
    {
        return match ($billingPeriod) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'one_time' => null, // never expires
            default => now()->addMonth(),
        };
    }

    protected function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (Subscription::where('code', $code)->exists());

        return $code;
    }

    /**
     * TODO: wire up to CommissionFactor / CvCommission / Commission / Wallet
     * once those schemas are confirmed. Placeholder shows the intended shape:
     * pull the package's active CommissionFactor and post a direct commission
     * to the sponsor + binary volume to both legs' totals.
     */
    protected function applyCommissions(Member $member, Package $package): void
    {
        $factor = $package->commissionFactors()->latest()->first();

        if (! $factor) {
            return;
        }

        // Example intended flow (uncomment/adjust once CvCommission fields are confirmed):
        //
        // if ($member->sponsor) {
        //     $member->sponsor->cvCommissions()->create([
        //         'amount' => $package->cv * ($factor->direct_rate / 100),
        //         'side'   => null,
        //         'source_member_id' => $member->id,
        //     ]);
        // }
        //
        // // Binary CV rolls up both legs to every upline — needs getAllTreeUplines()
        // foreach ($member->getAllTreeUplines() as $upline) {
        //     $upline->cvCommissions()->create([
        //         'amount' => $package->cv * ($factor->binary_rate / 100),
        //         'side' => $this->legOf($upline, $member),
        //         'source_member_id' => $member->id,
        //     ]);
        // }
    }
}
