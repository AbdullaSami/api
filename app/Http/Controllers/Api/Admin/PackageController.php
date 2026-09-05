<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\CommissionFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class PackageController extends Controller
{
    /**
     * Where package images are stored (relative to the "public" disk).
     */
    protected string $imageDisk = 'public';
    protected string $imagePath = 'packages';

    /**
     * GET /packages
     */
    public function index(Request $request)
    {
        $query = Package::query()->with('commissionFactors');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_published')) {
            $query->where('is_published', filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN));
        }

        $packages = $query->orderBy('level')->paginate($request->get('per_page', 15));

        return response()->json($packages);
    }

    /**
     * GET /packages/{package}
     */
    public function show(Package $package)
    {
        $package->load('commissionFactors');

        return response()->json($package);
    }

    /**
     * POST /packages
     */
    public function store(Request $request)
    {
        $validated = $this->validatePackage($request);

        $package = DB::transaction(function () use ($validated, $request) {
            $data = collect($validated)->only([
                'name', 'price', 'level', 'billing_period', 'cv', 'features', 'is_published',
            ])->toArray();

            $data['is_published'] = $validated['is_published'] ?? false;

            if ($request->hasFile('pack_card')) {
                $data['pack_card'] = $this->storeImage($request->file('pack_card'));
            }

            if ($request->hasFile('pack_icon')) {
                $data['pack_icon'] = $this->storeImage($request->file('pack_icon'));
            }

            $package = Package::create($data);

            if (isset($validated['direct_rate']) || isset($validated['binary_rate'])) {
                $package->commissionFactors()->create([
                    'direct_rate' => $validated['direct_rate'] ?? 0,
                    'binary_rate' => $validated['binary_rate'] ?? 0,
                ]);
            }

            return $package;
        });

        return response()->json($package->load('commissionFactors'), 201);
    }

    /**
     * PUT/PATCH /packages/{package}
     */
    public function update(Request $request, Package $package)
    {
        $validated = $this->validatePackage($request, $package->id);

        DB::transaction(function () use ($validated, $request, $package) {
            $data = collect($validated)->only([
                'name', 'price', 'level', 'billing_period', 'cv', 'features', 'is_published',
            ])->toArray();

            if ($request->hasFile('pack_card')) {
                $this->deleteImage($package->pack_card);
                $data['pack_card'] = $this->storeImage($request->file('pack_card'));
            }

            if ($request->hasFile('pack_icon')) {
                $this->deleteImage($package->pack_icon);
                $data['pack_icon'] = $this->storeImage($request->file('pack_icon'));
            }

            $package->update($data);

            if (array_key_exists('direct_rate', $validated) || array_key_exists('binary_rate', $validated)) {
                $package->commissionFactors()->updateOrCreate(
                    ['package_id' => $package->id],
                    [
                        'direct_rate' => $validated['direct_rate'] ?? optional($package->commissionFactors()->first())->direct_rate ?? 0,
                        'binary_rate' => $validated['binary_rate'] ?? optional($package->commissionFactors()->first())->binary_rate ?? 0,
                    ]
                );
            }
        });

        return response()->json($package->fresh('commissionFactors'));
    }

    /**
     * DELETE /packages/{package}
     */
    public function destroy(Package $package)
    {
        if ($package->subscriptions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a package that has active subscriptions.',
            ], 422);
        }

        DB::transaction(function () use ($package) {
            $this->deleteImage($package->pack_card);
            $this->deleteImage($package->pack_icon);
            $package->commissionFactors()->delete();
            $package->delete();
        });

        return response()->json(['message' => 'Package deleted successfully.']);
    }

    /**
     * PATCH /packages/{package}/toggle-publish
     */
    public function togglePublish(Package $package)
    {
        $package->update(['is_published' => ! $package->is_published]);

        return response()->json($package);
    }

    /**
     * Shared validation rules for store/update.
     */
    protected function validatePackage(Request $request, ?int $packageId = null): array
    {
        $isUpdate = $request->isMethod('put') || $request->isMethod('patch');

        return $request->validate([
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255',
                Rule::unique('packages', 'name')->ignore($packageId),
            ],
            'price' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'level' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:0'],
            'billing_period' => [$isUpdate ? 'sometimes' : 'required', Rule::in(['monthly', 'yearly', 'one_time'])],
            'cv' => ['nullable', 'numeric', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'is_published' => ['nullable', 'boolean'],
            'pack_card' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'pack_icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'direct_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'binary_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
