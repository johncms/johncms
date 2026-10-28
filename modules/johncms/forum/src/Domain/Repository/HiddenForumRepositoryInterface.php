<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\ValueObjects\HiddenListFilter;

/**
 * The hidden posts and topics of the forum, narrowed by the filter of the list in the panel.
 */
interface HiddenForumRepositoryInterface
{
    /**
     * What the tab of the filter holds under the filter.
     */
    public function count(HiddenListFilter $filter): int;

    /**
     * Every hidden post or topic, whatever the filter, keyed by the value of HiddenListTab.
     *
     * @return array<string, int>
     */
    public function totals(): array;

    /**
     * The newest first, each with its topic and its author.
     *
     * @return Collection<int, ForumMessage>
     */
    public function posts(HiddenListFilter $filter, int $limit, int $offset): Collection;

    /**
     * The newest first, each with its section.
     *
     * @return Collection<int, ForumTopic>
     */
    public function topics(HiddenListFilter $filter, int $limit, int $offset): Collection;

    /**
     * The names of those who hid something in the tab, in the order of the alphabet.
     *
     * @return list<string>
     */
    public function hiders(HiddenListTab $tab): array;

    public function authorName(int $userId): ?string;

    public function topicName(int $topicId): ?string;

    public function sectionName(int $sectionId): ?string;

    /**
     * The names of the files attached to what the filter lets through, so they can be taken off
     * the disk.
     *
     * @return list<string>
     */
    public function attachmentFilenames(HiddenListFilter $filter): array;

    /**
     * Deletes what the filter lets through for good: a post with its files, a topic with its
     * posts and files.
     */
    public function delete(HiddenListFilter $filter): void;
}
