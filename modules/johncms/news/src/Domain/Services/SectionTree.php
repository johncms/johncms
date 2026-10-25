<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Services;

/**
 * The tree of the sections, built from where each of them stands.
 */
final readonly class SectionTree
{
    /** @var array<int, list<int>> */
    private array $children;

    /**
     * @param array<int, int> $parents The id of a section => the id of its parent, 0 at the root.
     */
    public function __construct(array $parents)
    {
        $children = [];
        foreach ($parents as $id => $parent) {
            $children[$parent][] = $id;
        }

        $this->children = $children;
    }

    /**
     * The section and everything under it, the section first.
     *
     * @return list<int>
     */
    public function subtreeOf(int $id): array
    {
        $ids = [$id];
        // A parent loop in the data would otherwise never end.
        for ($i = 0; $i < count($ids); $i++) {
            foreach ($this->children[$ids[$i]] ?? [] as $child) {
                if (! in_array($child, $ids, true)) {
                    $ids[] = $child;
                }
            }
        }

        return $ids;
    }
}
