<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    /**
     * GET /users
     */
    public function index(Request $request)
    {
        $query = User::query()->with(['member.rank', 'member.subscription.package']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('id_code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * GET /users/{user}
     * Basic user + member record (no heavy tree stats — see profile()).
     */
    public function show(User $user)
    {
        $user->load(['member.rank', 'member.sponsor.user', 'member.subscription.package', 'member.wallet']);

        return response()->json($user);
    }

    /**
     * GET /users/{user}/profile
     * Full profile bundle: user, member, tree position, rank, wallet, subscription.
     */
    public function profile(User $user)
    {
        $member = $user->member;

        if (! $member) {
            return response()->json(['message' => 'This user has no member profile yet.'], 404);
        }

        $member->load(['rank', 'sponsor.user', 'leftLeg.user', 'rightLeg.user', 'subscription.package', 'wallet']);

        return response()->json([
            'user' => $user,
            'member' => $member,
            'tree' => [
                'left_leg_count' => $member->countLeftDownline(),
                'right_leg_count' => $member->countRightDownline(),
                'left_volume' => $member->calculateLeftVolume(),
                'right_volume' => $member->calculateRightVolume(),
                'upline_count' => $member->getUplineCount(),
            ],
        ]);
    }

    /**
     * POST /users
     * Register a new user + place them in the binary tree under a sponsor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'sponsor_code' => ['nullable', 'exists:users,id_code'],
            'leg' => ['required_with:sponsor_code', Rule::in(['left', 'right'])],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // hashed automatically via cast
                'country' => $validated['country'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'status' => 'active',
            ]);

            $sponsorMember = null;
            $placementNode = null;

            if (! empty($validated['sponsor_code'])) {
                $sponsorUser = User::where('id_code', $validated['sponsor_code'])->firstOrFail();
                $sponsorMember = $sponsorUser->member;

                if (! $sponsorMember) {
                    abort(422, 'Sponsor has no member profile.');
                }

                $placementNode = $this->findPlacementSpot($sponsorMember, $validated['leg']);
            }

            $member = Member::create([
                'user_id' => $user->id,
                'sponsor_id' => $sponsorMember->id ?? null,
                'left_leg_id' => null,
                'right_leg_id' => null,
                'current_cv' => 0,
                'totla_left_volume' => 0,
                'totla_right_volume' => 0,
                'rank_id' => null,
                'total_commision' => 0,
                'is_first' => is_null($sponsorMember),
            ]);

            if ($placementNode) {
                $placementNode->update([
                    $validated['leg'] . '_leg_id' => $member->id,
                ]);
            }

            return $user;
        });

        return response()->json($user->load('member'), 201);
    }

    /**
     * PUT/PATCH /users/{user}
     * Update account fields and a limited set of member fields.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'image' => ['nullable', 'string'],
            'rank_id' => ['nullable', 'exists:ranks,id'], // admin override
        ]);

        DB::transaction(function () use ($validated, $user) {
            $user->update(collect($validated)->except('rank_id')->toArray());

            if (array_key_exists('rank_id', $validated) && $user->member) {
                $user->member->update(['rank_id' => $validated['rank_id']]);
            }
        });

        return response()->json($user->fresh('member'));
    }

    /**
     * DELETE /users/{user}
     * Soft-disable rather than hard delete — tree/commission history must stay intact.
     */
    public function destroy(User $user)
    {
        $user->update(['status' => 'suspended']);

        return response()->json(['message' => 'User suspended.']);
    }

    /**
     * BFS down a leg from the sponsor to find the first open slot (spillover placement).
     */
    protected function findPlacementSpot(Member $sponsor, string $leg): Member
    {
        $startId = $leg === 'left' ? $sponsor->left_leg_id : $sponsor->right_leg_id;

        if (! $startId) {
            return $sponsor; // sponsor's own slot on that leg is open
        }

        $queue = [$startId];
        $visited = [];

        while (! empty($queue)) {
            $currentId = array_shift($queue);

            if (in_array($currentId, $visited)) {
                continue;
            }
            $visited[] = $currentId;

            $current = Member::find($currentId);
            if (! $current) {
                continue;
            }

            if (! $current->left_leg_id || ! $current->right_leg_id) {
                return $current;
            }

            $queue[] = $current->left_leg_id;
            $queue[] = $current->right_leg_id;
        }

        // Should be unreachable if the tree is well-formed, but fall back to the sponsor.
        return $sponsor;
    }
}
