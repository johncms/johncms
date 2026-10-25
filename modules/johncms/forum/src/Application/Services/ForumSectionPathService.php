<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The paths of the sections of the forum, remembered for the rest of the request: a page of
 * topics asks for the same few sections over and over. Reset before every request, since a
 * section may be renamed or moved between two of them.
 */
final class ForumSectionPathService implements ResetInterface
{
    /** @var array<int, string> */
    private array $pathCache = [];

    /** @var array<string, ForumSection> */
    private array $sectionByPathCache = [];

    public function __construct(
        private ForumSectionRepositoryInterface $sectionRepository,
    ) {
    }

    public function findSectionByPath(string $sectionPath): ?ForumSection
    {
        $normalizedPath = trim($sectionPath, '/');
        if ($normalizedPath === '') {
            return null;
        }

        if (isset($this->sectionByPathCache[$normalizedPath])) {
            return $this->sectionByPathCache[$normalizedPath];
        }

        $segments = explode('/', $normalizedPath);
        $parentSectionId = 0;
        $section = null;

        foreach ($segments as $segment) {
            $section = $this->sectionRepository->findByParentAndSlug($parentSectionId, $segment);

            if ($section === null) {
                return null;
            }

            $parentSectionId = $section->id;
        }

        $this->sectionByPathCache[$normalizedPath] = $section;
        $this->pathCache[$section->id] = $normalizedPath;

        return $section;
    }

    public function getSectionPath(ForumSection $section): string
    {
        if (isset($this->pathCache[$section->id])) {
            return $this->pathCache[$section->id];
        }

        $segments = [trim((string) $section->slug)];
        $parentId = (int) ($section->parent ?? 0);

        while ($parentId > 0) {
            $parent = $this->sectionRepository->findById($parentId);
            if ($parent === null) {
                break;
            }

            array_unshift($segments, trim((string) $parent->slug));
            $parentId = (int) ($parent->parent ?? 0);
        }

        $path = implode('/', $segments);
        $this->pathCache[$section->id] = $path;
        $this->sectionByPathCache[$path] = $section;

        return $path;
    }

    public function getSectionUrl(ForumSection $section): string
    {
        return '/forum/' . $this->getSectionPath($section) . '/';
    }

    public function reset(): void
    {
        $this->pathCache = [];
        $this->sectionByPathCache = [];
    }

    public function getSectionUrlById(int $sectionId): ?string
    {
        $section = $this->sectionRepository->findById($sectionId);
        if ($section === null) {
            return null;
        }

        return $this->getSectionUrl($section);
    }
}
