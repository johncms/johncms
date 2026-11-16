<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Twig\Markup;

/**
 * A post of a topic as the visitor sees it.
 */
final readonly class ForumPostDTO
{
    /**
     * @param list<PostFileDTO> $files
     */
    public function __construct(
        public int $id,
        public string $url,
        public Markup $body,
        public string $createdAt,
        public PostAuthorDTO $author,
        public ?PostEditInfoDTO $editInfo,
        public array $files,
        public bool $isDeleted,
        /** Who hid the post; empty when it is not hidden. */
        public string $deletedBy,
        /** Who brought the hidden post back; shown to those who see the hidden ones. */
        public string $restoredBy,
        /** Where the post came from; null unless the visitor may see it. */
        public ?PostModerationDTO $origin,
        public PostActionsDTO $actions,
    ) {
    }
}
