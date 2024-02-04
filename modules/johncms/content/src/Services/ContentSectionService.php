<?php

declare(strict_types=1);

namespace Johncms\Content\Services;

use Johncms\Content\Models\ContentSection;

class ContentSectionService
{
    public function getAllContentTypeSections(int $contentTypeId, array $excludeIds = []): array
    {
        $sections = ContentSection::query()
            ->where('content_type_id', $contentTypeId)
            ->whereNull('parent')
            ->get();
        $result = [];

        foreach ($sections as $section) {
            if (in_array($section->id, $excludeIds)) {
                continue;
            }
            $sectionData = $section->toArray();
            $sectionData['subsections'] = $this->getSubsections($section->id, $excludeIds);
            $result[] = $sectionData;
        }

        return $result;
    }

    public function getAllContentTypeSectionsFlatList(int $contentTypeId, array $excludeIds = []): array
    {
        $sections = $this->getAllContentTypeSections($contentTypeId, $excludeIds);
        $result = [];
        $getSubsections = function (array $subsections, $getSubsectionsFunction, int $level = 0) use (&$result) {
            foreach ($subsections as $subsection) {
                $subsection['level'] = $level;
                $tmpSubsections = $subsection['subsections'];
                unset($subsection['subsections']);
                $result[] = $subsection;

                // Get children sections
                $getSubsectionsFunction($tmpSubsections, $getSubsectionsFunction, $level + 1);
            }
        };

        $getSubsections($sections, $getSubsections);

        return $result;
    }

    public function getSubsections(int $sectionId, array $excludeIds = []): array
    {
        $sections = ContentSection::query()->where('parent', $sectionId)->get();
        $result = [];
        foreach ($sections as $section) {
            if (in_array($section->id, $excludeIds)) {
                continue;
            }
            $sectionData = $section->toArray();
            $sectionData['subsections'] = $this->getSubsections($section->id);
            $result[] = $sectionData;
        }

        return $result;
    }
}
