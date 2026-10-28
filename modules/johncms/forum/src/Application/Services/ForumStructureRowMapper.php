<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

/**
 * The rows of the tree of the forum, as forum::admin/index draws them: every section under its
 * parent, how deep it stands, what it holds, and what can be done to it.
 *
 * A section whose parent does not exist is left out, as it is on the site.
 */
final readonly class ForumStructureRowMapper
{
    private const string URL = '/admin/forum/structure';

    /**
     * @param Collection<int, ForumSection> $sections Every section, in the order of sort.
     * @return list<array<string, mixed>>
     */
    public function map(Collection $sections): array
    {
        $children = [];
        foreach ($sections as $section) {
            $children[(int) $section->parent][] = $section;
        }

        return $this->branch($children, 0, 0);
    }

    /**
     * @param array<int, list<ForumSection>> $children
     * @return list<array<string, mixed>>
     */
    private function branch(array $children, int $parent, int $depth): array
    {
        $rows = [];

        foreach ($children[$parent] ?? [] as $section) {
            $rows[] = $this->row($section, $depth);
            array_push($rows, ...$this->branch($children, $section->id, $depth + 1));
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(ForumSection $section, int $depth): array
    {
        $holdsTopics = $section->section_type === 1;
        $editUrl = self::URL . '/' . $section->id . '/edit';

        $actions = [$this->action($editUrl, __('Edit'), 'pencil')];
        if (! $holdsTopics) {
            $actions[] = $this->action(self::URL . '/new?parent=' . $section->id, __('Add subsection'), 'folder-plus');
        }
        $actions[] = $this->action($section->url, __('Open on the site'), 'external-link');
        $actions[] = $this->action(self::URL . '/' . $section->id . '/delete', __('Delete'), 'trash-2', danger: true);

        return [
            'id'           => $section->id,
            'name'         => $section->name,
            'description'  => (string) $section->description,
            'depth'        => $depth,
            'holds_topics' => $holdsTopics,
            'count'        => (int) $section->getAttribute($holdsTopics ? 'topics_count' : 'subsections_count'),
            'sort'         => $section->sort,
            'access'       => match ($section->access) {
                1       => __('Authors are curators'),
                2       => __('Authors edit the first post'),
                4       => __('Only for reading'),
                default => '',
            },
            'read_only'    => $section->access === 4,
            'edit_url'     => $editUrl,
            'actions'      => $actions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function action(string $url, string $title, string $icon, bool $danger = false): array
    {
        return ['method' => 'get', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => false, 'fields' => []];
    }
}
