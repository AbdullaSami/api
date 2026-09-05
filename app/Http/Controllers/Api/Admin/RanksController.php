<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RanksController extends Controller
{
    protected string $imageDisk = 'public';
    protected string $imagePath = 'ranks';

    /**
     * GET /ranks
     */
    public function index(Request $request)
    {
        $query = Rank::query()->withCount('members');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Ranks are progressive (upgradeRank() walks them by id), so keep them ordered.
        $ranks = $query->orderBy('id')->paginate($request->get('per_page', 15));

        return response()->json($ranks);
    }

    /**
     * GET /ranks/{rank}
     */
    public function show(Rank $rank)
    {
        return response()->json($rank->loadCount('members'));
    }

    /**
     * GET /ranks/{rank}/members
     */
    public function members(Request $request, Rank $rank)
    {
        $members = $rank->members()
            ->with(['user:id,username,email,id_code', 'subscription.package'])
            ->paginate($request->get('per_page', 15));

        return response()->json($members);
    }

    /**
     * POST /ranks
     */
    public function store(Request $request)
    {
        $validated = $this->validateRank($request);

        $data = collect($validated)->except(['image', 'icon'])->toArray();

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

        if ($request->hasFile('icon')) {
            $data['icon'] = $this->storeImage($request->file('icon'));
        }

        $rank = Rank::create($data);

        return response()->json($rank, 201);
    }

    /**
     * PUT/PATCH /ranks/{rank}
     */
    public function update(Request $request, Rank $rank)
    {
        $validated = $this->validateRank($request, $rank->id);

        $data = collect($validated)->except(['image', 'icon'])->toArray();

        if ($request->hasFile('image')) {
            $this->deleteImage($rank->image);
            $data['image'] = $this->storeImage($request->file('image'));
        }

        if ($request->hasFile('icon')) {
            $this->deleteImage($rank->icon);
            $data['icon'] = $this->storeImage($request->file('icon'));
        }

        $rank->update($data);

        return response()->json($rank->fresh());
    }

    /**
     * DELETE /ranks/{rank}
     */
    public function destroy(Rank $rank)
    {
        if ($rank->members()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a rank that members currently hold.',
            ], 422);
        }

        $this->deleteImage($rank->image);
        $this->deleteImage($rank->icon);
        $rank->delete();

        return response()->json(['message' => 'Rank deleted successfully.']);
    }

    /**
     * Shared validation for store/update.
     */
    protected function validateRank(Request $request, ?int $rankId = null): array
    {
        $isUpdate = $request->isMethod('put') || $request->isMethod('patch');

        return $request->validate([
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255',
                Rule::unique('ranks', 'name')->ignore($rankId),
            ],
            'package' => ['nullable', 'string', 'max:255'],
            'left_volume' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'right_volume' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'direct_referrals' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:0'],
            'downline_requirements' => ['nullable', 'array'],
            'downline_requirements.left' => ['nullable', 'array'],
            'downline_requirements.left.rank_id' => ['required_with:downline_requirements.left', 'integer', 'exists:ranks,id'],
            'downline_requirements.left.count' => ['required_with:downline_requirements.left', 'integer', 'min:1'],
            'downline_requirements.right' => ['nullable', 'array'],
            'downline_requirements.right.rank_id' => ['required_with:downline_requirements.right', 'integer', 'exists:ranks,id'],
            'downline_requirements.right.count' => ['required_with:downline_requirements.right', 'integer', 'min:1'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
        ]);
    }

    protected function storeImage($file): string
    {
        return $file->store($this->imagePath, $this->imageDisk);
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk($this->imageDisk)->exists($path)) {
            Storage::disk($this->imageDisk)->delete($path);
        }
    }
}
