<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;

/**
 * Where a topic can be moved: every section that holds topics, the one it is in left out, each
 * titled with its path. The same list fills the form of the page and the dialog of the topic.
 */
final readonly class TopicMoveTargets
{
    public function __construct(
        private ForumStructureRepositoryInterface $structure,
        private ForumTreeOptions $treeOptions,
    ) {
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    public function forSection(int $currentSectionId): array
    {
        return $this->treeOptions->topicHolders($this->structure->tree(), $currentSectionId);
    }
}
