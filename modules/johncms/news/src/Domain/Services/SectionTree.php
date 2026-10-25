<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Services;

/**
 * The tree of the sections, built from where each of them stands, and the names that make a path
 * of it.
 */
final readonly class SectionTree
{
    /** @var array<int, list<int>> */
    private array $children;

    /**
     * @param array<int, int>    $parents The id of a section => the id of its parent, 0 at the root.
     * @param array<int, string> $names   The id of a section => its name.
     */
    public function __construct(
        private array $parents,
        private array $names = [],
    ) {
        $children = [];
        foreach ($parents as $id => $parent) {
            $children[$parent][] = $id;
        }

        $this->children = $children;
    }

    public function has(int $id): bool
    {
        return isset($this->parents[$id]);
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

    /**
     * The sections standing right under this one; under 0, the sections at the top.
     *
     * @return list<int>
     */
    public function childrenOf(int $id): array
    {
        return $this->children[$id] ?? [];
    }

    /**
     * The sections from the top down to this one: the names of a path. Empty for a section the
     * tree does not have.
     *
     * @return list<string>
     */
    public function pathOf(int $id): array
    {
        $path = [];
        $seen = [];

        while (isset($this->parents[$id]) && ! isset($seen[$id])) {
            $seen[$id] = true;
            array_unshift($path, $this->names[$id] ?? '');
            $id = $this->parents[$id];
        }

        return $path;
    }

    /**
     * Every section, each under its parent, the children of a section by name: the order of a list
     * that shows the tree flat. A section whose parent is gone comes last, with what is under it.
     *
     * @return list<int>
     */
    public function ordered(): array
    {
        $ordered = [];
        $this->appendChildren(0, $ordered);

        foreach (array_keys($this->parents) as $id) {
            if (! in_array($id, $ordered, true)) {
                $ordered[] = $id;
                $this->appendChildren($id, $ordered);
            }
        }

        return $ordered;
    }

    /**
     * @param list<int> $ordered
     */
    private function appendChildren(int $parent, array &$ordered): void
    {
        $children = $this->children[$parent] ?? [];
        usort($children, fn (int $a, int $b): int => strnatcasecmp($this->names[$a] ?? '', $this->names[$b] ?? ''));

        foreach ($children as $child) {
            if (in_array($child, $ordered, true)) {
                continue;
            }

            $ordered[] = $child;
            $this->appendChildren($child, $ordered);
        }
    }
}
