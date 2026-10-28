<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\HiddenForumRepositoryInterface;
use Johncms\Modules\Forum\Domain\ValueObjects\HiddenListFilter;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;

/**
 * The hidden posts and topics of the forum: the list of the panel, and emptying it for good.
 */
final readonly class ManageHiddenForumUseCase
{
    public function __construct(
        private HiddenForumRepositoryInterface $repository,
        private ForumAttachmentStorage $attachments,
    ) {
    }

    public function count(HiddenListFilter $filter): int
    {
        return $this->repository->count($filter);
    }

    /**
     * @return array<string, int> Keyed by the value of HiddenListTab.
     */
    public function totals(): array
    {
        return $this->repository->totals();
    }

    /**
     * @return Collection<int, ForumMessage>
     */
    public function posts(HiddenListFilter $filter, int $limit, int $offset): Collection
    {
        return $this->repository->posts($filter, $limit, $offset);
    }

    /**
     * @return Collection<int, ForumTopic>
     */
    public function topics(HiddenListFilter $filter, int $limit, int $offset): Collection
    {
        return $this->repository->topics($filter, $limit, $offset);
    }

    /**
     * @return list<string>
     */
    public function hiders(HiddenListTab $tab): array
    {
        return $this->repository->hiders($tab);
    }

    /**
     * The names the chips of the filter show instead of the numbers in it.
     *
     * @return array{author: string, topic: string, section: string}
     */
    public function names(HiddenListFilter $filter): array
    {
        return [
            'author'  => $filter->author !== null ? ($this->repository->authorName($filter->author) ?? '#' . $filter->author) : '',
            'topic'   => $filter->topic !== null ? ($this->repository->topicName($filter->topic) ?? '#' . $filter->topic) : '',
            'section' => $filter->section !== null ? ($this->repository->sectionName($filter->section) ?? '#' . $filter->section) : '',
        ];
    }

    /**
     * Deletes for good what the filter lets through, the files on the disk included.
     */
    public function purge(HiddenListFilter $filter): void
    {
        foreach ($this->repository->attachmentFilenames($filter) as $filename) {
            $this->attachments->delete($filename);
        }

        $this->repository->delete($filter);
    }
}
