<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

/**
 * Recalculates the denormalized message counters and last post data of a topic.
 */
final readonly class ForumTopicStatsRecalculator
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function recalculate(int $topicId): void
    {
        $lastPost = $this->messageRepository->findLastByTopicId($topicId, false);
        $modLastPost = $this->messageRepository->findLastByTopicId($topicId, true);

        $this->topicRepository->updateStats(
            $topicId,
            [
                'post_count'                => $this->messageRepository->countByTopicId($topicId, false),
                'mod_post_count'            => $this->messageRepository->countByTopicId($topicId, true),
                'last_post_date'            => $lastPost?->date,
                'last_post_author'          => $lastPost?->user_id,
                'last_post_author_name'     => $lastPost?->user_name,
                'last_message_id'           => $lastPost?->id,
                'mod_last_post_date'        => $modLastPost?->date,
                'mod_last_post_author'      => $modLastPost?->user_id,
                'mod_last_post_author_name' => $modLastPost?->user_name,
                'mod_last_message_id'       => $modLastPost?->id,
            ]
        );
    }
}
