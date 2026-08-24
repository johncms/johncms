<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;

final readonly class ForumSectionTreeService
{
    public function __construct(
        private ForumSectionRepositoryInterface $sectionRepository,
    ) {
    }

    /**
     * Returns a flat section list where nesting is expressed by an indent prefix in the name.
     *
     * @return array<int, array{id: int, name: string, parent: int}>
     */
    public function getFlatTree(): array
    {
        $childrenByParent = [];
        foreach ($this->sectionRepository->getAllOrdered() as $section) {
            $childrenByParent[(int) $section->parent][] = $section;
        }

        return $this->buildBranch($childrenByParent, 0, '');
    }

    /**
     * Returns the section chain from the root down to the given section, inclusive.
     *
     * @return ForumSection[]
     */
    public function getAncestors(int $sectionId): array
    {
        $sectionsById = [];
        foreach ($this->sectionRepository->getAllOrdered() as $section) {
            $sectionsById[(int) $section->id] = $section;
        }

        $chain = [];
        $currentId = $sectionId;
        while ($currentId > 0 && isset($sectionsById[$currentId])) {
            $section = $sectionsById[$currentId];
            unset($sectionsById[$currentId]);
            $chain[] = $section;
            $currentId = (int) $section->parent;
        }

        return array_reverse($chain);
    }

    /**
     * @param array<int, ForumSection[]> $childrenByParent
     *
     * @return array<int, array{id: int, name: string, parent: int}>
     */
    private function buildBranch(array $childrenByParent, int $parentId, string $indent): array
    {
        $branch = [];

        foreach ($childrenByParent[$parentId] ?? [] as $section) {
            $branch[] = [
                'id'     => (int) $section->id,
                'name'   => $indent . ' ' . $section->name,
                'parent' => (int) $section->parent,
            ];

            $branch = array_merge($branch, $this->buildBranch($childrenByParent, (int) $section->id, $indent . ' . '));
        }

        return $branch;
    }
}
