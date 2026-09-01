<?php

namespace App\Services;
use Illuminate\Support\Collection;
use App\Models\LayoutSection;
use App\Models\LayoutSectionBlock;

interface LandingPageServiceInterface
{
    public function getLayout(): Collection;

    public function getFullLayout(): Collection;

    public function reorderSections(array $orderedIds): void;

    public function reorderBlocks(int $sectionId, array $orderedIds): void;

    public function toggleSection(int $sectionId): LayoutSection;

    public function updateBlockContent(int $blockId, array $content): LayoutSectionBlock;
}
