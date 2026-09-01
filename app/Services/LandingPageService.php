<?php

namespace App\Services;

use App\Models\LayoutSectionBlock;
use App\Models\LayoutSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LandingPageService implements LandingPageServiceInterface
{
    public function getLayout(): Collection
    {
        return LayoutSection::query()
            ->active()
            ->ordered()
            ->with(['blocks' => fn ($q) => $q->active()])
            ->get();
    }

    public function getFullLayout(): Collection
    {
        return LayoutSection::query()
            ->ordered()
            ->with('blocks')
            ->get();
    }

    public function reorderSections(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                LayoutSection::whereKey($id)->update(['order' => $index]);
            }
        });
    }

    public function reorderBlocks(int $sectionId, array $orderedIds): void
    {
        DB::transaction(function () use ($sectionId, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                LayoutSectionBlock::where('id', $id)
                    ->where('layout_section_id', $sectionId)
                    ->update(['order' => $index]);
            }
        });
    }

    public function toggleSection(int $sectionId): LayoutSection
    {
        $section = LayoutSection::findOrFail($sectionId);
        $section->update(['is_active' => ! $section->is_active]);

        return $section->fresh();
    }

    public function updateBlockContent(int $blockId, array $content): LayoutSectionBlock
    {
        $block = LayoutSectionBlock::findOrFail($blockId);
        $block->update(['content' => $content]);

        return $block->fresh();
    }
}
