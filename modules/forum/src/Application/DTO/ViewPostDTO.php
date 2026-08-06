<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Twig\Markup;

final readonly class ViewPostDTO
{
    /**
     * @param PostFileDTO[] $files
     */
    public function __construct(
        public int $id,
        public bool $isDeleted,
        public Markup $body,
        public string $createdAt,
        public PostAuthorDTO $author,
        public ?PostEditInfoDTO $editInfo,
        public array $files,
        public ?PostModerationDTO $moderation,
        public PostActionsDTO $actions,
        public string $backToTopicUrl,
        public string $forumUrl,
    ) {
    }
}
