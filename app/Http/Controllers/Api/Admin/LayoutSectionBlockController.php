<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderLayoutSectionBlockRequest;
use App\Http\Requests\UpdateLayoutSectionBlockRequest;
use App\Http\Resources\LayoutSectionBlockResource;
use App\Models\LayoutSectionBlock;
use App\Services\LandingPageServiceInterface;
use Illuminate\Http\JsonResponse;

class LayoutSectionBlockController extends Controller
{
        public function __construct(
        protected LandingPageServiceInterface $landingPageService
    ) {}


    /**
     * PATCH /api/admin/landing-page/blocks/{block}
     */
    public function update(UpdateLayoutSectionBlockRequest $request, LayoutSectionBlock $block): LayoutSectionBlockResource
    {
        $updated = $this->landingPageService->updateBlockContent(
            $block->id,
            $request->validated('content')
        );

        return new LayoutSectionBlockResource($updated);
    }

    /**
     * PATCH /api/admin/landing-page/sections/{section}/blocks/reorder
     */
    public function reorder(ReorderLayoutSectionBlockRequest $request, int $section): JsonResponse
    {
        $this->landingPageService->reorderBlocks($section, $request->validated('ordered_ids'));

        return response()->json(['message' => 'Blocks reordered.']);
    }
}
