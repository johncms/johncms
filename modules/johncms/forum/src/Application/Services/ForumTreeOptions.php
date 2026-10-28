<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

/**
 * The places in the tree of the forum a section, or what it holds, can go to, each named by its
 * path from the root: the choices of a select, and the list a choice is checked against.
 */
final readonly class ForumTreeOptions
{
    public const int TYPE_SECTIONS = 0;
    public const int TYPE_TOPICS = 1;

    /**
     * The root and every section that holds sections. The given section and those under it are
     * left out: nothing goes under itself.
     *
     * @param Collection<int, ForumSection> $sections Every section, in the order of sort.
     * @return list<array{id: int, title: string}>
     */
    public function sectionHolders(Collection $sections, ?int $excluded): array
    {
        return [
            ['id' => 0, 'title' => __('The root: a category')],
            ...$this->collect($sections, $excluded, self::TYPE_SECTIONS),
        ];
    }

    /**
     * Every section that holds topics, but the given one.
     *
     * @param Collection<int, ForumSection> $sections Every section, in the order of sort.
     * @return list<array{id: int, title: string}>
     */
    public function topicHolders(Collection $sections, int $excluded): array
    {
        return $this->collect($sections, $excluded, self::TYPE_TOPICS);
    }

    /**
     * @param Collection<int, ForumSection> $sections
     * @return list<array{id: int, title: string}>
     */
    private function collect(Collection $sections, ?int $excluded, int $type): array
    {
        $children = [];
        foreach ($sections as $section) {
            $children[(int) $section->parent][] = $section;
        }

        return $this->branch($children, 0, [], $excluded, $type);
    }

    /**
     * @param array<int, list<ForumSection>> $children
     * @param list<string>                   $path
     * @return list<array{id: int, title: string}>
     */
    private function branch(array $children, int $parent, array $path, ?int $excluded, int $type): array
    {
        $options = [];

        foreach ($children[$parent] ?? [] as $section) {
            if ($section->id === $excluded) {
                continue;
            }

            $own = [...$path, $section->name];
            if ((int) $section->section_type === $type) {
                $options[] = ['id' => $section->id, 'title' => implode(' › ', $own)];
            }
            array_push($options, ...$this->branch($children, $section->id, $own, $excluded, $type));
        }

        return $options;
    }
}
