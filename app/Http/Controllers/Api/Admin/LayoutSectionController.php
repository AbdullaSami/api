<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderLayoutSectionRequest;
use App\Http\Resources\LayoutSectionResource;
use App\Models\LayoutSection;
use App\Services\LandingPageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LayoutSectionController extends Controller
{
        public function __construct(
        protected LandingPageServiceInterface $landingPageService
    ) {}


    /**
     * GET /api/admin/landing-page/sections
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('edit-landing-page');

        return LayoutSectionResource::collection($this->landingPageService->getFullLayout());
    }

    /**
     * PATCH /api/admin/landing-page/sections/reorder
     */
    public function reorder(ReorderLayoutSectionRequest $request): JsonResponse
    {
        $this->landingPageService->reorderSections($request->validated('ordered_ids'));

        return response()->json(['message' => 'Sections reordered.']);
    }

    /**
     * PATCH /api/admin/landing-page/sections/{section}/toggle
     */
    public function toggle(LayoutSection $section): LayoutSectionResource
    {
        Gate::authorize('edit-landing-page');

        $section = $this->landingPageService->toggleSection($section->id);

        return new LayoutSectionResource($section);
    }
}
