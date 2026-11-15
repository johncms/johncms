<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\DTO\ForumLastTopicDTO;
use Johncms\Modules\Forum\Application\DTO\ForumSectionRowDTO;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Utils\DateFormatterInterface;

/**
 * The sections under a section, as the rows of the root of the forum and of a category show them.
 *
 * Three queries whatever the size of the forum: every section, the counts of the topics grouped by
 * section, and the topics that end the sections. What a section holds is counted over all the
 * sections under it. The counts follow what the visitor may see: hidden topics and posts count only
 * for whoever may see them, and only then may a hidden topic be the last one of a section.
 */
final readonly class ForumSectionRows
{
    public function __construct(
        private ForumSectionPaths $sectionPaths,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private AccessCheckerInterface $accessChecker,
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * @return list<ForumSectionRowDTO>
     */
    public function under(int $parentId): array
    {
        $withHidden = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
        $sections = $this->sectionPaths->sections();
        $children = $this->children($sections);
        $totals = $this->count($children, $withHidden);

        $rows = $children[$parentId] ?? [];
        $shown = [...$rows];
        foreach ($rows as $row) {
            array_push($shown, ...($children[$row->id] ?? []));
        }

        $lastTopics = $this->lastTopics($shown, $totals, $sections, $withHidden);

        return array_map(
            fn(ForumSection $section): ForumSectionRowDTO => $this->row(
                $section,
                $totals,
                $lastTopics,
                array_map(
                    fn(ForumSection $child): ForumSectionRowDTO => $this->row($child, $totals, $lastTopics, []),
                    $children[$section->id] ?? [],
                ),
            ),
            $rows,
        );
    }

    /**
     * What a section holds together with the sections under it.
     *
     * @return array{topics: int, posts: int}
     */
    public function totalsOf(int $sectionId): array
    {
        $children = $this->children($this->sectionPaths->sections());
        $totals = $this->count($children, $this->accessChecker->allows(ForumPermissions::DELETED_VIEW));

        return [
            'topics' => $totals[$sectionId]['topics'] ?? 0,
            'posts'  => $totals[$sectionId]['posts'] ?? 0,
        ];
    }

    /**
     * @param array<int, ForumSection> $sections
     * @return array<int, list<ForumSection>> The sections right under each one, by its id.
     */
    private function children(array $sections): array
    {
        $children = [];
        foreach ($sections as $section) {
            $children[(int) $section->parent][] = $section;
        }

        return $children;
    }

    /**
     * @param array<int, list<ForumSection>> $children
     * @return array<int, array{topics: int, posts: int, last_date: int, last_in: int}>
     */
    private function count(array $children, bool $withHidden): array
    {
        $own = [];
        foreach ($this->topicRepository->getSectionStats($withHidden) as $stats) {
            $own[$stats['section_id']] = $stats;
        }

        $totals = [];
        $this->walk($children, 0, $totals, $own);

        return $totals;
    }

    /**
     * What the given rows hold together.
     *
     * @param list<ForumSectionRowDTO> $rows
     * @return array{topics: int, posts: int}
     */
    public function totals(array $rows): array
    {
        return [
            'topics' => array_sum(array_map(static fn(ForumSectionRowDTO $row): int => $row->topics, $rows)),
            'posts'  => array_sum(array_map(static fn(ForumSectionRowDTO $row): int => $row->posts, $rows)),
        ];
    }

    /**
     * Fills what every section holds together with the sections under it.
     *
     * @param array<int, list<ForumSection>>                                          $children
     * @param array<int, array{topics: int, posts: int, last_date: int, last_in: int}>    $totals
     * @param array<int, array{section_id: int, topics: int, posts: int, last_date: int}> $own
     */
    private function walk(array $children, int $parentId, array &$totals, array $own): void
    {
        foreach ($children[$parentId] ?? [] as $section) {
            $this->walk($children, $section->id, $totals, $own);

            $total = [
                'topics'    => $own[$section->id]['topics'] ?? 0,
                'posts'     => $own[$section->id]['posts'] ?? 0,
                'last_date' => $own[$section->id]['last_date'] ?? 0,
                'last_in'   => $section->id,
            ];
            foreach ($children[$section->id] ?? [] as $child) {
                $total['topics'] += $totals[$child->id]['topics'];
                $total['posts'] += $totals[$child->id]['posts'];
                if ($totals[$child->id]['last_date'] > $total['last_date']) {
                    $total['last_date'] = $totals[$child->id]['last_date'];
                    $total['last_in'] = $totals[$child->id]['last_in'];
                }
            }
            $totals[$section->id] = $total;
        }
    }

    /**
     * The topic that ends each of the given sections, by the id of the section.
     *
     * @param list<ForumSection>                                                    $shown
     * @param array<int, array{topics: int, posts: int, last_date: int, last_in: int}> $totals
     * @param array<int, ForumSection>                                              $sections
     * @return array<int, ForumLastTopicDTO>
     */
    private function lastTopics(array $shown, array $totals, array $sections, bool $withHidden): array
    {
        $wanted = [];
        foreach ($shown as $section) {
            $total = $totals[$section->id];
            if ($total['last_date'] > 0) {
                $wanted[$total['last_in']] = $total['last_date'];
            }
        }

        /** @var array<int, ForumTopic> $ending */
        $ending = [];
        foreach ($this->topicRepository->getEndingAt($wanted, $withHidden) as $topic) {
            $current = $ending[$topic->section_id] ?? null;
            if ($current === null || $topic->id > $current->id) {
                $ending[$topic->section_id] = $topic;
            }
        }

        $lastTopics = [];
        foreach ($shown as $section) {
            $topic = $ending[$totals[$section->id]['last_in']] ?? null;
            if ($topic === null || ! isset($sections[$topic->section_id])) {
                continue;
            }

            $lastTopics[$section->id] = new ForumLastTopicDTO(
                name: (string) $topic->name,
                url: $this->sectionPaths->url($topic->section_id) . $this->topicPathService->getTopicSlug($topic) . '-' . $topic->id . '/',
                author: (string) ($withHidden ? $topic->mod_last_post_author_name : $topic->last_post_author_name),
            );
        }

        return $lastTopics;
    }

    /**
     * @param array<int, array{topics: int, posts: int, last_date: int, last_in: int}> $totals
     * @param array<int, ForumLastTopicDTO>                                         $lastTopics
     * @param list<ForumSectionRowDTO>                                              $children
     */
    private function row(ForumSection $section, array $totals, array $lastTopics, array $children): ForumSectionRowDTO
    {
        $total = $totals[$section->id];

        return new ForumSectionRowDTO(
            id: $section->id,
            name: (string) $section->name,
            url: (string) $this->sectionPaths->url($section->id),
            description: trim((string) $section->description),
            holdsTopics: $section->section_type === 1,
            topics: $total['topics'],
            posts: $total['posts'],
            lastDate: $total['last_date'] > 0 ? $this->dateFormatter->format($total['last_date']) : '',
            lastTopic: $lastTopics[$section->id] ?? null,
            children: $children,
        );
    }
}
