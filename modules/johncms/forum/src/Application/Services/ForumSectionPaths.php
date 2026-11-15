<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Every section of the forum, read once per request, and the address of each worked out in
 * memory: a listing of many topics or sections needs no query for each of them.
 */
final class ForumSectionPaths implements ResetInterface
{
    /** @var array<int, ForumSection>|null */
    private ?array $sections = null;

    /** @var array<int, string> */
    private array $paths = [];

    public function __construct(
        private readonly ForumSectionRepositoryInterface $sectionRepository,
    ) {
    }

    /**
     * Every section by its id, in the order of sort.
     *
     * @return array<int, ForumSection>
     */
    public function sections(): array
    {
        if ($this->sections === null) {
            $this->sections = [];
            foreach ($this->sectionRepository->getAllOrdered() as $section) {
                $this->sections[$section->id] = $section;
            }
        }

        return $this->sections;
    }

    public function find(int $sectionId): ?ForumSection
    {
        return $this->sections()[$sectionId] ?? null;
    }

    /** The address of the section, null when it does not exist. */
    public function url(int $sectionId): ?string
    {
        $path = $this->path($sectionId);

        return $path === null ? null : '/forum/' . $path . '/';
    }

    /** The slugs from the root down to the section, null when it does not exist. */
    public function path(int $sectionId): ?string
    {
        if (isset($this->paths[$sectionId])) {
            return $this->paths[$sectionId];
        }

        $section = $this->find($sectionId);
        if ($section === null) {
            return null;
        }

        $parentPath = (int) $section->parent > 0 ? $this->path((int) $section->parent) : null;
        $slug = trim((string) $section->slug);

        return $this->paths[$sectionId] = $parentPath === null ? $slug : $parentPath . '/' . $slug;
    }

    public function reset(): void
    {
        $this->sections = null;
        $this->paths = [];
    }
}
