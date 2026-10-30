<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Services;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;

/**
 * The sections of one collection as a tree: where each stands, in the order the collection keeps
 * them (the sorting, then the name), and the names that make a path of it.
 */
final readonly class SectionTree
{
    /** @var array<int, list<int>> */
    private array $children;

    /**
     * @param array<int, int>    $parents The id of a section => the id of its parent, 0 at the top.
     * @param array<int, string> $names
     * @param array<int, int>    $sorts
     * @param array<int, string> $codes   What a section adds to the address of its items.
     */
    public function __construct(
        private array $parents,
        private array $names = [],
        private array $sorts = [],
        private array $codes = [],
    ) {
        $children = [];
        foreach ($parents as $id => $parent) {
            $children[$parent][] = $id;
        }

        foreach ($children as $parent => $ids) {
            usort($ids, fn (int $a, int $b): int => [$this->sorts[$a] ?? 0, $this->names[$a] ?? ''] <=> [$this->sorts[$b] ?? 0, $this->names[$b] ?? '']);
            $children[$parent] = $ids;
        }

        $this->children = $children;
    }

    /**
     * @param iterable<ContentCollectionSection> $sections
     */
    public static function of(iterable $sections): self
    {
        $parents = $names = $sorts = $codes = [];
        foreach ($sections as $section) {
            $parents[$section->id] = $section->parent ?? 0;
            $names[$section->id] = $section->name;
            $sorts[$section->id] = $section->sort;
            $codes[$section->id] = $section->code;
        }

        return new self($parents, $names, $sorts, $codes);
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
     * The sections above this one, from its parent up to the top.
     *
     * @return list<int>
     */
    public function ancestorsOf(int $id): array
    {
        $ancestors = [];
        $seen = [$id => true];

        while (isset($this->parents[$id])) {
            $id = $this->parents[$id];
            if ($id === 0 || isset($seen[$id])) {
                break;
            }

            $seen[$id] = true;
            $ancestors[] = $id;
        }

        return $ancestors;
    }

    public function depthOf(int $id): int
    {
        return count($this->ancestorsOf($id));
    }

    /**
     * The names from the top down to this section; empty for one the tree does not have.
     *
     * @return list<string>
     */
    public function pathOf(int $id): array
    {
        if (! $this->has($id)) {
            return [];
        }

        $path = [];
        foreach (array_reverse($this->ancestorsOf($id)) as $ancestor) {
            $path[] = $this->names[$ancestor] ?? '';
        }
        $path[] = $this->names[$id] ?? '';

        return $path;
    }

    /**
     * The codes from the top down to this section, as its part of the address of an item.
     *
     * @return list<string>
     */
    public function codePathOf(int $id): array
    {
        if (! $this->has($id)) {
            return [];
        }

        $path = [];
        foreach (array_reverse($this->ancestorsOf($id)) as $ancestor) {
            $path[] = $this->codes[$ancestor] ?? '';
        }
        $path[] = $this->codes[$id] ?? '';

        return $path;
    }

    /**
     * Every section, each under its parent: the order of a list that shows the tree flat. A
     * section whose parent is gone comes last, with what is under it.
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
        foreach ($this->children[$parent] ?? [] as $child) {
            if (in_array($child, $ordered, true)) {
                continue;
            }

            $ordered[] = $child;
            $this->appendChildren($child, $ordered);
        }
    }
}
