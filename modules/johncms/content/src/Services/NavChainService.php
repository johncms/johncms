<?php

declare(strict_types=1);

namespace Johncms\Content\Services;

use Johncms\Content\Models\ContentSection;
use Johncms\Content\Models\ContentType;
use Johncms\NavChain;

class NavChainService
{
    public function __construct(
        private readonly NavChain $navChain
    ) {
    }

    /**
     * @param int $sectionId
     * @return ContentSection[]
     */
    public function getNavPathToSection(int $sectionId): array
    {
        $path = [];
        $section = ContentSection::query()->find($sectionId);
        $path[] = $section;
        while ($section = $section?->parentSection) {
            $path[] = $section;
        }
        krsort($path);
        return array_values($path);
    }

    public function setAdminBreadcrumbs(int $typeId, ?int $sectionId = null, bool $lastIsEmpty = false): void
    {
        $contentType = ContentType::query()->findOrFail($typeId);
        $this->navChain->add($contentType->name, route('content.admin.sections', ['type' => $contentType->id]));

        if ($sectionId) {
            $sections = $this->getNavPathToSection($sectionId);
            $total = count($sections);
            foreach ($sections as $key => $section) {
                if (! $lastIsEmpty || $total !== ($key + 1)) {
                    $this->navChain->add($section->name, route('content.admin.sections', ['sectionId' => $section->id, 'type' => $contentType->id]));
                } else {
                    $this->navChain->add($section->name);
                }
            }
        }
    }
}
